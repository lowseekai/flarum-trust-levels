<?php

namespace Xypp\Collector\Event;

use Flarum\User\User;
use Xypp\Collector\Data\ConditionData;

class UpdateCondition
{
    public function __construct(?User $user, array|ConditionData $data)
    {
        $this->user = $user;
        $this->data = $data;
    }
    public User $user;
    public array|ConditionData $data;
}