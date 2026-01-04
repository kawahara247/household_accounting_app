<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PayerType;
use App\Models\Category;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * @param DashboardService $dashboardService ダッシュボード用のビジネスロジックを提供するサービス
     */
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {
    }

    /**
     * ダッシュボード画面を表示する.
     *
     * 指定された年月（デフォルトは現在月）のカレンダービューを表示し、
     * 日ごとの収支合計、月間の収支合計、カテゴリ一覧、支払元一覧を提供する。
     *
     * @param Request $request HTTPリクエスト（year, monthクエリパラメータを含む場合がある）
     *
     * @return Response Inertiaレスポンス
     */
    public function index(Request $request): Response
    {
        $now   = Carbon::now();
        $year  = (int) $request->query('year', $now->year);
        $month = (int) $request->query('month', $now->month);

        $transactions   = $this->dashboardService->getMonthlyTransactions($year, $month);
        $dailyBalances  = $this->dashboardService->calculateDailyBalances($transactions);
        $monthlyBalance = $this->dashboardService->calculateBalance($transactions);

        $categories = Category::all();
        $payers     = collect(PayerType::cases())->map(fn (PayerType $payer) => [
            'value' => $payer->value,
            'label' => $payer->label(),
        ]);

        return Inertia::render('Dashboard', [
            'year'           => $year,
            'month'          => $month,
            'dailyBalances'  => $dailyBalances,
            'monthlyBalance' => $monthlyBalance,
            'categories'     => $categories,
            'payers'         => $payers,
        ]);
    }

    /**
     * 指定された日付の取引一覧をJSON形式で返す.
     *
     * カレンダーの日付クリック時に呼び出され、その日の取引リストを取得する。
     *
     * @param Request $request HTTPリクエスト（dateクエリパラメータを含む）
     *
     * @return JsonResponse 取引データを含むJSONレスポンス
     */
    public function transactions(Request $request): JsonResponse
    {
        $date = Carbon::parse($request->query('date'));

        $transactions = $this->dashboardService->getTransactionsByDate($date);

        return response()->json([
            'transactions' => $transactions,
        ]);
    }
}
