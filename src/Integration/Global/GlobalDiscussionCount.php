<?php

namespace Xypp\Collector\Integration\Global;

use Flarum\Discussion\Discussion;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\GlobalConditionDefinition;
use Xypp\Collector\Helper\CommandContextHelper;
use Xypp\Collector\RewardDefinition;

class GlobalDiscussionCount extends GlobalConditionDefinition
{
    public bool $accumulateAbsolute = true;
    protected CommandContextHelper $commandContextHelper;
    public function __construct(CommandContextHelper $commandContextHelper)
    {
        parent::__construct("discussion_count", null, "xypp-collector.ref.integration.global-condition.discussion_count");
        $this->commandContextHelper = $commandContextHelper;
    }
    public function getAbsoluteValue(ConditionAccumulation $conditionAccumulation): bool
    {
        $discussions = Discussion::query()->orderByDesc('created_at')->get(['created_at', 'hidden_at']);
        $this->commandContextHelper->withProgressBar($discussions, function ($discuss) use (&$conditionAccumulation) {
            if ($discuss->hidden_at)
                return;
            $conditionAccumulation->updateValue($discuss->created_at, 1);
        });
        if (!$conditionAccumulation->dirty)
            return false;
        return true;
    }
}