<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 支払者種別を表すEnum
 *
 * 家計簿の支払者（PersonA/PersonB）を定義する。
 * 表示名は config/payers.php で設定可能。
 */
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
