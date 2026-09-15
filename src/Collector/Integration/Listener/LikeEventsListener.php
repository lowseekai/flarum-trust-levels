<?php

namespace Xypp\Collector\Integration\Listener;

use Illuminate\Events\Dispatcher;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateGlobalCondition;

class LikeEventsListener
{
    protected $events;
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }
    public function subscribe($events)
    {
        $events->listen(\Flarum\Likes\Event\PostWasLiked::class, [$this, 'liked']);
        $events->listen(\Flarum\Likes\Event\PostWasUnliked::class, [$this, 'unliked']);
    }

    public function liked($event)
    {
        $this->events->dispatch(
            new UpdateGlobalCondition(
                [new ConditionData('global.like', 1)]
            )
        );
    }

    public function unliked($event)
    {
        $this->events->dispatch(
            new UpdateGlobalCondition(
                [new ConditionData('global.like', -1)]
            )
        );
    }
}
