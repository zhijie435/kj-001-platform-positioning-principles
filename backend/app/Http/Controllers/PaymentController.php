<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
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

        $payment = DB::transaction(function () use ($data, $user) {
            $payment = Payment::create([
                'payment_no' => 'PAY'.date('YmdHis').str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                'order_id' => $data['order_id'],
                'created_by' => $user->id,
                'type' => $data['type'],
                'method' => $data['method'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'CNY',
                'payment_date' => $data['payment_date'],
                'transaction_no' => $data['transaction_no'] ?? null,
                'remark' => $data['remark'] ?? null,
            ]);

            $order = Order::findOrFail($data['order_id']);
            $paidAmount = (float) $order->paid_amount + (float) $data['amount'];
            $total = (float) $order->total;

            $order->paid_amount = $paidAmount;

            if ($paidAmount >= $total) {
                $order->payment_status = 'paid';
            } elseif ($paidAmount > 0) {
                $order->payment_status = 'partial';
            }

            $order->save();

            return $payment;
        });

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
}
