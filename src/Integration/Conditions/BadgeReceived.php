<?php
namespace Xypp\Collector\Integration\Conditions;

use V17Development\FlarumUserBadges\UserBadge\UserBadge;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\RewardDefinition;

class BadgeReceived extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    public bool $accumulateUpdate = true;
    public bool $needManualUpdate = true;
    public function __construct()
    {
        parent::__construct("badge_received", null, "xypp-collector.ref.integration.condition.badge_received");
    }
    public function getAbsoluteValue(\Flarum\User\User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $badges = UserBadge::where("user_id", $user->id)->get();
        foreach ($badges as $badge) {
            $conditionAccumulation->updateValue($badge->assigned_at, 1);
        }
        if (!$conditionAccumulation->dirty)
            return false;
        return true;
    }
    public function updateValue(\Flarum\User\User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $conditionAccumulation->clear();
        $q = UserBadge::where("user_id", $user->id);
        $badges = $q->get();
        foreach ($badges as $badge) {
            $conditionAccumulation->updateValue($badge->assigned_at, 1);
        }
        if (!$conditionAccumulation->dirty)
            return false;
        return true;
    }

}