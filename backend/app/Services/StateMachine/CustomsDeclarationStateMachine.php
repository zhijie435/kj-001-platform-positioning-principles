<?php

namespace App\Services\StateMachine;

use App\Contracts\StateMachine\StateMachineInterface;
use App\Contracts\StateMachine\TransitionResult;
use App\Enums\CustomsDeclarationStatus;
use App\Models\CustomsDeclaration;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CustomsDeclarationStateMachine implements StateMachineInterface
{
    public function __construct(
        protected CustomsDeclaration $declaration,
    ) {
    }

    public function getModel(): Model
    {
        return $this->declaration;
    }

    public function currentState(): BackedEnum
    {
        return CustomsDeclarationStatus::from($this->declaration->status);
    }

    public function canTransitionTo(BackedEnum $targetState, array $context = []): bool
    {
        if (!$targetState instanceof CustomsDeclarationStatus) {
            return false;
        }

        return $this->currentState()->canTransitionTo($targetState);
    }

    public function validateTransition(BackedEnum $targetState, array $context = []): TransitionResult
    {
        if (!$targetState instanceof CustomsDeclarationStatus) {
            return TransitionResult::failure('无效的报关状态类型');
        }

        $current = $this->currentState();

        if ($current === $targetState) {
            return TransitionResult::success('状态未变更');
        }

        if ($current->isTerminal()) {
            return TransitionResult::failure("报关已处于终态（{$current->label()}），无法变更状态");
        }

        if (!$this->canTransitionTo($targetState, $context)) {
            return TransitionResult::failure(
                "不允许从「{$current->label()}」变更为「{$targetState->label()}」"
            );
        }

        return TransitionResult::success();
    }

    public function transitionTo(BackedEnum $targetState, array $context = []): Model
    {
        if (!$targetState instanceof CustomsDeclarationStatus) {
            throw new \InvalidArgumentException('无效的报关状态类型');
        }

        $validation = $this->validateTransition($targetState, $context);

        if ($validation->isInvalid()) {
            throw new \DomainException($validation->message);
        }

        return DB::transaction(function () use ($targetState) {
            $timestampField = $targetState->timestampField();
            $update = ['status' => $targetState->value];

            if ($timestampField && !$this->declaration->$timestampField) {
                $update[$timestampField] = now()->toDateString();
            }

            $this->declaration->update($update);

            return $this->declaration->fresh();
        });
    }

    public function allowedTransitions(): array
    {
        return [];
    }
}
