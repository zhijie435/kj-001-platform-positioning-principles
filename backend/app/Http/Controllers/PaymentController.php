<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payment.view')->only(['index', 'show']);
        $this->middleware('permission:payment.create')->only(['store']);
        $this->middleware('permission:payment.delete')->only(['destroy']);
        $this->middleware('permission:payment.settle')->only(['settle']);
        $this->middleware('permission:payment.refund')->only(['refund']);
    }

    public function index(Request $request)
    {
        $query = Payment::visibleTo($request->user())
            ->with(['order:id,order_no,total,payment_status', 'creator:id,name']);

        $this->applySearch($query, $request, ['payment_no', 'transaction_no']);

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->integer('order_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('method')) {
            $query->where('method', $request->string('method'));
        }

        return PaymentResource::collection(
            $query->latest()->paginate($this->perPage($request))
        );
    }

    public function store(PaymentRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $payment = Payment::create([
            'payment_no' => 'PAY'.date('YmdHis').str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            'order_id' => $data['order_id'],
            'created_by' => $user->id,
            'type' => $data['type'],
            'method' => $data['method'],
            'amount' => $data['amount'],
            'fee_amount' => $data['fee_amount'] ?? 0,
            'currency' => $data['currency'] ?? 'CNY',
            'payment_date' => $data['payment_date'],
            'transaction_no' => $data['transaction_no'] ?? null,
            'status' => $data['status'] ?? 'completed',
            'remark' => $data['remark'] ?? null,
        ]);

        return new PaymentResource($payment->load(['order', 'creator']));
    }

    public function show(Request $request, Payment $payment)
    {
        Payment::visibleTo($request->user())->where('id', $payment->id)->firstOrFail();

        return new PaymentResource($payment->load(['order', 'creator']));
    }

    public function destroy(Request $request, Payment $payment)
    {
        Payment::visibleTo($request->user())->where('id', $payment->id)->firstOrFail();

        $payment->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function settle(Request $request, Payment $payment)
    {
        Payment::visibleTo($request->user())->where('id', $payment->id)->firstOrFail();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_no' => ['nullable', 'string', 'max:100'],
            'remark' => ['nullable', 'string'],
        ]);

        $release = Payment::create([
            'payment_no' => 'STL' . date('YmdHis') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            'order_id' => $payment->order_id,
            'created_by' => $request->user()->id,
            'type' => 'escrow_release',
            'method' => $payment->method,
            'amount' => $validated['amount'],
            'fee_amount' => 0,
            'currency' => $payment->currency,
            'payment_date' => now()->toDateString(),
            'transaction_no' => $validated['transaction_no'] ?? null,
            'status' => 'completed',
            'remark' => $validated['remark'] ?? '平台结算给供应商',
        ]);

        return new PaymentResource($release->load(['order', 'creator']));
    }

    public function refund(Request $request, Payment $payment)
    {
        Payment::visibleTo($request->user())->where('id', $payment->id)->firstOrFail();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_no' => ['nullable', 'string', 'max:100'],
            'remark' => ['nullable', 'string'],
        ]);

        $refund = Payment::create([
            'payment_no' => 'RFD' . date('YmdHis') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            'order_id' => $payment->order_id,
            'created_by' => $request->user()->id,
            'type' => 'refund',
            'method' => $payment->method,
            'amount' => $validated['amount'],
            'fee_amount' => 0,
            'currency' => $payment->currency,
            'payment_date' => now()->toDateString(),
            'transaction_no' => $validated['transaction_no'] ?? null,
            'status' => 'completed',
            'remark' => $validated['remark'] ?? '平台退款给分销商',
        ]);

        return new PaymentResource($refund->load(['order', 'creator']));
    }
}
