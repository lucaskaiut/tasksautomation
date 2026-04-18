<?php

namespace App\Support;

use App\Support\Enums\TaskStatus;

final class TaskStatusPresenter
{
    /**
     * @return array<string, array{label: string, badge_classes: string}>
     */
    public function presentations(): array
    {
        return collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status): array => [
                $status->value => [
                    'label' => $this->label($status),
                    'badge_classes' => $this->badgeClasses($status),
                ],
            ])
            ->all();
    }

    public function label(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::Draft => 'Rascunho',
            TaskStatus::Pending => 'Pendente',
            TaskStatus::Claimed => 'Em fila',
            TaskStatus::Running => 'Em andamento',
            TaskStatus::Review => 'Em revisão',
            TaskStatus::NeedsAdjustment => 'Precisa de ajustes',
            TaskStatus::Done => 'Concluída',
            TaskStatus::Failed => 'Falhou',
            TaskStatus::Blocked => 'Bloqueada',
            TaskStatus::Cancelled => 'Cancelada',
        };
    }

    public function badgeClasses(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::Draft => 'bg-slate-100 text-slate-700',
            TaskStatus::Pending => 'bg-amber-100 text-amber-800',
            TaskStatus::Claimed => 'bg-sky-100 text-sky-800',
            TaskStatus::Running => 'bg-blue-100 text-blue-800',
            TaskStatus::Review => 'bg-violet-100 text-violet-800',
            TaskStatus::NeedsAdjustment => 'bg-orange-100 text-orange-800',
            TaskStatus::Done => 'bg-emerald-100 text-emerald-800',
            TaskStatus::Failed => 'bg-rose-100 text-rose-800',
            TaskStatus::Blocked => 'bg-red-100 text-red-800',
            TaskStatus::Cancelled => 'bg-zinc-200 text-zinc-700',
        };
    }
}
