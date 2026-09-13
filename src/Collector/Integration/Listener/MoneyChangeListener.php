<?php

namespace Xypp\Collector\Integration\Listener;

use AntoineFr\Money\Event\MoneyUpdated;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateCondition;
class MoneyChangeListener
{
    protected $events;
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }
    public function __invoke(MoneyUpdated $event)
    {
        $user = $event->user;
        $this->events->dispatch(
            new UpdateCondition(
                $user,
                [new ConditionData('money', intval($user->money), "", true)]
            )
        );
    }
}