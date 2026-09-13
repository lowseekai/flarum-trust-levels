<?php

namespace Xypp\Collector\Event;

use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\GlobalCondition;

/**
 * Trigger when global condition changed after write to database.
 */
class GlobalConditionChange
{
    public GlobalCondition $condition;
    public ConditionData $data;
    public function __construct(ConditionData $data, GlobalCondition $condition)
    {
        $this->data = $data;
        $this->condition = $condition;
    }
}