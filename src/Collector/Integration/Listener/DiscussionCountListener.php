<?php

namespace Xypp\Collector\Integration\Listener;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleted;
use Flarum\Discussion\Event\Hidden;
use Flarum\Discussion\Event\Restored;
use Flarum\Discussion\Event\Started;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateCondition;
use Xypp\Collector\Event\UpdateGlobalCondition;
use Xypp\Collector\Helper\ConditionHelper;

class DiscussionCountListener
{
    protected Dispatcher $events;

    public function __construct(
        Dispatcher $events,
        protected ConditionHelper $conditionHelper,
        protected ConnectionInterface $connection
    )
    {
        $this->events = $events;
    }

    public function subscribe($events): void
    {
        $events->listen(Started::class, [$this, 'startOrRestore']);
        $events->listen(Restored::class, [$this, 'startOrRestore']);
        $events->listen(Hidden::class, [$this, 'hidden']);
        $events->listen(Deleted::class, [$this, 'deleted']);
    }

    public function startOrRestore(Started|Restored $event): void
    {
        $discussion = $event->discussion;
        $this->discussionCondition($discussion->user, $discussion, 1);

        if ($event instanceof Restored) {
            $this->discussionPostCondition($discussion, 1);
            $this->refreshDiscussionViewConditions($discussion);
        }
    }

    public function hidden(Hidden $event): void
    {
        $discussion = $event->discussion;
        $this->discussionCondition($discussion->user, $discussion, -1);
        $this->discussionPostCondition($discussion, -1);
        $this->refreshDiscussionViewConditions($discussion);
    }

    public function deleted(Deleted $event): void
    {
        if ($event->discussion->hidden_at) {
            return;
        }

        $discussion = $event->discussion;
        $this->discussionCondition($discussion->user, $discussion, -1);
        $this->discussionPostCondition($discussion, -1);
        $this->refreshDiscussionViewConditions($discussion);
    }

    protected function discussionCondition(?User $user, Discussion $discussion, int $amount): void
    {
        if ($user) {
            $this->events->dispatch(new UpdateCondition(
                $user,
                [new ConditionData('discussion_count', $amount)]
            ));

            $this->events->dispatch(new UpdateCondition(
                $user,
                [new ConditionData('active_days', $amount)]
            ));
        }

        $this->events->dispatch(new UpdateGlobalCondition(
            [new ConditionData('global.discussion_count', $amount)]
        ));
    }

    protected function discussionPostCondition(Discussion $discussion, int $amount): void
    {
        $repliedUserIds = [];

        $discussion->posts->each(function ($post) use ($amount): void {
            if ($post->type !== 'comment' || $post->hidden_at || !$post->user) {
                return;
            }

            $this->events->dispatch(new UpdateCondition(
                $post->user,
                [new ConditionData('post_count', $amount)]
            ));

            $this->events->dispatch(new UpdateCondition(
                $post->user,
                [new ConditionData('active_days', $amount)]
            ));

            $this->events->dispatch(new UpdateGlobalCondition(
                [new ConditionData('global.post_count', $amount)]
            ));
        });

        $discussion->posts->each(function ($post) use (&$repliedUserIds): void {
            if (
                $post->type === 'comment'
                && !$post->hidden_at
                && $post->user
                && (int) $post->number > 1
                && (int) $post->id !== (int) $discussion->first_post_id
            ) {
                $repliedUserIds[$post->user->id] = $post->user;
            }
        });

        foreach ($repliedUserIds as $user) {
            $this->events->dispatch(new UpdateCondition(
                $user,
                [new ConditionData('replied_discussions', $amount)]
            ));
        }
    }

    protected function refreshDiscussionViewConditions(Discussion $discussion): void
    {
        $userIds = $this->connection->table('trust_level_discussion_views')
            ->where('discussion_id', $discussion->id)
            ->distinct()
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('id', $userIds)
            ->get()
            ->each(function (User $user): void {
                $this->conditionHelper->updateUserCondition($user, 'discussion_views');
            });
    }
}
