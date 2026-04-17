<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'repository_url',
    'default_branch',
    'global_rules',
    'is_active',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function environmentProfiles(): HasMany
    {
        return $this->hasMany(ProjectEnvironmentProfile::class);
    }

    protected function casts(): array
    {
        return [
            'global_rules' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
