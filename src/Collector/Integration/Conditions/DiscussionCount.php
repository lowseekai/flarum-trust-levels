<?php

namespace Xypp\Collector\Integration\Conditions;

use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\RewardDefinition;

class DiscussionCount extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;

    public function __construct()
    {
        parent::__construct("discussion_count", null, "xypp-collector.ref.integration.condition.discussion_count");
    }
    public function getAbsoluteValue(\Flarum\User\User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $discussions = $user->discussions()->orderByDesc('created_at')->get();
        foreach ($discussions as $discuss) {
            if ($discuss->hidden_at)
                continue;
            $conditionAccumulation->updateValue($discuss->created_at, 1);
        }
        if (!$conditionAccumulation->dirty)
            return false;
        return true;
    }
}