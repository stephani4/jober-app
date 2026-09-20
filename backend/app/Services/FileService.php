<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Хранилище загруженных файлов: запись на диск и отдача содержимого по запросу.
 */
class FileService
{
    /**
     * Сохраняет загруженный файл на диск и создаёт запись в таблице files.
     *
     * @param  string  $directory  каталог внутри диска загрузок
     */
    public function store(UploadedFile $upload, string $directory): File
    {
        $path = $upload->store($directory, $this->disk());

        if ($path === false) {
            throw new RuntimeException('Не удалось сохранить файл на диск.');
        }

        return File::query()->create([
            'name' => $upload->getClientOriginalName(),
            'extension' => mb_strtolower($upload->getClientOriginalExtension()),
            'size' => (int) $upload->getSize(),
            'path' => $path,
        ]);
    }

    /**
     * Сохраняет изображение аватара пользователя.
     */
    public function storeAvatar(UploadedFile $upload): File
    {
        return $this->store($upload, (string) config('uploads.avatar.directory'));
    }

    /**
     * Сохраняет вложение точки заказа.
     */
    public function storeAttachment(UploadedFile $upload): File
    {
        return $this->store($upload, (string) config('uploads.attachment.directory'));
    }

    /**
     * Снимает пометку «временный» после того, как файл привязали к сущности.
     *
     * @param  list<int>  $ids
     */
    public function commit(array $ids, ?int $orderPointId = null): void
    {
        if ($ids === []) {
            return;
        }

        $attributes = ['temporary_at' => null];
        if ($orderPointId !== null) {
            $attributes['order_point_id'] = $orderPointId;
        }

        File::query()->whereIn('id', $ids)->update($attributes);
    }

    /**
     * Отдаёт содержимое файла — ссылка работает в <img src> без заголовка Authorization.
     *
     * @param  bool  $download  attachment, чтобы браузер скачал файл, а не открыл его
     */
    public function response(File $file, bool $download = false): StreamedResponse
    {
        $disk = Storage::disk($this->disk());

        abort_unless($disk->exists($file->path), 404);

        return $disk->response(
            $file->path,
            $file->name,
            [
                'Content-Type' => $disk->mimeType($file->path) ?: 'application/octet-stream',
                'Cache-Control' => 'public, max-age=604800',
            ],
            $download ? 'attachment' : 'inline',
        );
    }

    /**
     * Диск хранения загруженных файлов.
     */
    private function disk(): string
    {
        return (string) config('uploads.disk');
    }
}
