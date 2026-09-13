<?php

namespace Xypp\Collector\Custom;
use Brick\Math\Exception\MathException;
use Carbon\Carbon;
use Flarum\User\User;
use NXP\MathExecutor;
use Xypp\Collector\ConditionDefinition;
use Xypp\Collector\Custom\CustomCondition;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\Helper\SettingHelper;
use Xypp\LocalizeDate\Helper\CarbonZoneHelper;

class CustomConditionDefinition extends ConditionDefinition
{
    public bool $accumulateAbsolute = true;
    public bool $accumulateUpdate = true;
    public string $evaluation;
    protected CustomCondition $model;
    protected ?MathExecutor $mathExecutor = null;
    private CarbonZoneHelper $carbonZoneHelper;
    private SettingHelper $settingHelper;
    public function __construct(CustomCondition $model)
    {
        parent::__construct($model->name, false, $model->display_name);
        $this->evaluation = $model->evaluation;
        $this->model = $model;
        $this->carbonZoneHelper = resolve(CarbonZoneHelper::class);
        $this->settingHelper = resolve(SettingHelper::class);
    }
    public function getExecuter()
    {
        if (!$this->mathExecutor) {
            $this->mathExecutor = new MathExecutor();
            $this->mathExecutor->setDivisionByZeroIsZero();
        }
        return $this->mathExecutor;
    }

    public function evaluate(Carbon $ref, User $user, ?int $day): int
    {

        $related = $this->model->related_conditions()->get();
        $evaluation = $this->evaluation;
        /**
         * @var ConditionCustomCondition $conditionCustom
         */
        foreach ($related as $conditionCustom) {
            $condition = $conditionCustom->getCondition($user);
            $name = $condition->name;
            if (!$day)
                $value = $condition->getAccumulation()->getTotal(ConditionAccumulation::CALCULATE_SUM);
            else {
                $value = $condition->getAccumulation()->getSpan($ref, $day, ConditionAccumulation::CALCULATE_SUM);
            }
            $evaluation = str_replace("{{$name}}", $value, $evaluation);
        }
        try {
            return floor($this->getExecuter()->execute($evaluation));
        } catch (MathException $e) {
            return 0;
        }
    }
    public function getAbsoluteValue(User $user, ConditionAccumulation $accumulation): bool
    {
        $max = $this->settingHelper->maxKeep();
        $ref = $this->carbonZoneHelper->now();
        $current = $ref->copy();
        $last = 0;
        for ($i = 1; $i <= $max; $i++) {
            $currentVal = $this->evaluate($ref, $user, $i);
            $accumulation->updateValue($current, $currentVal - $last);
            $last = $currentVal;
            $current->subDay();
        }
        $total = $this->evaluate($ref, $user, null);
        $accumulation->updateValue($current, $total - $last);
        $accumulation->updateFlag($ref->format('Ymd'));
        return true;
    }
    public function updateValue(User $user, ConditionAccumulation $accumulation): bool
    {
        $flag = $accumulation->updateFlag;
        $ref = $this->carbonZoneHelper->now();
        $current = $ref->copy();
        $lastUpd = $ref->copy()
            ->year(substr($flag, 0, 4))
            ->month(substr($flag, 4, 2))
            ->day(substr($flag, 6, 2));
        $max = $this->settingHelper->maxKeep();

        $last = 0;
        for ($i = 1; $i <= $max && $current->gte($lastUpd); $i++) {
            $currentVal = $this->evaluate($ref, $user, $i);
            $accumulation->updateValue($current, $currentVal - $last, false);
            $last = $currentVal;
            $current->subDay();
        }
        if ($i >= $max) {
            $total = $this->evaluate($ref, $user, null);
            $accumulation->updateValue($current, $total - $last);
        }
        $accumulation->updateFlag($ref->format('Ymd'));
        return true;
    }
}