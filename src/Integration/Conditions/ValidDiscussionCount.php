<?php

namespace Xypp\Collector\Integration\Conditions;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\Integration\Helper\ValidTagsHelper;
use Xypp\Collector\RewardDefinition;

class ValidDiscussionCount extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    private $helper;
    public function __construct(ValidTagsHelper $helper)
    {
        parent::__construct("valid_discussion_count", null, "xypp-collector.ref.integration.condition.valid_discussion_count");
        $this->helper = $helper;
    }
    public function getAbsoluteValue(User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $discussions = $user->discussions()->orderByDesc('created_at')->get();
        foreach ($discussions as $discuss) {
            if ($discuss->hidden_at)
                continue;
            if ($this->helper->isAllTagValid($discuss->tags, "discussion"))
                $conditionAccumulation->updateValue($discuss->created_at, 1);
        }
        if (!$conditionAccumulation->dirty)
            return false;
        return true;
    }
}