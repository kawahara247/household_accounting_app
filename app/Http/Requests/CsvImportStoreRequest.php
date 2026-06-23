<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\FlowType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CsvImportStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date'                       => ['required', 'date'],
            'transactions'               => ['required', 'array', 'min:1'],
            'transactions.*.memo'        => ['nullable', 'string', 'max:255'],
            'transactions.*.amount'      => ['required', 'integer', 'min:1'],
            'transactions.*.category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('type', FlowType::Expense->value),
            ],
        ];
    }
}
