<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Загруженный файл: имя, расширение, размер и путь на диске загрузок.
 */
#[Fillable([
    'name',
    'extension',
    'size',
    'path',
    'temporary_at',
    'order_point_id',
])]
class File extends Model
{
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'temporary_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (File $file): void {
            // Новая загрузка сначала временная, пока её не привяжут к заказу или профилю.
            $file->temporary_at ??= now();
        });
    }

    /**
     * Точка заказа, к которой файл прикреплён после сохранения заказа.
     */
    public function orderPoint(): BelongsTo
    {
        return $this->belongsTo(OrderPoint::class);
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
