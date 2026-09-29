<?php

namespace Xypp\TrustLevels;

use Flarum\Database\AbstractModel;
use Flarum\Group\Group;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string $icon
 * @property string $type
 * @property int $level
 * @property array $conditions
 * @property int|null $group_id
 * @property bool $allow_downgrade
 * @property int $downgrade_grace_days
 * @property bool $manual_only
 */
class TrustLevel extends AbstractModel
{
    private const PRESET_ICONS = [
        'fas fa-user',
        'fas fa-user-plus',
        'fas fa-seedling',
        'fas fa-shield-halved',
        'fas fa-medal',
        'fas fa-star',
        'fas fa-crown',
        'fas fa-gem',
        'fas fa-trophy',
        'fas fa-layer-group',
        'fas fa-fire',
        'fas fa-users',
        'fas fa-handshake',
    ];

    protected $table = 'trust_levels';

    protected $fillable = [
        'name',
        'icon',
        'conditions',
        'group_id',
        'level',
        'allow_downgrade',
        'downgrade_grace_days',
        'manual_only',
    ];

    /**
     * The group attached to users before this level was saved.
     *
     * Flarum 2 calls a resource's saved hook after Eloquent has synced its
     * original attributes, so getRawOriginal('group_id') is not reliable there.
     */
    public ?int $groupIdBeforeSave = null;

    protected $casts = [
        'conditions' => 'array',
        'group_id' => 'integer',
        'level' => 'integer',
        'allow_downgrade' => 'boolean',
        'downgrade_grace_days' => 'integer',
        'manual_only' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'trust_level', 'level');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function nextLevel(): ?self
    {
        return static::query()
            ->where('level', '>', (int) $this->level)
            ->orderBy('level')
            ->first();
    }

    public function previousLevel(): ?self
    {
        return static::query()
            ->where('level', '<', (int) $this->level)
            ->orderByDesc('level')
            ->first();
    }

    public static function presetIcons(): array
    {
        return self::PRESET_ICONS;
    }

    public static function defaultIconForLevel(int $level): string
    {
        $icons = self::PRESET_ICONS;

        return $icons[max(0, $level) % count($icons)];
    }

    public static function normalizeIcon(?string $icon, int $level): string
    {
        $icon = trim((string) $icon);

        return in_array($icon, self::PRESET_ICONS, true)
            ? $icon
            : self::defaultIconForLevel($level);
    }
}
