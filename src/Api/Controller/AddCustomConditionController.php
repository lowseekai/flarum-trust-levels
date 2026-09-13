<?php

namespace Xypp\Collector\Api\Controller;

use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xypp\Collector\Api\JsonApi;
use Xypp\Collector\Custom\ConditionCustomCondition;
use Xypp\Collector\Custom\CustomCondition;

class AddCustomConditionController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $attributes = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $model = new CustomCondition();
        $model->name = (string) Arr::get($attributes, 'name', '');
        $model->display_name = (string) Arr::get($attributes, 'display_name', '');
        $model->evaluation = (string) Arr::get($attributes, 'evaluation', '');
        $model->save();

        $this->syncRelatedConditions($model);

        return JsonApi::response(
            JsonApi::resource('custom-condition', $model->id, $this->attributes($model)),
            201
        );
    }

    private function syncRelatedConditions(CustomCondition $model): void
    {
        foreach ($model->getRelatedNamesFromEval() as $name) {
            $relate = new ConditionCustomCondition();
            $relate->custom_condition_id = $model->id;
            $relate->name = $name;
            $relate->save();
        }
    }

    private function attributes(CustomCondition $model): array
    {
        return [
            'name' => $model->name,
            'display_name' => $model->display_name,
            'evaluation' => $model->evaluation,
        ];
    }
}
