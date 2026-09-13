<?php

namespace Xypp\Collector\Integration\Conditions;

use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\RewardDefinition;

class StoreItemPurchase extends ConditionDefinition
{
    public bool $accumulateAbsolute = false;
    public function __construct()
    {
        parent::__construct("store_purchased", null, "xypp-collector.ref.integration.condition.store_purchased");

    }
    public function getAbsoluteValue(\Flarum\User\User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $count = \Xypp\Store\PurchaseHistory::where("user_id", $user->id)->count();

        $conditionAccumulation->resetTotal($count);
        return true;
    }
}