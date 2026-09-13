<?php

namespace Xypp\Collector\Integration\Global;

use Flarum\Discussion\Discussion;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\GlobalConditionDefinition;
use Xypp\Collector\Helper\CommandContextHelper;
use Xypp\Collector\Integration\Helper\ValidTagsHelper;

class GlobalValidDiscussionCount extends GlobalConditionDefinition
{
    public bool $accumulateAbsolute = true;
    private $helper;
    protected CommandContextHelper $commandContextHelper;
    public function __construct(ValidTagsHelper $helper, CommandContextHelper $commandContextHelper)
    {
        parent::__construct("valid_discussion_count", null, "xypp-collector.ref.integration.global-condition.valid_discussion_count");
        $this->commandContextHelper = $commandContextHelper;
        $this->helper = $helper;
    }
    public function getAbsoluteValue(ConditionAccumulation $conditionAccumulation): bool
    {
        $discussions = Discussion::query()->orderByDesc('created_at')->get(['created_at', 'hidden_at']);
        $this->commandContextHelper->withProgressBar($discussions, function ($discuss) use (&$conditionAccumulation) {
            if ($discuss->hidden_at)
                return;
            if ($this->helper->isAllTagValid($discuss->tags, "discussion"))
                $conditionAccumulation->updateValue($discuss->created_at, 1);

        });
        if (!$conditionAccumulation->dirty)
            return false;
        return true;
    }
}