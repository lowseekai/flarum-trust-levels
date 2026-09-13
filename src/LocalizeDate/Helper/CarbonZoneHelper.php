<?php

namespace Xypp\LocalizeDate\Helper;

use Carbon\Carbon;
use Flarum\Settings\SettingsRepositoryInterface;

class CarbonZoneHelper
{
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
    }

    public function z(Carbon $carbon): Carbon
    {
        return $carbon->setTimezone(
            $this->settings->get('xypp.localize-date.timezone') ?: 'UTC'
        );
    }

    public function now(): Carbon
    {
        return $this->z(Carbon::now());
    }
}
