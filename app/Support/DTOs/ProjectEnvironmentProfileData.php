<?php

namespace App\Support\DTOs;

final readonly class ProjectEnvironmentProfileData
{
    /**
     * @param array<string,mixed>|null $validationProfile
     * @param array<string,mixed>|null $environmentDefinition
     */
    public function __construct(
        public int $projectId,
        public string $name,
        public ?string $slug,
        public ?string $description,
        public ?array $validationProfile,
        public ?array $environmentDefinition,
        public ?string $dockerComposeYml,
        public bool $isDefault,
    ) {
    }

    /**
     * @param array<string,mixed> $validated
     */
    public static function fromValidated(int $projectId, array $validated): self
    {
        return new self(
            projectId: $projectId,
            name: $validated['name'],
            slug: $validated['slug'] ?? null,
            description: $validated['description'] ?? null,
            validationProfile: $validated['validation_profile'] ?? null,
            environmentDefinition: $validated['environment_definition'] ?? null,
            dockerComposeYml: $validated['docker_compose_yml'] ?? null,
            isDefault: (bool) ($validated['is_default'] ?? false),
        );
    }
}

