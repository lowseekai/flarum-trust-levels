<?php

namespace Xypp\Collector\Integration\Listener;

use Flarum\Post\Post;
use FoF\BestAnswer\Events\BestAnswerSet;
use FoF\BestAnswer\Events\BestAnswerUnset;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateCondition;
use Flarum\Discussion\Event\Deleting as DiscussionDeleting;
use Flarum\Post\Event\Deleting as PostDeleting;

class BestAnswerListener
{
    protected $events;
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }
    public function subscribe($events)
    {
        $events->listen(BestAnswerSet::class, [$this, 'set']);
        $events->listen(BestAnswerUnset::class, [$this, 'unset']);
        $events->listen(PostDeleting::class, [$this, 'postDelete']);
        $events->listen(DiscussionDeleting::class, [$this, 'discussionDelete']);
    }

    public function set(BestAnswerSet $event)
    {
        $this->events->dispatch(
            new UpdateCondition(
                $event->post->user,
                [new ConditionData('best_answer', 1)]
            )
        );
    }

    public function unset(BestAnswerUnset $event)
    {
        $this->events->dispatch(
            new UpdateCondition(
                $event->post->user,
                [new ConditionData('best_answer', -1)]
            )
        );
    }

    public function postDelete(PostDeleting $event): void
    {
        $post = $event->post;

        if ($post->discussion->best_answer_post_id === $post->id) {
            $this->events->dispatch(
                new UpdateCondition(
                    $event->post->user,
                    [new ConditionData('best_answer', -1)]
                )
            );
        }
    }

    public function discussionDelete(DiscussionDeleting $event): void
    {
        $discussion = $event->discussion;

        if ($discussion->best_answer_post_id) {
            $post = Post::find($discussion->best_answer_post_id);
            $author = $post->user;

            $this->events->dispatch(
                new UpdateCondition(
                    $author,
                    [new ConditionData('best_answer', -1)]
                )
            );
        }
    }
}