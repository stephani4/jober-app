<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use App\Services\UserProfileRpcService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    /** 1x1 PNG: содержимое нужно валидатору 'image', без зависимости от GD. */
    private const PNG_PIXEL = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==';

    private function fakeImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::PNG_PIXEL, true));
    }

    public function test_guest_cannot_upload_avatar(): void
    {
        $this->postJson('/api/uploads/avatar')->assertUnauthorized();
    }

    public function test_user_can_upload_avatar_and_get_file_id(): void
    {
        Storage::fake('uploads');
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->post('/api/uploads/avatar', ['file' => $this->fakeImage('avatar.png')])
            ->assertCreated()
            ->assertJsonPath('name', 'avatar.png')
            ->assertJsonPath('extension', 'png');

        $file = File::query()->findOrFail($response->json('id'));

        $this->assertSame('png', $file->extension);
        $this->assertGreaterThan(0, $file->size);
        $this->assertStringStartsWith('avatars/', $file->path);
        Storage::disk('uploads')->assertExists($file->path);
        $this->assertSame("/api/files/{$file->id}", $response->json('url'));
    }

    public function test_upload_rejects_non_image(): void
    {
        Storage::fake('uploads');
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->post('/api/uploads/avatar', [
                'file' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('files', 0);
    }

    public function test_uploaded_file_is_served_by_url(): void
    {
        Storage::fake('uploads');
        $user = User::factory()->create();

        $fileId = $this->actingAs($user, 'api')
            ->post('/api/uploads/avatar', ['file' => $this->fakeImage('avatar.png')])
            ->assertCreated()
            ->json('id');

        $this->get("/api/files/{$fileId}")
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_profile_update_attaches_uploaded_avatar(): void
    {
        Storage::fake('uploads');
        $user = User::factory()->create();

        $fileId = $this->actingAs($user, 'api')
            ->post('/api/uploads/avatar', ['file' => $this->fakeImage('avatar.png')])
            ->assertCreated()
            ->json('id');

        $profile = app(UserProfileRpcService::class)->update($user, [
            'name' => 'Иван',
            'avatar_id' => $fileId,
        ]);

        $this->assertSame($fileId, $user->fresh()->avatar_id);
        $this->assertSame($fileId, $profile['avatar_id']);
        $this->assertSame("/api/files/{$fileId}", $profile['avatar_url']);
    }

    public function test_profile_update_rejects_unknown_avatar(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);
        app(UserProfileRpcService::class)->update($user, ['avatar_id' => 999]);
    }
}
