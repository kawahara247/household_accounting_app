<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FlowType;
use App\Enums\PayerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date'  => 'date',
            'type'  => FlowType::class,
            'payer' => PayerType::class,
        ];
    }
}
