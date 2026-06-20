<?php

namespace App\Http\Controllers;

use App\Models\CustomsDeclaration;
use Illuminate\Http\Request;

class CustomsDeclarationController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomsDeclaration::visibleTo($request->user())
            ->with(['order', 'shipment']);

        $this->applySearch($query, $request, ['declaration_no', 'declarant', 'customs_broker']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->integer('order_id'));
        }

        if ($request->filled('shipment_id')) {
            $query->where('shipment_id', $request->integer('shipment_id'));
        }

        return response()->json(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'declaration_no' => 'required|string|max:100|unique:customs_declarations,declaration_no',
            'shipment_id' => 'nullable|exists:shipments,id',
            'order_id' => 'nullable|exists:orders,id',
            'type' => 'sometimes|in:import,export,transit',
            'status' => 'sometimes|in:pending,declared,inspecting,released,rejected,appealing',
            'declarant' => 'nullable|string|max:255',
            'declaration_date' => 'nullable|date',
            'release_date' => 'nullable|date',
            'hs_code_summary' => 'nullable|string',
            'declared_value' => 'nullable|decimal:0,2',
            'currency' => 'nullable|string|max:10',
            'tax_amount' => 'nullable|decimal:0,2',
            'duty_amount' => 'nullable|decimal:0,2',
            'vat_amount' => 'nullable|decimal:0,2',
            'total_fee' => 'nullable|decimal:0,2',
            'customs_broker' => 'nullable|string|max:255',
            'documents' => 'nullable|array',
            'remark' => 'nullable|string',
        ]);

        $declaration = CustomsDeclaration::create($validated);

        return response()->json($declaration->load(['order', 'shipment', 'items']));
    }

    public function show(Request $request, CustomsDeclaration $customsDeclaration)
    {
        CustomsDeclaration::visibleTo($request->user())->where('id', $customsDeclaration->id)->firstOrFail();

        return response()->json(
            $customsDeclaration->load(['order', 'shipment', 'items.product'])
        );
    }

    public function update(Request $request, CustomsDeclaration $customsDeclaration)
    {
        CustomsDeclaration::visibleTo($request->user())->where('id', $customsDeclaration->id)->firstOrFail();

        $validated = $request->validate([
            'declaration_no' => 'sometimes|string|max:100|unique:customs_declarations,declaration_no,' . $customsDeclaration->id,
            'shipment_id' => 'nullable|exists:shipments,id',
            'order_id' => 'nullable|exists:orders,id',
            'type' => 'sometimes|in:import,export,transit',
            'status' => 'sometimes|in:pending,declared,inspecting,released,rejected,appealing',
            'declarant' => 'nullable|string|max:255',
            'declaration_date' => 'nullable|date',
            'release_date' => 'nullable|date',
            'hs_code_summary' => 'nullable|string',
            'declared_value' => 'nullable|decimal:0,2',
            'currency' => 'nullable|string|max:10',
            'tax_amount' => 'nullable|decimal:0,2',
            'duty_amount' => 'nullable|decimal:0,2',
            'vat_amount' => 'nullable|decimal:0,2',
            'total_fee' => 'nullable|decimal:0,2',
            'customs_broker' => 'nullable|string|max:255',
            'documents' => 'nullable|array',
            'remark' => 'nullable|string',
        ]);

        $customsDeclaration->update($validated);

        return response()->json($customsDeclaration->load(['order', 'shipment', 'items']));
    }

    public function destroy(Request $request, CustomsDeclaration $customsDeclaration)
    {
        CustomsDeclaration::visibleTo($request->user())->where('id', $customsDeclaration->id)->firstOrFail();

        $customsDeclaration->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function updateStatus(Request $request, CustomsDeclaration $customsDeclaration)
    {
        CustomsDeclaration::visibleTo($request->user())->where('id', $customsDeclaration->id)->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:pending,declared,inspecting,released,rejected,appealing',
        ]);

        $customsDeclaration->update($validated);

        return response()->json($customsDeclaration);
    }
}
