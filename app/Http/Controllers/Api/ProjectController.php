<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\Project\CreateProjectService;
use App\Services\Project\UpdateProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->latest()
            ->paginate(20);

        return ProjectResource::collection($projects)
            ->additional(['message' => 'Lista de projetos.']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request, CreateProjectService $service): JsonResponse
    {
        $project = $service->handle($request->projectData());

        return (new ProjectResource($project))
            ->additional(['message' => 'Projeto criado com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return (new ProjectResource($project))
            ->additional(['message' => 'Projeto.']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project, UpdateProjectService $service): ProjectResource
    {
        $project = $service->handle($project, $request->projectData());

        return (new ProjectResource($project))
            ->additional(['message' => 'Projeto atualizado com sucesso.']);
    }
}
