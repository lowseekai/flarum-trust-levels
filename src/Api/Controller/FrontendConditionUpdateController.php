<?php

namespace Xypp\Collector\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Foundation\ValidationException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Helper\ConditionHelper;

class FrontendConditionUpdateController implements RequestHandlerInterface
{
    protected ConditionHelper $helper;
    public function __construct(ConditionHelper $helper)
    {
        $this->helper = $helper;
    }
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();
        $data = Arr::get($request->getParsedBody(), 'data', []);

        if (! is_array($data)) {
            throw new ValidationException([
                'data' => 'The data value must be an array.',
            ]);
        }

        $wp = [];
        foreach ($data as $d) {
            if (! is_array($d) || ! Arr::has($d, 'name')) {
                continue;
            }

            $wp[] = new ConditionData(
                (string) Arr::get($d, 'name'),
                (int) Arr::get($d, 'value', 0)
            );
        }
        $this->helper->updateConditions($actor, $wp, true);
        return new JsonResponse([
            "message" => "success"
        ]);
    }
}
