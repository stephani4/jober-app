<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkingArea;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserProfileRpcService
{
    /**
     * Список всех доступных рабочих зон.
     */
    public function listAreas(User $user): array
    {
        return WorkingArea::select(['id', 'name', 'type'])->get()->toArray();
    }

    /**
     * Обновление профиля пользователя.
     */
    public function update(User $user, array $data): array
    {
        $validator = Validator::make($data, [
            'working_area_id' => 'nullable|exists:working_areas,id',
            'name' => 'sometimes|string|max:255',
            'birth_date' => 'sometimes|date',
            'avatar_id' => 'sometimes|nullable|integer|exists:files,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user->update($validator->validated());
        $user->loadMissing('avatar');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'working_area_id' => $user->working_area_id,
            'birth_date' => $user->birth_date?->toDateString(),
            'avatar_id' => $user->avatar_id,
            'avatar_url' => $user->avatar?->url(),
        ];
    }
}
