<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Http\Requests\CsvImportPreviewRequest;
use App\Http\Requests\CsvImportStoreRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\CsvImportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CsvImportController extends Controller
{
    public function __construct(private readonly CsvImportService $csvImportService)
    {
    }

    public function create(): Response
    {
        return Inertia::render('Transactions/ImportCsv', [
            'previewRows' => null,
            'categories'  => $this->expenseCategories(),
        ]);
    }

    public function preview(CsvImportPreviewRequest $request): Response
    {
        $filePath    = $request->file('csv_file')->getRealPath();
        $previewRows = $this->csvImportService->parseCreditCardCsv($filePath);

        $categories = $this->expenseCategories();
        $default    = $categories->first(fn (Category $c): bool => $c->name === '個人の出費')
            ?? $categories->first();
        $defaultCategoryId = $default?->id;

        $previewRows = array_map(
            fn (array $row): array => [...$row, 'category_id' => $defaultCategoryId],
            $previewRows,
        );

        return Inertia::render('Transactions/ImportCsv', [
            'previewRows' => $previewRows,
            'categories'  => $categories,
        ]);
    }

    public function store(CsvImportStoreRequest $request): RedirectResponse
    {
        $date = $request->input('date');

        foreach ($request->input('transactions') as $row) {
            Transaction::create([
                'date'        => $date,
                'type'        => FlowType::Expense,
                'category_id' => $row['category_id'],
                'payer'       => PayerType::PersonA,
                'amount'      => $row['amount'],
                'memo'        => $row['memo'] ?? null,
            ]);
        }

        return Redirect::route('transactions.index');
    }

    /**
     * @return Collection<int, Category>
     */
    private function expenseCategories(): Collection
    {
        return Category::byType(FlowType::Expense)
            ->orderBy('id')
            ->get(['id', 'name']);
    }
}
