<?php

namespace Xypp\Collector\Integration\Global;

use Flarum\Post\Post;
use Illuminate\Support\Collection;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\GlobalConditionDefinition;
use Xypp\Collector\Helper\CommandContextHelper;

class GlobalPostCount extends GlobalConditionDefinition
{
    public bool $accumulateAbsolute = true;
    protected CommandContextHelper $commandContextHelper;
    public function __construct(CommandContextHelper $commandContextHelper)
    {
        parent::__construct("post_count", null, "xypp-collector.ref.integration.global-condition.post_count");
        $this->commandContextHelper = $commandContextHelper;
    }
    public function getAbsoluteValue(ConditionAccumulation $conditionAccumulation): bool
    {
        $total = Post::query()->count();
        $posts = Post::query()->orderByDesc('created_at')->select(['created_at', 'discussion_id', 'type']);
        $progress = $this->commandContextHelper->getProgressBar($total);
        $posts->chunk(2000, function (Collection $posts) use (&$conditionAccumulation, &$progress) {
            foreach ($posts->getIterator() as $post) {
                if ($post->type != 'comment')
                    return;
                if ($post->discussion->hidden_at)
                    return;
                if ($post->hidden_at)
                    return;
                $conditionAccumulation->updateValue($post->created_at, 1);
            }
            if ($progress)
                $progress->advance(count($posts));
        });
        if ($progress)
            $progress->finish();
        if (!$conditionAccumulation->dirty)
            return false;
        return true;
    }
}