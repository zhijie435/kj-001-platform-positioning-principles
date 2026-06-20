<?php

namespace App\Services\StateMachine;

use App\Contracts\StateMachine\StateMachineInterface;
use App\Contracts\StateMachine\TransitionResult;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ShipmentStateMachine implements StateMachineInterface
{
    public function __construct(
        protected Shipment $shipment,
    ) {
    }

    public function getModel(): Model
    {
        return $this->shipment;
    }

    public function currentState(): BackedEnum
    {
        return ShipmentStatus::from($this->shipment->status);
    }

    public function canTransitionTo(BackedEnum $targetState, array $context = []): bool
    {
        if (!$targetState instanceof ShipmentStatus) {
            return false;
        }

        return $this->currentState()->canTransitionTo($targetState, $context);
    }

    public function validateTransition(BackedEnum $targetState, array $context = []): TransitionResult
    {
        if (!$targetState instanceof ShipmentStatus) {
            return TransitionResult::failure('无效的物流状态类型');
        }

        $current = $this->currentState();

        if ($current === $targetState) {
            return TransitionResult::success('状态未变更');
        }

        if ($current->isTerminal()) {
            return TransitionResult::failure("物流已处于终态（{$current->label()}），无法变更状态");
        }

        if (!$this->canTransitionTo($targetState, $context)) {
            return TransitionResult::failure(
                "不允许从「{$current->label()}」变更为「{$targetState->label()}」"
            );
        }

        return $this->validateMarketRules($targetState, $context);
    }

    protected function validateMarketRules(ShipmentStatus $targetState, array $context): TransitionResult
    {
        $marketCode = $this->shipment->destinationMarket?->country_code;
        $rules = $this->resolveMarketRules($marketCode);

        if ($targetState === ShipmentStatus::IN_TRANSIT && $rules['require_release_before_transit']) {
            $hasReleasedDeclaration = $this->shipment->declarations()
                ->where('status', 'released')
                ->exists();

            if (!$hasReleasedDeclaration) {
                return TransitionResult::failure(
                    '当前市场（巴西）要求报关单放行后物流才能继续运输'
                );
            }
        }

        return TransitionResult::success();
    }

    protected function resolveMarketRules(?string $marketCode): array
    {
        $common = [
            'require_release_before_transit' => false,
            'auto_fail_on_rejected' => false,
            'advance_to_out_for_delivery_on_release' => false,
        ];

        return match ($marketCode) {
            'BR' => array_merge($common, [
                'require_release_before_transit' => true,
                'auto_fail_on_rejected' => true,
                'advance_to_out_for_delivery_on_release' => true,
            ]),
            'US' => array_merge($common, [
                'require_release_before_transit' => false,
                'auto_fail_on_rejected' => false,
                'advance_to_out_for_delivery_on_release' => true,
            ]),
            default => $common,
        };
    }

    public function transitionTo(BackedEnum $targetState, array $context = []): Model
    {
        if (!$targetState instanceof ShipmentStatus) {
            throw new \InvalidArgumentException('无效的物流状态类型');
        }

        $validation = $this->validateTransition($targetState, $context);

        if ($validation->isInvalid()) {
            throw new \DomainException($validation->message);
        }

        return DB::transaction(function () use ($targetState, $context) {
            $timestampField = $targetState->timestampField();
            $update = ['status' => $targetState->value];

            if ($timestampField && !$this->shipment->$timestampField) {
                $update[$timestampField] = now();
            }

            $this->shipment->addTrackingEvent(
                $targetState->value,
                $context['location'] ?? '',
                $context['description'] ?? ''
            );

            $this->shipment->update($update);

            return $this->shipment->fresh();
        });
    }

    public function allowedTransitions(): array
    {
        $current = $this->currentState();

        if ($current->isTerminal()) {
            return [];
        }

        return $current->allowedTransitions ?? [];
    }
}
