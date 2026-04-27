<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayerType;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $year_month
 * @property PayerType $payer
 * @property int $amount
 */
class Bonus extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payer' => PayerType::class,
        ];
    }
}
