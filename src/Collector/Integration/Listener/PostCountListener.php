<?php

namespace Xypp\Collector\Integration\Listener;

use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateCondition;
use Xypp\Collector\Event\UpdateGlobalCondition;

class PostCountListener
{
    protected Dispatcher $events;

    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    public function subscribe($events): void
    {
        $events->listen(Posted::class, [$this, 'postedOrRestore']);
        $events->listen(Restored::class, [$this, 'postedOrRestore']);
        $events->listen(Hidden::class, [$this, 'hidden']);
        $events->listen(Deleted::class, [$this, 'deleted']);
    }

    public function postedOrRestore(Posted|Restored $event): void
    {
        $post = $event->post;

        if ($post->type !== 'comment' || $post->discussion->hidden_at) {
            return;
        }

        $this->postCondition($post->user, $post, 1);
    }

    public function hidden(Hidden $event): void
    {
        $post = $event->post;

        if ($post->type !== 'comment' || $post->discussion->hidden_at) {
            return;
        }

        $this->postCondition($post->user, $post, -1);
    }

    public function deleted(Deleted $event): void
    {
        $post = $event->post;

        if (
            $post->type !== 'comment'
            || $post->discussion->hidden_at
            || $post->hidden_at
        ) {
            return;
        }

        $this->postCondition($post->user, $post, -1);
    }

    protected function postCondition(?User $user, Post $post, int $amount): void
    {
        if (!$user) {
            return;
        }

        $updates = [new ConditionData('post_count', $amount)];

        if ($this->shouldUpdateRepliedDiscussions($user, $post, $amount)) {
            $updates[] = new ConditionData('replied_discussions', $amount);
        }

        $this->events->dispatch(new UpdateCondition(
            $user,
            $updates
        ));
        $this->events->dispatch(new UpdateCondition(
            $user,
            [new ConditionData('active_days', $amount)]
        ));
        $this->events->dispatch(new UpdateGlobalCondition(
            [new ConditionData('global.post_count', $amount)]
        ));
    }

    protected function shouldUpdateRepliedDiscussions(User $user, Post $post, int $amount): bool
    {
        if (
            !$post->discussion->first_post_id
            || (is_numeric($post->number) && (int) $post->number <= 1)
            || ($post->id && (int) $post->id === (int) $post->discussion->first_post_id)
        ) {
            return false;
        }

        $visibleReplyCount = Post::query()
            ->where('discussion_id', $post->discussion_id)
            ->where('user_id', $user->id)
            ->where('type', 'comment')
            ->where('is_private', false)
            ->whereNull('hidden_at')
            ->where('id', '!=', $post->discussion->first_post_id)
            ->where('id', '!=', $post->id)
            ->count();

        // Posted/restored events can run before the row is visible in its new
        // state, while hidden/deleted events can run before or after removal.
        // Counting other replies makes the transition deterministic.
        return $visibleReplyCount === 0;
    }
}
