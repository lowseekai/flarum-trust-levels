<?php

namespace Xypp\Collector\Integration\Conditions;

use Flarum\User\User;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;

class ActiveDays extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    public bool $accumulateUpdate = true;

    public function __construct()
    {
        parent::__construct('active_days', null, 'xypp-collector.ref.integration.condition.active_days');
    }

    public function getAbsoluteValue(User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $discussions = $user->discussions()->get(['created_at', 'hidden_at']);

        foreach ($discussions as $discussion) {
            if (!$discussion->hidden_at) {
                $conditionAccumulation->updateValue($discussion->created_at, 1);
            }
        }

        $posts = $user->posts()
            ->with('discussion:id,hidden_at')
            ->get(['created_at', 'hidden_at', 'type', 'discussion_id']);

        foreach ($posts as $post) {
            if (
                $post->type === 'comment'
                && !$post->hidden_at
                && $post->discussion
                && !$post->discussion->hidden_at
            ) {
                $conditionAccumulation->updateValue($post->created_at, 1);
            }
        }

        return $conditionAccumulation->dirty;
    }

    public function updateValue(User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $conditionAccumulation->clear();

        return $this->getAbsoluteValue($user, $conditionAccumulation);
    }
}
