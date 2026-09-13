<?php

namespace Xypp\Collector\Integration\Conditions;

use Michaelbelgium\Discussionviews\Models\DiscussionView;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\RewardDefinition;

class DiscussionViews extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    public function __construct()
    {
        parent::__construct("discussion_views",null,"xypp-collector.ref.integration.condition.discussion_views");
    }
    public function getAbsoluteValue(\Flarum\User\User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $discussions = $user->discussions()->orderByDesc('created_at')->get();
        foreach ($discussions as $discuss) {
            $views = $discuss->hasMany(DiscussionView::class)->get();
            foreach ($views as $view) {
                $conditionAccumulation->updateValue($view->visited_at, 1);
            }
        }
        return true;
    }
}