<?php

namespace Xypp\Collector\Listener;

use Xypp\Collector\Event\UpdateCondition;
use Xypp\Collector\Helper\ConditionHelper;

class ConditionModifierListener
{
    private ConditionHelper $conditionHelper;
    public function __construct(ConditionHelper $conditionHelper)
    {
        $this->conditionHelper = $conditionHelper;
    }

    public function __invoke(UpdateCondition $event)
    {
        if ($event->user)
            $this->conditionHelper->updateConditions($event->user, $event->data);
    }
}