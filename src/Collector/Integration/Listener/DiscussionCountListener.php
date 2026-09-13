<?php

namespace Xypp\Collector\Integration\Listener;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleted;
use Flarum\Discussion\Event\Hidden;
use Flarum\Discussion\Event\Restored;
use \Flarum\Discussion\Event\Started;
use Flarum\User\User;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateCondition;
use Xypp\Collector\Event\UpdateGlobalCondition;
use Xypp\Collector\Integration\Helper\ValidTagsHelper;

class DiscussionCountListener
{
    protected $events;
    private $helper;
    public function __construct(Dispatcher $events, ValidTagsHelper $helper)
    {
        $this->events = $events;
        $this->helper = $helper;
    }
    public function subscribe($events)
    {
        $events->listen(Started::class, [$this, 'startOrRestore']);
        $events->listen(Restored::class, [$this, 'startOrRestore']);
        $events->listen(Hidden::class, [$this, 'hidden']);
    }
    public function startOrRestore(Started|Restored $event)
    {
        $this->discussionCondition($event->discussion->user, $event->discussion, 1);
        if ($event instanceof Restored) {
            $this->discussionPostCondition($event->discussion->user, $event->discussion, 1);
        }
    }

    public function hidden(Hidden $event)
    {
        $this->discussionCondition($event->discussion->user, $event->discussion, -1);
        $this->discussionPostCondition($event->discussion->user, $event->discussion, -1);
    }
    public function deleted(Deleted $event)
    {
        if ($event->discussion->hidden_at)
            return;
        $this->discussionCondition($event->discussion->user, $event->discussion, -1);
        $this->discussionPostCondition($event->discussion->user, $event->discussion, -1);
    }

    public function discussionCondition(?User $user, Discussion $discussion, int $amount)
    {
        $this->events->dispatch(
            new UpdateCondition(
                $user,
                [new ConditionData('discussion_count', $amount)]
            )
        );
        $this->events->dispatch(
            new UpdateGlobalCondition(
                [new ConditionData('global.discussion_count', $amount)]
            )
        );
        if (class_exists(\Flarum\Tags\Tag::class)) {
            if ($this->helper->isAllTagValid($discussion->tags, "discussion")) {
                $this->events->dispatch(
                    new UpdateCondition(
                        $user,
                        [new ConditionData('valid_discussion_count', $amount)]
                    )
                );
                $this->events->dispatch(
                    new UpdateGlobalCondition(
                        [new ConditionData('global.valid_discussion_count', $amount)]
                    )
                );
            }
        }
    }
    public function discussionPostCondition(?User $user, Discussion $discussion, int $amount)
    {
        $updateValid = false;
        if (class_exists(\Flarum\Tags\Tag::class)) {
            if ($this->helper->isAllTagValid($discussion->tags, "discussion"))
                $updateValid = true;
        }

        $discussion->posts->each(function ($post) use ($updateValid, $user, $amount) {
            if ($post->type != 'comment')
                return;
            if ($post->hidden_at)
                return;
            $this->events->dispatch(
                new UpdateCondition(
                    $post->user,
                    [new ConditionData('post_count', $amount)]
                )
            );
            $this->events->dispatch(
                new UpdateGlobalCondition(
                    [new ConditionData('global.post_count', $amount)]
                )
            );
            if ($updateValid) {
                $this->events->dispatch(
                    new UpdateCondition(
                        $post->user,
                        [new ConditionData('valid_post_count', $amount)]
                    )
                );
                $this->events->dispatch(
                    new UpdateGlobalCondition(
                        [new ConditionData('global.valid_post_count', $amount)]
                    )
                );
            }
        });
    }
}