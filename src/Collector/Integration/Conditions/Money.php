<?php

namespace Xypp\Collector\Integration\Conditions;

use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\RewardDefinition;

class Money extends ConditionDefinition
{
    public bool $accumulateAbsolute = false;
    public function __construct()
    {
        parent::__construct("money", null, "xypp-collector.ref.integration.condition.money");
    }
    public function getAbsoluteValue(\Flarum\User\User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $conditionAccumulation->resetTotal($user->money);
        return true;
    }
}