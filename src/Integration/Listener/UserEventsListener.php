<?php

namespace Xypp\Collector\Integration\Listener;

use Flarum\User\Event\AvatarChanged;
use Flarum\User\Event\EmailChanged;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateCondition;

class UserEventsListener
{
    protected $events;
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }
    public function subscribe($events)
    {
        $events->listen(EmailChanged::class, [$this, 'emailChange']);
        $events->listen(AvatarChanged::class, [$this, 'avatarChange']);
    }
    public function avatarChange(AvatarChanged $event)
    {
        $this->events->dispatch(
            new UpdateCondition(
                $event->user,
                [new ConditionData('avatar_changed', 1)]
            )
        );
    }
    public function emailChange(EmailChanged $event)
    {
        $this->events->dispatch(
            new UpdateCondition(
                $event->user,
                [new ConditionData('email_changed', 1)]
            )
        );
    }

}