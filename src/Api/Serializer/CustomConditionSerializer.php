<?php

namespace Xypp\Collector\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Xypp\Collector\Condition;
use InvalidArgumentException;
use Xypp\Collector\Custom\CustomCondition;

class CustomConditionSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'custom-condition';

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($model)
    {
        if (!($model instanceof CustomCondition)) {
            throw new InvalidArgumentException(
                get_class($this) . ' can only serialize instances of ' . Condition::class
            );
        }
        return [
            "id" => $model->id,
            "name" => $model->name,
            "display_name" => $model->display_name,
            "evaluation" => $model->evaluation
        ];
    }
}
