<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Загруженный файл: имя, расширение, размер и путь на диске загрузок.
 */
#[Fillable(['name', 'extension', 'size', 'path'])]
class File extends Model
{
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * Относительный URL файла: отдаётся через API, чтобы не зависеть от symlink storage.
     */
    public function url(): string
    {
        return self::urlFor((int) $this->id);
    }

    /**
     * URL файла по его идентификатору.
     */
    public static function urlFor(int $id): string
    {
        return "/api/files/{$id}";
    }
}
