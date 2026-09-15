<?php

namespace Xypp\Collector\Integration\Conditions;

use Carbon\Carbon;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Data\ConditionAccumulation;

class DiscussionViews extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    public bool $accumulateUpdate = true;

    public function __construct(protected ConnectionInterface $connection)
    {
        parent::__construct('discussion_views', null, 'xypp-collector.ref.integration.condition.discussion_views');
    }

    public function getAbsoluteValue(User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $views = $this->connection->table('trust_level_discussion_views')
            ->join('discussions', 'discussions.id', '=', 'trust_level_discussion_views.discussion_id')
            ->where('trust_level_discussion_views.user_id', $user->id)
            ->whereNull('discussions.hidden_at')
            ->where('discussions.is_private', false)
            ->orderByDesc('created_at')
            ->get(['trust_level_discussion_views.created_at']);

        $conditionAccumulation->clear();

        foreach ($views as $view) {
            if ($view->created_at) {
                $date = Carbon::createFromFormat($this->connection->getQueryGrammar()->getDateFormat(), $view->created_at);
                $conditionAccumulation->updateValue($date, 1);
            }
        }

        return $conditionAccumulation->dirty;
    }

    public function updateValue(User $user, ConditionAccumulation $conditionAccumulation): bool
    {
        $conditionAccumulation->clear();

        return $this->getAbsoluteValue($user, $conditionAccumulation);
    }
}
