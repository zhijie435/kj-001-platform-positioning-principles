<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Exceptions\BusinessException;
use App\Exceptions\ForbiddenException;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentRepository $paymentRepository,
        protected PermissionService $permissionService,
    ) {
        $this->middleware('permission:payment.view')->only(['index', 'show']);
        $this->middleware('permission:payment.create')->only(['store']);
        $this->middleware('permission:payment.delete')->only(['destroy']);
        $this->middleware('permission:payment.settle')->only(['settle']);
        $this->middleware('permission:payment.refund')->only(['refund']);
    }

    public function index(Request $request)
    {
        $with = ['order:id,order_no,total,payment_status', 'creator:id,name'];
        $paginator = $this->paymentRepository->listForUser($request->user(), $request, $with);

        return PaymentResource::collection($paginator);
    }

    public function store(PaymentRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $paymentType = PaymentType::tryFrom($data['type']);

        if (!$paymentType) {
            throw BusinessException::withCode(
                '无效的付款类型',
                'INVALID_PAYMENT_TYPE',
                [
                    'allowed' => array_column(PaymentType::cases(), 'value'),
                ]
            );
        }

        $this->permissionService->forUser($user)->ensureCanCreatePayment($paymentType);

        $payment = Payment::create([
            'payment_no' => 'PAY'.date('YmdHis').str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            'order_id' => $data['order_id'],
            'created_by' => $user->id,
            'type' => $paymentType->value,
            'method' => $data['method'],
            'amount' => $data['amount'],
            'fee_amount' => $data['fee_amount'] ?? 0,
            'currency' => $data['currency'] ?? 'CNY',
            'payment_date' => $data['payment_date'],
            'transaction_no' => $data['transaction_no'] ?? null,
            'status' => ($data['status'] ?? PaymentStatus::COMPLETED->value),
            'remark' => $data['remark'] ?? null,
        ]);

        return new PaymentResource($payment->load(['order', 'creator']));
    }

    public function show(Request $request, Payment $payment)
    {
        $this->paymentRepository->findForUserOrFail($request->user(), $payment->id, ['order', 'creator']);

        return new PaymentResource($payment->load(['order', 'creator']));
    }

    public function destroy(Request $request, Payment $payment)
    {
        $this->paymentRepository->findForUserOrFail($request->user(), $payment->id);

        if ($payment->isCompleted() && !$request->user()->isPlatform()) {
            throw new ForbiddenException('已完成的付款记录仅允许平台管理员删除');
        }

        $payment->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function settle(Request $request, Payment $payment)
    {
        $user = $request->user();
        $this->paymentRepository->findForUserOrFail($user, $payment->id);

        $this->permissionService->forUser($user)->ensureCanSettlePayment($payment);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_no' => ['nullable', 'string', 'max:100'],
            'remark' => ['nullable', 'string'],
        ]);

        if ($validated['amount'] > (float) $payment->amount) {
            throw BusinessException::withCode(
                '结算金额不能大于原始托管金额',
                'SETTLE_AMOUNT_EXCEEDED'
            );
        }

        $release = Payment::create([
            'payment_no' => 'STL' . date('YmdHis') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            'order_id' => $payment->order_id,
            'created_by' => $user->id,
            'type' => PaymentType::ESCROW_RELEASE->value,
            'method' => $payment->method,
            'amount' => $validated['amount'],
            'fee_amount' => 0,
            'currency' => $payment->currency,
            'payment_date' => now()->toDateString(),
            'transaction_no' => $validated['transaction_no'] ?? null,
            'status' => PaymentStatus::COMPLETED->value,
            'remark' => $validated['remark'] ?? '平台结算给供应商',
        ]);

        return new PaymentResource($release->load(['order', 'creator']));
    }

    public function refund(Request $request, Payment $payment)
    {
        $user = $request->user();
        $this->paymentRepository->findForUserOrFail($user, $payment->id);

        $this->permissionService->forUser($user)->ensureCanRefundPayment($payment);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_no' => ['nullable', 'string', 'max:100'],
            'remark' => ['nullable', 'string'],
        ]);

        if ($validated['amount'] > (float) $payment->amount) {
            throw BusinessException::withCode(
                '退款金额不能大于原始付款金额',
                'REFUND_AMOUNT_EXCEEDED'
            );
        }

        $refund = Payment::create([
            'payment_no' => 'RFD' . date('YmdHis') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            'order_id' => $payment->order_id,
            'created_by' => $user->id,
            'type' => PaymentType::REFUND->value,
            'method' => $payment->method,
            'amount' => $validated['amount'],
            'fee_amount' => 0,
            'currency' => $payment->currency,
            'payment_date' => now()->toDateString(),
            'transaction_no' => $validated['transaction_no'] ?? null,
            'status' => PaymentStatus::COMPLETED->value,
            'remark' => $validated['remark'] ?? '平台退款给分销商',
        ]);

        return new PaymentResource($refund->load(['order', 'creator']));
    }
}
