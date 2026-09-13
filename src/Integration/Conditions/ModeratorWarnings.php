<?php

namespace Xypp\Collector\Integration\Conditions;

use Askvortsov\FlarumWarnings\Model\Warning;
use Michaelbelgium\Discussionviews\Models\DiscussionView;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;

class ModeratorWarnings extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    public function __construct()
    {
        parent::__construct("moderator_warnings", null, "xypp-collector.ref.integration.condition.moderator_warnings");
    }
    public function getAbsoluteValue(\Flarum\User\User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $warnings = Warning::where('user_id', $user->id)->whereNull('hidden_at')->get();
        foreach ($warnings as $warning) {
            $conditionAccumulation->updateValue($warning->created_at, 1);
        }
        return true;
    }
}