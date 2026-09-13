<?php

namespace Xypp\Collector\Integration\ControllerCheck;
use Askvortsov\FlarumWarnings\Model\Warning;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Events\Dispatcher;
use Xypp\Collector\Data\ConditionData;
use Xypp\Collector\Event\UpdateCondition;

class ModeratorWarnings
{
    protected $events;
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }
    public function checks(string $source, Warning $model, ServerRequestInterface $request)
    {
        $user = $model->warnedUser;

        if ($source === "create") {
            $this->events->dispatch(
                new UpdateCondition(
                    $user,
                    [new ConditionData('moderator_warnings', 1)]
                )
            );
            $this->events->dispatch(
                new UpdateCondition(
                    $user,
                    [new ConditionData('moderator_warning_strikes', $model->strikes)]
                )
            );
        } else if ($source === "delete") {
            if (!$model->hidden_at) {
                $this->events->dispatch(
                    new UpdateCondition(
                        $user,
                        [new ConditionData('moderator_warnings', -1)]
                    )
                );
                $this->events->dispatch(
                    new UpdateCondition(
                        $user,
                        [new ConditionData('moderator_warning_strikes', -1 * $model->strikes)]
                    )
                );
            }
        } else if ($source === "update") {
            $requestBody = $request->getParsedBody();
            $requestData = $requestBody['data']['attributes'];
            $this->events->dispatch(
                new UpdateCondition(
                    $user,
                    [new ConditionData('moderator_warnings', $requestData['isHidden'] ? -1 : 1)]
                )
            );
            $this->events->dispatch(
                new UpdateCondition(
                    $user,
                    [new ConditionData('moderator_warning_strikes', ($requestData['isHidden'] ? -1 : 1) * $model->strikes)]
                )
            );
        }

    }
}