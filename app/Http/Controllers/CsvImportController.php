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
        ]);
    }

    public function preview(CsvImportPreviewRequest $request): Response
    {
        $filePath    = $request->file('csv_file')->getRealPath();
        $previewRows = $this->csvImportService->parseCreditCardCsv($filePath);

        return Inertia::render('Transactions/ImportCsv', [
            'previewRows' => $previewRows,
        ]);
    }

    public function store(CsvImportStoreRequest $request): RedirectResponse
    {
        // カテゴリは「個人の出費」、支払元は（PersonA）で固定
        $category = Category::where('name', '個人の出費')->firstOrFail();

        $date = $request->input('date');

        foreach ($request->input('transactions') as $row) {
            Transaction::create([
                'date'        => $date,
                'type'        => FlowType::Expense,
                'category_id' => $category->id,
                'payer'       => PayerType::PersonA,
                'amount'      => $row['amount'],
                'memo'        => $row['memo'] ?? null,
            ]);
        }

        return Redirect::route('transactions.index');
    }
}
