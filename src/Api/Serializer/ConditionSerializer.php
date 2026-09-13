<?php

namespace Xypp\Collector\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Xypp\Collector\Condition;
use InvalidArgumentException;
use Xypp\Collector\GlobalCondition;

class ConditionSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'condition';

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($model)
    {
        if (!($model instanceof Condition || $model instanceof GlobalCondition)) {
            throw new InvalidArgumentException(
                get_class($this) . ' can only serialize instances of ' . Condition::class
            );
        }

        $accumulation = $model->accumulation;
        if ($accumulation === null) {
            $accumulation = "{}";
        }

        $ret = [
            "name" => $model->name,
            "value" => $model->value,
            "accumulation" => $accumulation,
            "global" => false,
        ];

        if ($model instanceof GlobalCondition) {
            $ret["global"] = true;
        }else{
            $ret["user_id"] = $model->user_id;
        }

        return $ret;
    }
}
