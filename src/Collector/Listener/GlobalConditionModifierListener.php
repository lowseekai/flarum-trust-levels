<?php

namespace Xypp\Collector\Listener;

use Xypp\Collector\Event\UpdateGlobalCondition;
use Xypp\Collector\Helper\ConditionHelper;

class GlobalConditionModifierListener
{
    private ConditionHelper $conditionHelper;
    public function __construct(ConditionHelper $conditionHelper)
    {
        $this->conditionHelper = $conditionHelper;
    }

    public function __invoke(UpdateGlobalCondition $event)
    {
        $this->conditionHelper->updateGlobalConditions($event->data, false, "event");
    }
}