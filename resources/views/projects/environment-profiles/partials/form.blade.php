@php
    $validationProfileValue = old('validation_profile');
    if ($validationProfileValue === null && $profile?->validation_profile !== null) {
        $validationProfileValue = json_encode($profile->validation_profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    if (is_array($validationProfileValue)) {
        $validationProfileValue = json_encode($validationProfileValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $environmentDefinitionValue = old('environment_definition');
    if ($environmentDefinitionValue === null && $profile?->environment_definition !== null) {
        $environmentDefinitionValue = json_encode($profile->environment_definition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    if (is_array($environmentDefinitionValue)) {
        $environmentDefinitionValue = json_encode($environmentDefinitionValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
@endphp

<div class="space-y-5">
    <div>
        <x-input-label for="name" value="Nome" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $profile->name ?? '')" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="slug" value="Slug (opcional)" />
        <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug', $profile->slug ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('slug')" />
    </div>

    <div>
        <x-input-label for="description" value="Descrição (opcional)" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $profile->description ?? '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>

    <div class="flex items-center gap-2">
        <input
            id="is_default"
            name="is_default"
            type="checkbox"
            value="1"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            @checked(old('is_default', $profile->is_default ?? false))
        />
        <label for="is_default" class="text-sm text-gray-700">Marcar como padrão</label>
        <x-input-error class="mt-2" :messages="$errors->get('is_default')" />
    </div>

    <div>
        <x-input-label for="docker_compose_yml" value="docker-compose.yml (opcional)" />
        <textarea id="docker_compose_yml" name="docker_compose_yml" rows="10" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('docker_compose_yml', $profile->docker_compose_yml ?? '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('docker_compose_yml')" />
    </div>

    <details class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3">
        <summary class="cursor-pointer text-sm font-medium text-gray-800">
            Campos avançados (JSON)
        </summary>

        <div class="mt-4 space-y-5">
            <div>
                <x-input-label for="validation_profile" value="Perfil de validação (JSON, opcional)" />
                <textarea id="validation_profile" name="validation_profile" rows="6" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $validationProfileValue }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('validation_profile')" />
            </div>

            <div>
                <x-input-label for="environment_definition" value="Definição de ambiente (JSON, opcional)" />
                <textarea id="environment_definition" name="environment_definition" rows="6" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $environmentDefinitionValue }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('environment_definition')" />
            </div>
        </div>
    </details>
</div>

