<?php

namespace Xypp\Collector\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xypp\Collector\Api\JsonApi;
use Xypp\Collector\Condition;
use Xypp\Collector\GlobalCondition;

class ListUserConditionsController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $userId = Arr::get($request->getQueryParams(), 'id');
        if ($userId !== null && $userId !== '') {
            $user = User::findOrFail($userId);

            if ((int) $actor->id !== (int) $user->id) {
                $actor->assertCan('user.view-condition');
            }

            $actor = $user;
        }

        $results = Condition::query()
            ->where('user_id', $actor->id)
            ->orderBy('id')
            ->get();
        $globalIdOffset = ((int) $results->max('id')) + 1;

        $data = $results
            ->map(fn (Condition $model) => JsonApi::resource('condition', $model->id, [
                'name' => $model->name,
                'value' => (int) $model->value,
                'accumulation' => $model->accumulation ?: '{}',
                'global' => false,
                'user_id' => (int) $model->user_id,
            ]))
            ->all();

        foreach (GlobalCondition::query()->orderBy('id')->get() as $model) {
            $data[] = JsonApi::resource('condition', $globalIdOffset + (int) $model->id, [
                'name' => $model->name,
                'value' => (int) $model->value,
                'accumulation' => $model->accumulation ?: '{}',
                'global' => true,
            ]);
        }

        return new JsonResponse(['data' => $data]);
    }
}
