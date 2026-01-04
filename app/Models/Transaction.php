<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FlowType;
use App\Enums\PayerType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property Carbon $date
 * @property FlowType $type
 * @property int $category_id
 * @property PayerType $payer
 * @property int $amount
 * @property string|null $memo
 */
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
