<?php

namespace App\Support\Enums;

enum TaskImplementationType: string
{
    case Feature = 'feature';
    case Fix = 'fix';
}
