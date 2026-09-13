<?php

namespace Xypp\Collector\Integration\Conditions;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Date;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\RewardDefinition;

class LikeRecv extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    public ConnectionInterface $connection;
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct("like_recv",null,"xypp-collector.ref.integration.condition.like_recv");
        $this->connection = $connection;
    }
    public function getAbsoluteValue(\Flarum\User\User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $posts = $user->posts()->get(['id']);
        $ids = $posts->pluck('id')->toArray();
        $likes = $this->connection->table("post_likes")->whereIn("post_id", $ids)->get(['created_at']);
        foreach ($likes as $like) {
            $date = Date::createFromFormat($this->connection->getQueryGrammar()->getDateFormat(), $like->created_at);
            $conditionAccumulation->updateValue($date, 1);
        }
        return $conditionAccumulation->dirty;
    }
}