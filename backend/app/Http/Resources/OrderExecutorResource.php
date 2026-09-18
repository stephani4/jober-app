<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Краткие данные исполнителя для карточек заказа и страницы наблюдения.
 *
 * @mixin User
 */
class OrderExecutorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_id' => $this->avatar_id,
            // Связь avatar подгружается заранее, чтобы не было запросов в списках заказов.
            'avatar_url' => $this->whenLoaded('avatar', fn () => $this->avatar?->url()),
        ];
    }
}
