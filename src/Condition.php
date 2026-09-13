<?php

namespace Xypp\Collector;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\User\User;
use Xypp\Collector\Data\ConditionAccumulation;

class Condition extends AbstractModel
{
    public $timestamps = true;
    protected $dates = ['updated_at', 'created_at'];
    protected $table = 'collector_condition';
    protected ?ConditionAccumulation $accObj = null;
    public function user()
    {
        return $this->belongsTo(User::class, "user_id", "id");
    }
    public function getAccumulation()
    {
        if ($this->accObj === null) {
            $accumulation = $this->accumulation;
            if ($accumulation === null) {
                $accumulation = "{}";
            }
            $this->accObj = new ConditionAccumulation($accumulation);
        }
        return $this->accObj;
    }
    public function setAccumulation(ConditionAccumulation $accumulation)
    {
        $this->accObj = $accumulation;
    }
    public static function boot()
    {
        parent::boot();
        static::saving(function (Condition $model) {
            if ($model->accObj !== null) {
                $model->accumulation = $model->accObj->serialize();
            }
        });
    }
}
