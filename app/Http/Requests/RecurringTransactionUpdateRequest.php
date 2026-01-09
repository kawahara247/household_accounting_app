<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\FlowType;
use App\Enums\PayerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RecurringTransactionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Enum>>
     */
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:28'],
            'type'         => ['required', new Enum(FlowType::class)],
            'category_id'  => ['required', 'exists:categories,id'],
            'payer'        => ['required', new Enum(PayerType::class)],
            'amount'       => ['required', 'integer', 'min:1'],
            'memo'         => ['nullable', 'string', 'max:255'],
        ];
    }
}
