<?php

namespace Xypp\TrustLevels\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Xypp\TrustLevels\TrustLevel;

class TrustLevelChangeNotification implements BlueprintInterface, AlertableInterface
{
    public User $user;
    public TrustLevel $trustLevel;
    public array $data;

    public function __construct(TrustLevel $trustLevel, User $user, ?TrustLevel $fromLevel)
    {
        $this->user = $user;
        $this->trustLevel = $trustLevel;
        $this->data = [
            "time" => time(),
        ];
        if ($fromLevel) {
            $this->data["from"] = $fromLevel->name;
            $this->data["from_level"] = $fromLevel->level;
        }
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->trustLevel;
    }

    public function getFromUser(): ?User
    {
        return $this->user;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public static function getType(): string
    {
        return 'trust_level_change';
    }

    public static function getSubjectModel(): string
    {
        return TrustLevel::class;
    }
}
