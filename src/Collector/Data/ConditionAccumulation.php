<?php

namespace Xypp\Collector\Data;

use Carbon\Carbon;
use Xypp\Collector\Helper\SettingHelper;

class ConditionAccumulation
{
    public const CALCULATE_SUM = 1;
    public const CALCULATE_MAX = 2;
    public const CALCULATE_DAY_COUNT = 3;
    public array $data = [];
    public array $countedDays = [];
    public int $total = 0;
    public int $rest = 0;
    public int $maxValue = 0;
    public int $days = 0;
    public bool $dirty = false;
    protected bool $sorted = false;
    public string $updateFlag = "";
    public function __construct(?string $data = "{}")
    {
        if (!$data) {
            $data = "{}";
            $this->dirty = true;
        }
        $datas = json_decode($data, true);
        if (!is_array($datas))
            $datas = [];
        foreach ($datas as $key => $value) {
            if ($key == "all") {
                $this->total = $value;
            } else if ($key == "rest") {
                $this->rest = $value;
            } else if ($key == "flg") {
                $this->updateFlag = $value;
            } else if ($key == "max") {
                $this->maxValue = $value;
            } else if ($key == "days") {
                $this->days = $value;
            } else {
                $this->data[] = [
                    "date" => $key,
                    "value" => $value
                ];
                if ($value > 0)
                    $this->countedDays[$key] = true;
            }
        }
        $this->sort();
    }
    public function __tostring(): string
    {
        return $this->serialize();
    }
    public function serialize(): string
    {
        $keepDays = resolve(SettingHelper::class)->maxKeep();
        $data = [];
        $count = 0;
        $keys = [];
        foreach ($this->data as $value) {
            $key = $value["date"];
            if (isset($data[$key])) {
                $data[$key] += $value["value"];
            } else {
                $data[$key] = $value["value"];
                $keys[] = $value["date"];
                $count++;
            }

            if ($data[$key] > 0 && !isset($this->countedDays[$key])) {
                $this->countedDays[$key] = true;
                $this->days++;
            } else if ($data[$key] <= 0 && isset($this->countedDays[$key])) {
                unset($this->countedDays[$key]);
                $this->days--;
            }

            if ($data[$key] > $this->maxValue) {
                $this->maxValue = $data[$key];
            }
        }
        if ($count > $keepDays) {
            usort($keys, function ($a, $b) {
                return ($a > $b) ? -1 : 1;
            });
            while ($count > $keepDays) {
                $this->rest += $data[$keys[$count - 1]];
                unset($data[$keys[$count - 1]]);
                $count--;
            }
        }

        $data["all"] = $this->total;
        $data["rest"] = $this->rest;
        $data["flg"] = $this->updateFlag;
        $data["max"] = $this->maxValue;
        $data["days"] = $this->days;
        return json_encode($data);
    }
    protected function sort()
    {
        if ($this->sorted)
            return;
        usort($this->data, function ($a, $b) {
            return ($a["date"] > $b["date"]) ? -1 : 1;
        });
        $this->sorted = true;
    }
    public function getSpan(Carbon $ref, int $days, int $calculate = self::CALCULATE_SUM): int
    {
        $this->sort();
        $ret = 0;
        $begin = $ref->format("Ymd");
        if ($days > 1) {
            $begin = $ref->copy()->subDays($days - 1)->format("Ymd");
        }
        for ($i = 0; $i < count($this->data); $i++) {
            if ($this->data[$i]["date"] >= $begin) {
                switch ($calculate) {
                    case self::CALCULATE_MAX:
                        if ($this->data[$i]['value'] > $ret) {
                            $ret = $this->data[$i]['value'];
                        }
                        break;
                    case self::CALCULATE_DAY_COUNT:
                        if ($this->data[$i]['value'] > 0)
                            $ret++;
                        break;
                    default:
                        $ret += $this->data[$i]['value'];
                }
            } else {
                break;
            }
        }
        return $ret;
    }
    public function getToday(Carbon $ref, int $calculate = self::CALCULATE_SUM): int
    {
        return $this->getSpan($ref, 0);
    }
    public function getTotal(int $calculate = self::CALCULATE_SUM)
    {
        switch ($calculate) {
            case self::CALCULATE_MAX:
                return $this->maxValue;
            case self::CALCULATE_DAY_COUNT:
                return $this->days;
            default:
                return $this->total;
        }
    }
    public function updateValue(Carbon $ref, int $value, bool $relative = true)
    {
        $ref = $ref->copy()->format("Ymd");
        $this->dirty = true;
        if (!count($this->data)) {
            $this->data[] = [
                "date" => $ref,
                "value" => $value
            ];
            $this->total += $value;
        } elseif ($ref == $this->data[0]["date"]) {
            if (!$relative) {
                $this->total += $value - $this->data[0]["value"];
                $this->data[0]["value"] = $value;
            } else {
                $this->total += $value;
                $this->data[0]["value"] += $value;
            }
        } else {
            $this->total += $value;
            array_unshift($this->data, [
                "date" => $ref,
                "value" => $value
            ]);
            $this->sorted = false;
        }
    }
    public function updateFlag(?string $flag)
    {
        $this->dirty = true;
        $this->updateFlag = $flag ?? "";
    }
    public function clear()
    {
        $this->data = [];
        $this->total = 0;
        $this->dirty = true;
        $this->updateFlag = "";
        $this->sorted = true;
        $this->rest = 0;
    }
    public function resetTotal(int $total)
    {
        $this->clear();
        $this->total = $total;
        $this->rest = $total;
        $this->dirty = true;
    }
}