<?php

namespace Xypp\Collector\Api\Controller;

use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xypp\Collector\Custom\ConditionCustomCondition;
use Xypp\Collector\Custom\CustomCondition;

class DeleteCustomConditionController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $model = CustomCondition::findOrFail($this->id($request));
        ConditionCustomCondition::query()
            ->where('custom_condition_id', $model->id)
            ->delete();
        $model->delete();

        return new EmptyResponse(204);
    }

    private function id(ServerRequestInterface $request): string
    {
        return (string) ($request->getAttribute('id') ?? Arr::get($request->getQueryParams(), 'id'));
    }
}
