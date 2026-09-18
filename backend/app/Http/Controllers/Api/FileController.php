<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\UploadFileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Загрузка файлов и отдача их содержимого.
 */
class FileController extends Controller
{
    public function __construct(
        private readonly FileService $files,
    ) {}

    /**
     * Загружает изображение сразу после выбора и возвращает его запись.
     * Полученный id клиент отправляет при сохранении формы (например, users.avatar_id).
     */
    public function storeAvatar(UploadFileRequest $request): JsonResponse
    {
        /** @var \Illuminate\Http\UploadedFile $upload */
        $upload = $request->file('file');
        $file = $this->files->storeAvatar($upload);

        return response()->json(FileResource::make($file)->resolve($request), 201);
    }

    /**
     * Отдаёт содержимое файла по id.
     */
    public function show(File $file): StreamedResponse
    {
        return $this->files->response($file);
    }
}
