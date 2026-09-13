<?php

namespace Xypp\Collector\Integration\Listener;

use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use \Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateCondition;
use Xypp\Collector\Event\UpdateGlobalCondition;
use Xypp\Collector\Integration\Helper\ValidTagsHelper;

class PostCountListener
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
        $events->listen(Posted::class, [$this, 'postedOrRestore']);
        $events->listen(Hidden::class, [$this, 'hidden']);
        $events->listen(Restored::class, [$this, 'postedOrRestore']);
    }
    public function postedOrRestore(Posted|Restored $event)
    {
        if ($event->post->type != 'comment')
            return;
        if ($event->post->discussion->hidden_at)
            return;
        $this->postCondition($event->post->user, $event->post, 1);
    }
    public function hidden(Hidden $event)
    {
        if ($event->post->type != 'comment')
            return;
        if ($event->post->discussion->hidden_at)
            return;
        $this->postCondition($event->post->user, $event->post, -1);
    }
    public function delete(Deleted $event)
    {
        if ($event->post->type != 'comment')
            return;
        if ($event->post->discussion->hidden_at)
            return;
        if (!$event->post->hidden_at)
            $this->postCondition($event->post->user, $event->post, -1);
    }

    protected function postCondition(?User $user, Post $post, int $amount)
    {
        $this->events->dispatch(
            new UpdateCondition(
                $user,
                [new ConditionData('post_count', $amount)]
            )
        );
        $this->events->dispatch(
            new UpdateGlobalCondition(
                [new ConditionData('global.post_count', $amount)]
            )
        );

        if (class_exists(\Flarum\Tags\Tag::class)) {
            if ($this->helper->isAllTagValid($post->discussion->tags, "post")) {
                $this->events->dispatch(
                    new UpdateCondition(
                        $user,
                        [new ConditionData('valid_post_count', $amount)]
                    )
                );
                $this->events->dispatch(
                    new UpdateGlobalCondition(
                        [new ConditionData('global.valid_post_count', $amount)]
                    )
                );
            }
        }
    }
}