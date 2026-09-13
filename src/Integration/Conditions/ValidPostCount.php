<?php

namespace Xypp\Collector\Integration\Conditions;

use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\Integration\Helper\ValidTagsHelper;
use Xypp\Collector\RewardDefinition;

class ValidPostCount extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    private $helper;
    public function __construct(ValidTagsHelper $helper)
    {
        parent::__construct("valid_post_count", null, "xypp-collector.ref.integration.condition.valid_post_count");
        $this->helper = $helper;
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
            if ($this->helper->isAllTagValid($post->discussion->tags, "post"))
                $conditionAccumulation->updateValue($post->created_at, 1);
        }
        if (!$conditionAccumulation->dirty)
            return false;
        return true;
    }
}