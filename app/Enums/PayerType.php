<?php

declare(strict_types=1);

namespace App\Enums;

enum PayerType: string
{
    case PersonA = 'person_a';
    case PersonB = 'person_b';

    public function label(): string
    {
        return match ($this) {
            self::PersonA => config('payers.person_a'),
            self::PersonB => config('payers.person_b'),
        };
    }
}
