<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Загрузка вложения точки заказа (docx, pdf, изображения).
 */
class UploadAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', (array) config('uploads.attachment.mimes')),
                'max:'.(int) config('uploads.attachment.max_size'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Выберите файл.',
            'file.mimes' => 'Поддерживаются форматы DOCX, PDF, JPG, PNG и WebP.',
            'file.max' => 'Размер файла не должен превышать 10 МБ.',
        ];
    }
}
