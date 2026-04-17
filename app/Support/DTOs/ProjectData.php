<?php

namespace App\Support\DTOs;

final readonly class ProjectData
{
    /**
     * @param array<string,mixed>|null $globalRules
     */
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?string $description,
        public string $repositoryUrl,
        public string $defaultBranch,
        public ?array $globalRules,
        public bool $isActive,
    ) {
    }

    /**
     * @param array<string,mixed> $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            name: $validated['name'],
            slug: $validated['slug'] ?? null,
            description: $validated['description'] ?? null,
            repositoryUrl: $validated['repository_url'],
            defaultBranch: $validated['default_branch'] ?? 'main',
            globalRules: $validated['global_rules'] ?? null,
            isActive: (bool) ($validated['is_active'] ?? true),
        );
    }
}

