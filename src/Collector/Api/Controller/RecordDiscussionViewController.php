<?php

namespace Xypp\Collector\Api\Controller;

use Carbon\Carbon;
use Flarum\Discussion\DiscussionRepository;
use Flarum\Http\RequestUtil;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateCondition;

class RecordDiscussionViewController implements RequestHandlerInterface
{
    public function __construct(
        protected DiscussionRepository $discussions,
        protected Dispatcher $events
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $id = (int) Arr::get($request->getParsedBody(), 'discussionId', 0);
        $discussion = $this->discussions->findOrFail($id, $actor);
        $now = Carbon::now();

        $inserted = DB::table('trust_level_discussion_views')->insertOrIgnore([
            'user_id' => $actor->id,
            'discussion_id' => $discussion->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted > 0) {
            $this->events->dispatch(new UpdateCondition(
                $actor,
                [new ConditionData('discussion_views', 1)]
            ));
        }

        return new JsonResponse([
            'message' => 'success',
            'created' => $inserted > 0,
        ]);
    }
}
