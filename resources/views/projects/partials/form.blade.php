@php
    $globalRulesValue = old('global_rules');
    if ($globalRulesValue === null && isset($project) && $project->global_rules !== null) {
        $globalRulesValue = json_encode($project->global_rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    if (is_array($globalRulesValue)) {
        $globalRulesValue = json_encode($globalRulesValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="name" value="Nome" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $project->name ?? '')" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="slug" value="Slug (opcional)" />
        <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug', $project->slug ?? '')" />
        <x-input-error class="mt-2" :messages="$errors->get('slug')" />
    </div>

    <div>
        <x-input-label for="description" value="Descrição (opcional)" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $project->description ?? '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="repository_url" value="Endereço do repositório" />
            <x-text-input id="repository_url" name="repository_url" type="text" class="mt-1 block w-full" :value="old('repository_url', $project->repository_url ?? '')" required />
            <x-input-error class="mt-2" :messages="$errors->get('repository_url')" />
        </div>

        <div>
            <x-input-label for="default_branch" value="Branch padrão" />
            <x-text-input id="default_branch" name="default_branch" type="text" class="mt-1 block w-full" :value="old('default_branch', $project->default_branch ?? 'main')" />
            <x-input-error class="mt-2" :messages="$errors->get('default_branch')" />
        </div>
    </div>

    <div>
        <x-input-label for="global_rules" value="Regras globais (JSON, opcional)" />
        <textarea id="global_rules" name="global_rules" rows="6" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $globalRulesValue }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('global_rules')" />
    </div>

    <div class="flex items-center gap-2">
        <input
            id="is_active"
            name="is_active"
            type="checkbox"
            value="1"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            @checked(old('is_active', $project->is_active ?? true))
        />
        <label for="is_active" class="text-sm text-gray-700">Ativo</label>
        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
    </div>
</div>
