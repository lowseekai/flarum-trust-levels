<?php

namespace Xypp\Collector\Custom;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\User\User;
use Xypp\Collector\Condition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\GlobalCondition;

/**
 * @property string $name
 * @property string $custom_condition_id
 */
class ConditionCustomCondition extends AbstractModel
{
    protected $table = 'collector_condition_custom_condition';
    protected GlobalCondition|Condition|null $queriedCondition = null;
    public function getCondition(User $user): GlobalCondition|Condition
    {
        if ($this->queriedCondition)
            return $this->queriedCondition;
        if (str_starts_with($this->name, "global.")) {
            $ret = GlobalCondition::where("name", $this->name)->first();
        } else {
            $ret = Condition::where("name", $this->name)->where("user_id", $user->id)->first();
        }
        $this->queriedCondition = $ret;
        return $ret;
    }
    public function custom()
    {
        return $this->belongsTo(CustomCondition::class, "custom_condition_id", "id");
    }
}
