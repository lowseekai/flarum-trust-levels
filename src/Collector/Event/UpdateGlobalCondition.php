<?php

namespace Xypp\Collector\Event;

use Flarum\User\User;
use Xypp\Collector\Data\ConditionData;

class UpdateGlobalCondition
{
    public function __construct(array|ConditionData $data)
    {
        $this->data = $data;
    }
    public array|ConditionData $data;
}