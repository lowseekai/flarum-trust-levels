<?php

namespace Xypp\Collector\Integration\Conditions;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\ForumQuests\ConditionDefinition;

class BestAnswer extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;

    public function __construct()
    {
        parent::__construct("best_answer", false, "xypp-collector.ref.integration.condition.best_answer");
    }

    public function getAbsoluteValue(User $user, ConditionAccumulation $accumulation): bool
    {
        $discussions = Discussion::whereNotNull('best_answer_post_id')
            ->leftJoin('posts', 'posts.id', '=', 'discussions.best_answer_post_id')
            ->where('posts.user_id', $user->id)
            ->get();
        foreach ($discussions as $bestAnswer) {
            $post = Post::find($bestAnswer->best_answer_post_id);
            $accumulation->updateValue($post->created_at, 1);
        }
        return $accumulation->dirty;
    }
}