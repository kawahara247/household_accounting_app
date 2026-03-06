<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CsvImportStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'date'                  => ['required', 'date'],
            'transactions'          => ['required', 'array', 'min:1'],
            'transactions.*.memo'   => ['nullable', 'string', 'max:255'],
            'transactions.*.amount' => ['required', 'integer', 'min:1'],
        ];
    }
}
