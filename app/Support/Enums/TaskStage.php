<?php

namespace App\Support\Enums;

enum TaskStage: string
{
    case Analysis = 'analysis';
    case ImplementationBackend = 'implementation:backend';
    case ImplementationFrontend = 'implementation:frontend';
    case ImplementationInfra = 'implementation:infra';

    public function label(): string
    {
        return match ($this) {
            self::Analysis => 'Análise',
            self::ImplementationBackend => 'Implementação Backend',
            self::ImplementationFrontend => 'Implementação Frontend',
            self::ImplementationInfra => 'Implementação Infra',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Analysis => 'bg-sky-100 text-sky-800',
            self::ImplementationBackend => 'bg-emerald-100 text-emerald-800',
            self::ImplementationFrontend => 'bg-amber-100 text-amber-800',
            self::ImplementationInfra => 'bg-slate-200 text-slate-800',
        };
    }
}
