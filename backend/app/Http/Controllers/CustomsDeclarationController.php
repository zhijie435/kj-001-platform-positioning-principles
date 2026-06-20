<?php

namespace App\Http\Controllers;

use App\Enums\CustomsDeclarationStatus;
use App\Exceptions\BusinessException;
use App\Exceptions\StateTransitionException;
use App\Models\CustomsDeclaration;
use App\Repositories\CustomsDeclarationRepository;
use Illuminate\Http\Request;

class CustomsDeclarationController extends Controller
{
    public function __construct(
        protected CustomsDeclarationRepository $declarationRepository,
    ) {
    }

    public function index(Request $request)
    {
        $with = ['order', 'shipment'];
        $paginator = $this->declarationRepository->listForUser($request->user(), $request, $with);

        return response()->json($paginator);
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
        $this->declarationRepository->findForUserOrFail(
            $request->user(),
            $customsDeclaration->id,
            ['order', 'shipment', 'items.product']
        );

        return response()->json(
            $customsDeclaration->load(['order', 'shipment', 'items.product'])
        );
    }

    public function update(Request $request, CustomsDeclaration $customsDeclaration)
    {
        $this->declarationRepository->findForUserOrFail($request->user(), $customsDeclaration->id);

        if ($customsDeclaration->getStatusEnum()->isTerminal()) {
            throw new StateTransitionException("报关已处于终态（{$customsDeclaration->getStatusEnum()->label()}），无法修改");
        }

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
        $this->declarationRepository->findForUserOrFail($request->user(), $customsDeclaration->id);

        if ($customsDeclaration->getStatusEnum()->isTerminal() && !$request->user()->isPlatform()) {
            throw new BusinessException('已放行的报关单仅允许平台管理员删除');
        }

        $customsDeclaration->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function updateStatus(Request $request, CustomsDeclaration $customsDeclaration)
    {
        $this->declarationRepository->findForUserOrFail($request->user(), $customsDeclaration->id);

        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $targetStatus = CustomsDeclarationStatus::tryFrom($validated['status']);

        if (!$targetStatus) {
            throw BusinessException::withCode(
                '无效的报关状态值',
                'INVALID_DECLARATION_STATUS',
                [
                    'allowed' => array_column(CustomsDeclarationStatus::cases(), 'value'),
                ]
            );
        }

        try {
            $stateMachine = new \App\Services\StateMachine\CustomsDeclarationStateMachine($customsDeclaration);
            $updated = $stateMachine->transitionTo($targetStatus);
        } catch (\DomainException $e) {
            throw new StateTransitionException($e->getMessage());
        }

        return response()->json($updated);
    }
}
