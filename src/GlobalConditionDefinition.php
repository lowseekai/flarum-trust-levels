<?php

namespace Xypp\Collector;

use Flarum\User\User;
use Xypp\Collector\Data\ConditionAccumulation;
use Xypp\Collector\Enum\ConditionOperator;
use Illuminate\Console\Command;

class GlobalConditionDefinition
{
    /**
     * Name of this condition
     * @var string
     */
    public string $name;
    /**
     * Weather this condition can be triggered from frontend
     * @var bool
     */
    public bool $allowFrontendTrigger = false;

    /**
     * Can accumulate when get absolute value;
     * @var bool
     */
    public bool $accumulateAbsolute = false;

    /**
     * Supports update with updateValue
     * @var bool
     */
    public bool $accumulateUpdate = false;
    /**
     * No auto update support. Will display a check button on frontend
     * @var bool
     */
    public bool $needManualUpdate = false;

    /**
     * Translate key
     */
    public ?string $translateKey = null;

    public function __construct(?string $name = null, ?bool $frontend = false, ?string $translateKey = null)
    {
        if ($name)
            $this->name = "global." . $name;
        if ($frontend)
            $this->allowFrontendTrigger = true;
        if ($translateKey)
            $this->translateKey = $translateKey;
    }

    public function compare(int $value, string $operator, int $compareValue): bool
    {
        switch ($operator) {
            case ConditionOperator::EQUAL:
                return $value == $compareValue;
            case ConditionOperator::NOT_EQUAL:
                return $value != $compareValue;
            case ConditionOperator::GREATER_THAN:
                return $value > $compareValue;
            case ConditionOperator::LESS_THAN:
                return $value < $compareValue;
            case ConditionOperator::GREATER_THAN_OR_EQUAL:
                return $value >= $compareValue;
            case ConditionOperator::LESS_THAN_OR_EQUAL:
                return $value <= $compareValue;
            default:
                return false;
        }
    }
    public function getAbsoluteValue(ConditionAccumulation $accumulation): bool
    {
        return false;
    }
    public function updateValue(ConditionAccumulation $accumulation): bool
    {
        return false;
    }
}