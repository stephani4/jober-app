<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Пользователь приложения: профиль в PWA и список в админке.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'role' => $this->role?->value,
            'role_label' => $this->role?->label(),
            'working_area_id' => $this->working_area_id,
            'avatar_id' => $this->avatar_id,
            'avatar_url' => $this->avatar?->url(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
