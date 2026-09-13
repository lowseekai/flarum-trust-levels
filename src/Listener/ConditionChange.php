<?php

namespace Xypp\TrustLevels\Listener;

use Flarum\Settings\SettingsRepositoryInterface;
use Xypp\Collector\Event\ConditionChange as ConditionChangeEvent;
use Xypp\TrustLevels\Utils\TrustLevelUtils;

class ConditionChange
{
    protected SettingsRepositoryInterface $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function __invoke(ConditionChangeEvent $event): void
    {
        if (filter_var(
            $this->settings->get('xypp-trust-levels.no-auto-update', false),
            FILTER_VALIDATE_BOOLEAN
        )) {
            return;
        }

        TrustLevelUtils::checkLevel($event->user, $event);
    }
}
