<?php

namespace Xypp\Collector\Integration\Conditions;

use Flarum\User\User;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\LocalizeDate\Helper\CarbonZoneHelper;

class AccountAge extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    public bool $accumulateUpdate = true;

    public function __construct(protected CarbonZoneHelper $carbonZoneHelper)
    {
        parent::__construct('account_age', null, 'xypp-collector.ref.integration.condition.account_age');
    }

    public function getAbsoluteValue(User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $days = $user->joined_at
            ? max(0, $user->joined_at->copy()->startOfDay()->diffInDays($this->carbonZoneHelper->now()->startOfDay(), false))
            : 0;

        $conditionAccumulation->resetTotal($days);

        return true;
    }

    public function updateValue(User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $days = $user->joined_at
            ? max(0, $user->joined_at->copy()->startOfDay()->diffInDays($this->carbonZoneHelper->now()->startOfDay(), false))
            : 0;

        if ($conditionAccumulation->total === $days) {
            return false;
        }

        $conditionAccumulation->resetTotal($days);

        return true;
    }
}
