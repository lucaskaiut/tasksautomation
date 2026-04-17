<?php

namespace App\Models;

use Database\Factories\ProjectEnvironmentProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id',
    'name',
    'slug',
    'description',
    'validation_profile',
    'environment_definition',
    'docker_compose_yml',
    'is_default',
])]
class ProjectEnvironmentProfile extends Model
{
    /** @use HasFactory<ProjectEnvironmentProfileFactory> */
    use HasFactory;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function casts(): array
    {
        return [
            'validation_profile' => 'array',
            'environment_definition' => 'array',
            'is_default' => 'boolean',
        ];
    }
}
