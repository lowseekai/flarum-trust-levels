<?php

namespace Xypp\Collector\Api\Controller;

use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xypp\Collector\Api\JsonApi;
use Xypp\Collector\Custom\CustomCondition;

class ListCustomConditionController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $data = CustomCondition::query()
            ->orderBy('id')
            ->get()
            ->map(fn (CustomCondition $model) => JsonApi::resource('custom-condition', $model->id, [
                'name' => $model->name,
                'display_name' => $model->display_name,
                'evaluation' => $model->evaluation,
            ]))
            ->all();

        return new JsonResponse(['data' => $data]);
    }
}
