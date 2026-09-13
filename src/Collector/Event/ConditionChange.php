<?php

namespace Xypp\Collector\Event;

use Flarum\User\User;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Condition;

/**
 * Trigger when condition changed after write to database.
 */
class ConditionChange
{
    public Condition $condition;
    public User $user;
    public ConditionData $data;
    public function __construct(User $user, ConditionData $data, Condition $condition)
    {
        $this->user = $user;
        $this->data = $data;
        $this->condition = $condition;
    }
}