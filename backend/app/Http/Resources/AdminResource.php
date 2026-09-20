<?php

namespace App\Http\Resources;

use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Admin
 */
class AdminResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roleNames = $this->getRoleNames()->values()->all();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $roleNames,
            'role_labels' => collect($roleNames)
                ->map(fn (string $role) => AdminRole::tryFrom($role)?->label() ?? $role)
                ->values()
                ->all(),
            'permissions' => $this->getAllPermissions()->pluck('name')->values()->all(),
            'direct_permissions' => $this->getDirectPermissions()->pluck('name')->values()->all(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
