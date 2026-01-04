<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 収支種別を表すEnum
 *
 * 収入（Income）と支出（Expense）の2種類を定義する。
 */
enum FlowType: string
{
    case Income  = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income  => '収入',
            self::Expense => '支出',
        };
    }
}
