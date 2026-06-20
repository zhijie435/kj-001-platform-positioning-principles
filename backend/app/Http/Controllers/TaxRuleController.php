<?php

namespace App\Http\Controllers;

use App\Models\TaxRule;
use Illuminate\Http\Request;

class TaxRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxRule::with(['market', 'category']);

        if ($request->filled('is_active')) {
            $query->where('is_active', $this->boolean($request, 'is_active'));
        }

        if ($request->filled('market_id')) {
            $query->where('market_id', $request->integer('market_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return response()->json(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'market_id' => 'required|exists:markets,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:vat,gst,sales_tax,duty,ipi,icms,pis,cofins,other',
            'name' => 'required|string|max:255',
            'rate' => 'required|decimal:0,4',
            'min_amount' => 'nullable|decimal:0,2',
            'max_amount' => 'nullable|decimal:0,2',
            'is_compound' => 'nullable|boolean',
            'compound_rules' => 'nullable|array',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'is_active' => 'nullable|boolean',
            'remark' => 'nullable|string',
        ]);

        $rule = TaxRule::create($validated);

        return response()->json($rule->load(['market', 'category']));
    }

    public function show(Request $request, TaxRule $taxRule)
    {
        return response()->json($taxRule->load(['market', 'category']));
    }

    public function update(Request $request, TaxRule $taxRule)
    {
        $validated = $request->validate([
            'market_id' => 'sometimes|exists:markets,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'sometimes|in:vat,gst,sales_tax,duty,ipi,icms,pis,cofins,other',
            'name' => 'sometimes|string|max:255',
            'rate' => 'sometimes|decimal:0,4',
            'min_amount' => 'nullable|decimal:0,2',
            'max_amount' => 'nullable|decimal:0,2',
            'is_compound' => 'nullable|boolean',
            'compound_rules' => 'nullable|array',
            'effective_date' => 'sometimes|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'is_active' => 'nullable|boolean',
            'remark' => 'nullable|string',
        ]);

        $taxRule->update($validated);

        return response()->json($taxRule->load(['market', 'category']));
    }

    public function destroy(Request $request, TaxRule $taxRule)
    {
        $taxRule->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'market_id' => 'required|exists:markets,id',
            'category_id' => 'nullable|exists:categories,id',
            'amount' => 'required|decimal:0,2',
            'type' => 'nullable|in:vat,gst,sales_tax,duty,ipi,icms,pis,cofins,other',
        ]);

        $query = TaxRule::where('market_id', $validated['market_id'])->active();

        if (!empty($validated['category_id'])) {
            $query->byCategory($validated['category_id']);
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $rules = $query->get();
        $totalTax = 0;
        $details = [];

        foreach ($rules as $rule) {
            $tax = $rule->calculateTax((float) $validated['amount']);
            $totalTax += $tax;
            $details[] = [
                'id' => $rule->id,
                'name' => $rule->name,
                'type' => $rule->type,
                'rate' => $rule->rate,
                'tax_amount' => $tax,
            ];
        }

        return response()->json([
            'amount' => (float) $validated['amount'],
            'total_tax' => round($totalTax, 2),
            'total_with_tax' => round((float) $validated['amount'] + $totalTax, 2),
            'details' => $details,
        ]);
    }
}
