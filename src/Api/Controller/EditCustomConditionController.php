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

class EditCustomConditionController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $model = CustomCondition::findOrFail($this->id($request));
        $attributes = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $model->name = (string) Arr::get($attributes, 'name', $model->name);
        $model->display_name = (string) Arr::get($attributes, 'display_name', $model->display_name);
        $model->evaluation = (string) Arr::get($attributes, 'evaluation', $model->evaluation);
        $model->save();

        ConditionCustomCondition::query()
            ->where('custom_condition_id', $model->id)
            ->delete();

        foreach ($model->getRelatedNamesFromEval() as $name) {
            $relate = new ConditionCustomCondition();
            $relate->custom_condition_id = $model->id;
            $relate->name = $name;
            $relate->save();
        }

        return JsonApi::response(
            JsonApi::resource('custom-condition', $model->id, [
                'name' => $model->name,
                'display_name' => $model->display_name,
                'evaluation' => $model->evaluation,
            ])
        );
    }

    private function id(ServerRequestInterface $request): string
    {
        return (string) ($request->getAttribute('id') ?? Arr::get($request->getQueryParams(), 'id'));
    }
}
