<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Загрузка изображения аватара.
 */
class UploadFileRequest extends FormRequest
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
                'image',
                'mimes:'.implode(',', (array) config('uploads.avatar.mimes')),
                'max:'.(int) config('uploads.avatar.max_size'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Выберите изображение.',
            'file.image' => 'Можно загрузить только изображение.',
            'file.mimes' => 'Поддерживаются форматы JPG, PNG, WebP и GIF.',
            'file.max' => 'Размер изображения не должен превышать 5 МБ.',
        ];
    }
}
