<?php

namespace Xypp\Collector\Api\Controller;

use Flarum\Locale\Translator;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xypp\Collector\Custom\CustomConditionDefinition;
use Xypp\Collector\Helper\ConditionHelper;
use Xypp\Collector\Helper\RewardHelper;

class GetCollectorDefinitionController implements RequestHandlerInterface
{
    protected ConditionHelper $conditionHelper;
    protected RewardHelper $rewardHelper;
    protected Translator $translator;
    public function __construct(ConditionHelper $conditionHelper, RewardHelper $rewardHelper, Translator $translator)
    {
        $this->conditionHelper = $conditionHelper;
        $this->rewardHelper = $rewardHelper;
        $this->translator = $translator;
    }
    protected function optionalTranslate(string $key, array $params = [])
    {
        if (str_contains($key, '.')) {
            return $this->translator->trans($key, $params);
        }

        return $key;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse([
            'conditions' => array_merge(
                array_map(
                    function ($conditionName) {
                        $definition = $this->conditionHelper->getConditionDefinition($conditionName);

                        $result = [
                            'global' => false,
                            'key' => $conditionName,
                            'trans' => $this->optionalTranslate($definition->translateKey),
                            'abs' => $definition->accumulateAbsolute,
                            'manual' => $definition->needManualUpdate,
                            'update' => $definition->accumulateUpdate,
                        ];

                        if ($definition instanceof CustomConditionDefinition) {
                            $result['evaluation'] = $definition->evaluation;
                        }

                        return $result;
                    },
                    $this->conditionHelper->getAllConditionName()
                ),
                array_map(
                    function ($conditionName) {
                        $definition = $this->conditionHelper->getGlobalConditionDefinition($conditionName);

                        return [
                            'global' => true,
                            'key' => $conditionName,
                            'trans' => $this->optionalTranslate($definition->translateKey),
                            'abs' => $definition->accumulateAbsolute,
                            'manual' => $definition->needManualUpdate,
                            'update' => $definition->accumulateUpdate,
                        ];
                    },
                    $this->conditionHelper->getGlobalConditionName()
                )
            ),
            'rewards' => array_map(
                function ($rewardName) {
                    $definition = $this->rewardHelper->getRewardDefinition($rewardName);

                    return [
                        'key' => $rewardName,
                        'trans' => $this->translator->trans($definition->translateKey),
                    ];
                },
                $this->rewardHelper->getAllRewardNames()
            )
        ]);
    }
}
