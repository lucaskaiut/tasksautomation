<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectEnvironmentProfile\StoreProjectEnvironmentProfileRequest;
use App\Http\Requests\ProjectEnvironmentProfile\UpdateProjectEnvironmentProfileRequest;
use App\Models\Project;
use App\Models\ProjectEnvironmentProfile;
use App\Services\ProjectEnvironmentProfile\CreateProjectEnvironmentProfileService;
use App\Services\ProjectEnvironmentProfile\UpdateProjectEnvironmentProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectEnvironmentProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project): View
    {
        $this->authorize('update', $project);

        $profiles = $project->environmentProfiles()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('projects.environment-profiles.index', compact('project', 'profiles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Project $project, StoreProjectEnvironmentProfileRequest $request, CreateProjectEnvironmentProfileService $service): RedirectResponse
    {
        $this->authorize('update', $project);

        $service->handle($request->profileData());

        return redirect()
            ->route('projects.environment-profiles.index', $project)
            ->with('success', 'Perfil de ambiente criado com sucesso.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project, ProjectEnvironmentProfile $environmentProfile): View
    {
        $this->authorize('update', $project);

        abort_unless($environmentProfile->project_id === $project->id, 404);

        return view('projects.environment-profiles.edit', [
            'project' => $project,
            'profile' => $environmentProfile,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Project $project,
        ProjectEnvironmentProfile $environmentProfile,
        UpdateProjectEnvironmentProfileRequest $request,
        UpdateProjectEnvironmentProfileService $service
    ): RedirectResponse {
        $this->authorize('update', $project);

        abort_unless($environmentProfile->project_id === $project->id, 404);

        $service->handle($environmentProfile, $request->profileData());

        return redirect()
            ->route('projects.environment-profiles.index', $project)
            ->with('success', 'Perfil de ambiente atualizado com sucesso.');
    }

    public function destroy(string $id)
    {
        abort(404);
    }
}
