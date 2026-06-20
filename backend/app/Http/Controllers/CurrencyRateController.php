<?php

namespace App\Http\Controllers;

use App\Models\CurrencyRate;
use Illuminate\Http\Request;

class CurrencyRateController extends Controller
{
    public function index(Request $request)
    {
        $query = CurrencyRate::query();

        if ($request->filled('base_currency')) {
            $query->where('base_currency', $request->string('base_currency'));
        }

        if ($request->filled('target_currency')) {
            $query->where('target_currency', $request->string('target_currency'));
        }

        if ($request->filled('active_only', false)) {
            $query->active();
        }

        return response()->json(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'base_currency' => 'required|string|max:10',
            'target_currency' => 'required|string|max:10',
            'rate' => 'required|decimal:0,6',
            'buy_rate' => 'nullable|decimal:0,6',
            'sell_rate' => 'nullable|decimal:0,6',
            'source' => 'nullable|string|max:100',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'remark' => 'nullable|string',
        ]);

        $rate = CurrencyRate::create($validated);

        return response()->json($rate);
    }

    public function show(Request $request, CurrencyRate $currencyRate)
    {
        return response()->json($currencyRate);
    }

    public function update(Request $request, CurrencyRate $currencyRate)
    {
        $validated = $request->validate([
            'base_currency' => 'sometimes|string|max:10',
            'target_currency' => 'sometimes|string|max:10',
            'rate' => 'sometimes|decimal:0,6',
            'buy_rate' => 'nullable|decimal:0,6',
            'sell_rate' => 'nullable|decimal:0,6',
            'source' => 'nullable|string|max:100',
            'effective_date' => 'sometimes|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'remark' => 'nullable|string',
        ]);

        $currencyRate->update($validated);

        return response()->json($currencyRate);
    }

    public function destroy(Request $request, CurrencyRate $currencyRate)
    {
        $currencyRate->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function latest(Request $request)
    {
        $validated = $request->validate([
            'base_currency' => 'required|string|max:10',
            'target_currency' => 'required|string|max:10',
        ]);

        $rate = CurrencyRate::byPair(
            $validated['base_currency'],
            $validated['target_currency']
        )->active()->latest()->first();

        if (!$rate) {
            return response()->json(['message' => '未找到有效汇率'], 404);
        }

        return response()->json($rate);
    }

    public function convert(Request $request)
    {
        $validated = $request->validate([
            'base_currency' => 'required|string|max:10',
            'target_currency' => 'required|string|max:10',
            'amount' => 'required|decimal:0,2',
        ]);

        $rate = CurrencyRate::byPair(
            $validated['base_currency'],
            $validated['target_currency']
        )->active()->latest()->first();

        if (!$rate) {
            return response()->json(['message' => '未找到有效汇率'], 404);
        }

        return response()->json([
            'base_currency' => $validated['base_currency'],
            'target_currency' => $validated['target_currency'],
            'amount' => (float) $validated['amount'],
            'rate' => $rate->rate,
            'converted_amount' => $rate->convert((float) $validated['amount']),
        ]);
    }
}
