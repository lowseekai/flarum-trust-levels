<?php

namespace Xypp\Collector\Integration\Global;

use Flarum\Post\Post;
use Illuminate\Support\Collection;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\GlobalConditionDefinition;
use Xypp\Collector\Helper\CommandContextHelper;
use Xypp\Collector\Integration\Helper\ValidTagsHelper;

class GlobalValidPostCount extends GlobalConditionDefinition
{
    public bool $accumulateAbsolute = true;
    private $helper;
    protected CommandContextHelper $commandContextHelper;
    public function __construct(ValidTagsHelper $helper, CommandContextHelper $commandContextHelper)
    {
        parent::__construct("valid_post_count", null, "xypp-collector.ref.integration.global-condition.valid_post_count");
        $this->helper = $helper;
        $this->commandContextHelper = $commandContextHelper;
    }
    public function getAbsoluteValue(ConditionAccumulation $conditionAccumulation): bool
    {
        $total = Post::query()->count();
        $posts = Post::query()->orderByDesc('created_at')->select(['created_at', 'discussion_id', 'type']);
        $progress = $this->commandContextHelper->getProgressBar($total);
        $posts->chunk(2000, function (Collection $posts) use (&$conditionAccumulation, &$progress) {
            foreach ($posts->getIterator() as $post) {
                if ($post->discussion->hidden_at)
                    return;
                if ($post->hidden_at)
                    return;
                if ($this->helper->isAllTagValid($post->discussion->tags, "post"))
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