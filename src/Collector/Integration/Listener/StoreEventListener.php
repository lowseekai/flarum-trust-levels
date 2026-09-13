<?php

namespace Xypp\Collector\Integration\Listener;

use \Flarum\Post\Event\Posted;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateCondition;

class StoreEventListener
{
    protected $events;
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }
    public function __invoke(\Xypp\Store\Event\PurchaseDone $event)
    {
        $this->events->dispatch(
            new UpdateCondition(
                $event->user,
                [new ConditionData('store_purchased', 1)]
            )
        );
    }
}