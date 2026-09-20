<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\UploadAttachmentRequest;
use App\Http\Requests\File\UploadFileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
        /** @var UploadedFile $upload */
        $upload = $request->file('file');
        $file = $this->files->storeAvatar($upload);

        return response()->json(FileResource::make($file)->resolve($request), 201);
    }

    /**
     * Загружает вложение сразу после выбора и возвращает его запись.
     * Пока заказ не сохранён, файл остаётся с temporary_at.
     */
    public function store(UploadAttachmentRequest $request): JsonResponse
    {
        /** @var UploadedFile $upload */
        $upload = $request->file('file');
        $file = $this->files->storeAttachment($upload);

        return response()->json(FileResource::make($file)->resolve($request), 201);
    }

    /**
     * Отдаёт содержимое файла по id. ?download=1 — скачивание с исходным именем.
     */
    public function show(Request $request, File $file): StreamedResponse
    {
        return $this->files->response($file, $request->boolean('download'));
    }
}
