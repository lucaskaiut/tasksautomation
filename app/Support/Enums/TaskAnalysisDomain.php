<?php

namespace App\Support\Enums;

enum TaskAnalysisDomain: string
{
    case Backend = 'backend';
    case Frontend = 'frontend';
    case Infra = 'infra';

    public function label(): string
    {
        return match ($this) {
            self::Backend => 'Backend',
            self::Frontend => 'Frontend',
            self::Infra => 'Infra',
        };
    }
}
