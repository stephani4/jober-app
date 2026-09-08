<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'type', 'geometry', 'points'])]
class WorkingArea extends Model
{
    use HasFactory;

    protected $casts = [
        'points' => 'string',
        'geometry' => 'string',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
