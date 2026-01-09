<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FlowType;
use App\Enums\PayerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property int $day_of_month
 * @property FlowType $type
 * @property int $category_id
 * @property PayerType $payer
 * @property int $amount
 * @property string|null $memo
 * @property bool $is_active
 */
class RecurringTransaction extends Model
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
            'type'      => FlowType::class,
            'payer'     => PayerType::class,
            'is_active' => 'boolean',
        ];
    }
}
