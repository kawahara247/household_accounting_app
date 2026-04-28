<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PayerType;
use App\Models\Bonus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Stringable;

class BonusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Enum|Stringable|ValidationRule|string>|string>
     */
    public function rules(): array
    {
        /** @var Bonus $bonus */
        $bonus = $this->route('bonus');

        return [
            'year_month' => [
                'required',
                'date_format:Y-m',
                Rule::unique('bonuses', 'year_month')
                    ->ignore($bonus->id)
                    ->where(fn (Builder $query): Builder => $query->where('payer', $this->string('payer')->toString())),
            ],
            'payer'      => ['required', new Enum(PayerType::class)],
            'amount'     => ['required', 'integer', 'min:1'],
        ];
    }
}
