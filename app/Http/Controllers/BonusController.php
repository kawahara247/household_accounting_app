<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PayerType;
use App\Http\Requests\BonusStoreRequest;
use App\Http\Requests\BonusUpdateRequest;
use App\Models\Bonus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class BonusController extends Controller
{
    public function index(): Response
    {
        $bonuses = Bonus::query()
            ->orderBy('year_month', 'desc')
            ->orderBy('payer')
            ->get();

        $payers = collect(PayerType::cases())->map(fn (PayerType $payer) => [
            'value' => $payer->value,
            'label' => $payer->label(),
        ]);

        return Inertia::render('Bonuses/Index', [
            'bonuses' => $bonuses,
            'payers'  => $payers,
        ]);
    }

    public function store(BonusStoreRequest $request): RedirectResponse
    {
        Bonus::create($request->validated());

        return Redirect::route('bonuses.index');
    }

    public function update(BonusUpdateRequest $request, Bonus $bonus): RedirectResponse
    {
        $bonus->update($request->validated());

        return Redirect::route('bonuses.index');
    }

    public function destroy(Bonus $bonus): RedirectResponse
    {
        $bonus->delete();

        return Redirect::route('bonuses.index');
    }
}
