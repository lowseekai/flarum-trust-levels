<?php

namespace Xypp\Collector\Integration\Conditions;

use Xypp\Collector\ConditionDefinition;

class ReadingTime extends ConditionDefinition
{
    public function __construct()
    {
        parent::__construct('reading_time', true, 'xypp-collector.ref.integration.condition.reading_time');
    }
}
