<?php

namespace Xypp\Collector\Custom;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\User\User;
use Xypp\Collector\Data\ConditionAccumulation;

/**
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string $evaluation
 */
class CustomCondition extends AbstractModel
{
    protected $table = 'collector_custom_condition';

    public function getRelatedNamesFromEval(): array
    {
        $evaluate = $this->evaluation;
        $count = preg_match_all("/\\{([^\\}]*)\\}/", $evaluate, $matches);
        if ($count === false)
            return [];
        return array_unique($matches[1]);
    }

    public function related_conditions()
    {
        return $this->hasMany(ConditionCustomCondition::class, "custom_condition_id", "id");
    }
}
