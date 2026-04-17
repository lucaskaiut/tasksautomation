<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Services\Project\CreateProjectService;
use App\Services\Project\UpdateProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->latest()
            ->paginate(20);

        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Project::class);

        return view('projects.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request, CreateProjectService $service): RedirectResponse
    {
        $service->handle($request->projectData());

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projeto criado com sucesso.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project, UpdateProjectService $service): RedirectResponse
    {
        $service->handle($project, $request->projectData());

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projeto atualizado com sucesso.');
    }
}
