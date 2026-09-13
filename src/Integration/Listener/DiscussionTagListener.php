<?php

namespace Xypp\Collector\Integration\Listener;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving;
use Flarum\Tags\Event\DiscussionWasTagged;
use Flarum\Tags\Tag;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateCondition;
use Xypp\Collector\Event\UpdateGlobalCondition;
use Xypp\Collector\Integration\Helper\ValidTagsHelper;

class DiscussionTagListener
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
        $events->listen(Saving::class, [$this, 'savingEvent']);
    }

    public function savingEvent(Saving $event)
    {
        if (!class_exists(\Flarum\Tags\Tag::class))
            return;
        if ($event->discussion->hidden_at)
            return;
        if (isset($event->data['relationships']['tags']['data'])) {
            $linkage = (array) $event->data['relationships']['tags']['data'];
            foreach ($linkage as $link) {
                $newTagIds[] = (int) $link['id'];
            }
            $newTags = Tag::whereIn('id', $newTagIds)->get();
            $oldTags = $event->discussion->tags()->get();

            $newValid = $this->helper->isAllTagValid($newTags, "discussion");
            $oldValid = $this->helper->isAllTagValid($oldTags, "discussion");
            if ($newValid && !$oldValid)
                $this->updateTagCount($event->discussion, $newValid ? 1 : -1);
        }
    }

    public function updateTagCount(Discussion $model, $amount)
    {
        $this->events->dispatch(
            new UpdateCondition(
                $model->user,
                [new ConditionData('valid_discussion_count', $amount)]
            )
        );
        $this->events->dispatch(
            new UpdateGlobalCondition(
                [new ConditionData('global.valid_discussion_count', $amount)]
            )
        );
        $model->posts->each(function ($post) use ($amount) {
            if ($post->type != 'comment')
                return;
            if ($post->hidden_at)
                return;
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
        });
    }
}