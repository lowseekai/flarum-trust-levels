<?php

namespace Xypp\Collector\Integration\Conditions;

use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Date;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;

class RepliedDiscussions extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;

    public function __construct(protected ConnectionInterface $connection)
    {
        parent::__construct('replied_discussions', null, 'xypp-collector.ref.integration.condition.replied_discussions');
    }

    public function getAbsoluteValue(User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $conditionAccumulation->clear();

        $rows = $this->connection->table('posts')
            ->join('discussions', 'discussions.id', '=', 'posts.discussion_id')
            ->where('posts.user_id', $user->id)
            ->where('posts.type', 'comment')
            ->where('posts.is_private', false)
            ->whereNull('posts.hidden_at')
            ->whereNull('discussions.hidden_at')
            ->whereColumn('posts.id', '!=', 'discussions.first_post_id')
            ->groupBy('posts.discussion_id')
            ->selectRaw('MIN(posts.created_at) as first_replied_at')
            ->get();

        foreach ($rows as $row) {
            if ($row->first_replied_at) {
                $date = Date::createFromFormat($this->connection->getQueryGrammar()->getDateFormat(), $row->first_replied_at);
                $conditionAccumulation->updateValue($date, 1);
            }
        }

        return $conditionAccumulation->dirty;
    }
}
