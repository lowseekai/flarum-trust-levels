<?php

namespace Xypp\Collector\Helper;

use Flarum\Settings\SettingsRepositoryInterface;


class SettingHelper
{
    protected SettingsRepositoryInterface $settings;
    protected ?array $disabled = null;
    public function __construct(
        SettingsRepositoryInterface $settings
    ) {
        $this->settings = $settings;
    }
    public function load()
    {
        if ($this->disabled === null) {
            $this->disabled = json_decode($this->settings->get("xypp.collector.emit_control") ?? "[]", true);
        }
        return $this->disabled;
    }
    public function enable(string $fromType, string $conditionName)
    {
        $dat = $this->load();
        if (!isset($dat[$fromType]))
            return true;
        return !(isset($dat[$fromType][$conditionName]) && $dat[$fromType][$conditionName]);
    }
    public function maxKeep()
    {
        // Trust-level rules can use 60/100-day rolling windows. Keep enough
        // daily data for those checks even if an older setting was 30 days.
        return max(100, (int) ($this->settings->get("xypp.collector.max_keep") ?? 100));
    }
    public function globalChangeCustom()
    {
        return $this->settings->get("xypp.collector.custom-global-update") ?? false;
    }
    public function normalChangeCustom()
    {
        return $this->settings->get("xypp.collector.custom-normal-update") ?? false;
    }
    public function useCustom()
    {
        return $this->settings->get("xypp.collector.use_custom") ?? false;
    }
    public function autoUpdate()
    {
        return $this->settings->get("xypp.collector.auto_update") ?? false;
    }
    public function autoUpdateHour()
    {
        return $this->settings->get("xypp.collector.auto_update_hour") ?? 0;
    }
}
