<?php

namespace App\Services\StateMachine;

use App\Contracts\StateMachine\StateMachineInterface;
use App\Contracts\StateMachine\TransitionResult;
use App\Enums\OrderStatus;
use App\Models\Order;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderStateMachine implements StateMachineInterface
{
    public function __construct(
        protected Order $order,
    ) {
    }

    public function getModel(): Model
    {
        return $this->order;
    }

    public function currentState(): BackedEnum
    {
        return OrderStatus::from($this->order->status);
    }

    public function canTransitionTo(BackedEnum $targetState, array $context = []): bool
    {
        if (!$targetState instanceof OrderStatus) {
            return false;
        }

        return $this->currentState()->canTransitionTo($targetState);
    }

    public function validateTransition(BackedEnum $targetState, array $context = []): TransitionResult
    {
        if (!$targetState instanceof OrderStatus) {
            return TransitionResult::failure('无效的订单状态类型');
        }

        $current = $this->currentState();

        if ($current === $targetState) {
            return TransitionResult::success('状态未变更');
        }

        if ($current->isTerminal()) {
            return TransitionResult::failure("订单已处于终态（{$current->label()}），无法变更状态");
        }

        if (!$this->canTransitionTo($targetState, $context)) {
            $allowed = array_map(fn (OrderStatus $s) => $s->label(), $current->allowedTransitions());
            $allowedStr = $allowed ? implode('、', $allowed) : '无';

            return TransitionResult::failure(
                "不允许从「{$current->label()}」变更为「{$targetState->label()}」，允许的目标状态：{$allowedStr}"
            );
        }

        return $this->validateBusinessRules($targetState, $context);
    }

    protected function validateBusinessRules(OrderStatus $targetState, array $context): TransitionResult
    {
        switch ($targetState) {
            case OrderStatus::SHIPPED:
                if ($this->order->shipments()->count() === 0) {
                    return TransitionResult::failure('订单发货前需至少创建一条发货记录');
                }
                break;

            case OrderStatus::COMPLETED:
                if ($this->order->isUnpaid() || $this->order->isPartialPaid()) {
                    return TransitionResult::failure('订单完成前需结清所有款项');
                }
                break;

            case OrderStatus::CANCELLED:
                if ($this->order->payments()->where('type', 'escrow_deposit')->exists()) {
                    return TransitionResult::failure('订单已产生付款记录，请先处理退款后再取消');
                }
                break;
        }

        return TransitionResult::success();
    }

    public function transitionTo(BackedEnum $targetState, array $context = []): Model
    {
        if (!$targetState instanceof OrderStatus) {
            throw new \InvalidArgumentException('无效的订单状态类型');
        }

        $validation = $this->validateTransition($targetState, $context);

        if ($validation->isInvalid()) {
            throw new \DomainException($validation->message);
        }

        return DB::transaction(function () use ($targetState, $context) {
            $timestampField = $targetState->timestampField();

            if ($timestampField && !$this->order->$timestampField) {
                $this->order->$timestampField = now();
            }

            $this->order->status = $targetState->value;

            if (isset($context['remark'])) {
                $this->order->remark = $context['remark'] ?? $this->order->remark;
            }

            $this->order->save();

            return $this->order;
        });
    }

    public function allowedTransitions(): array
    {
        return $this->currentState()->allowedTransitions();
    }
}
