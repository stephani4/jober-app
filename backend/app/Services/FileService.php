<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use RuntimeException;

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
     * Отдаёт содержимое файла — ссылка работает в <img src> без заголовка Authorization.
     */
    public function response(File $file): StreamedResponse
    {
        $disk = Storage::disk($this->disk());

        abort_unless($disk->exists($file->path), 404);

        return $disk->response($file->path, $file->name, [
            'Content-Type' => $disk->mimeType($file->path) ?: 'application/octet-stream',
            // Аватары неизменяемы (при смене создаётся новый файл), поэтому агрессивный кеш безопасен.
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /**
     * Диск хранения загруженных файлов.
     */
    private function disk(): string
    {
        return (string) config('uploads.disk');
    }
}