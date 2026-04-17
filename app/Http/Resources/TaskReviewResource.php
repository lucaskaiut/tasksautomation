<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TaskReview
 */
class TaskReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'task_execution_id' => $this->task_execution_id,
            'created_by' => $this->created_by,
            'decision' => $this->decision?->value ?? (string) $this->decision,
            'notes' => $this->notes,
            'current_behavior' => $this->current_behavior,
            'expected_behavior' => $this->expected_behavior,
            'preserve_scope' => $this->preserve_scope,
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
