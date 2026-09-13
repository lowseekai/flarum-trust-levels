<?php

namespace Xypp\Collector\Integration\Conditions;

use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\RewardDefinition;

class PostCount extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    public function __construct()
    {
        parent::__construct("post_count", null, "xypp-collector.ref.integration.condition.post_count");
    }
    public function getAbsoluteValue(\Flarum\User\User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $posts = $user->posts()->orderByDesc('created_at')->get();
        foreach ($posts as $post) {
            if ($post->type != 'comment')
                continue;
            if ($post->discussion->hidden_at)
                continue;
            if ($post->hidden_at)
                continue;
            $conditionAccumulation->updateValue($post->created_at, 1);
        }
        if (!$conditionAccumulation->dirty)
            return false;
        return true;
    }
}