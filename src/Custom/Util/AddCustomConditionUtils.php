<?php

namespace Xypp\Collector\Custom\Util;
use Illuminate\Container\Container;
use Xypp\Collector\Custom\CustomCondition;
use Xypp\Collector\Custom\CustomConditionDefinition;
use Xypp\Collector\Extend\ConditionDefinitionCollection;

class AddCustomConditionUtils
{
    public static function addAll(ConditionDefinitionCollection $collection)
    {
        CustomCondition::all()->each(function (CustomCondition $model) use ($collection) {
            $collection->addDefinition(new CustomConditionDefinition($model));
        });
    }
}
