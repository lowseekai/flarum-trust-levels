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
 */
class TrustLevel extends AbstractModel
{
    protected $table = 'trust_levels';

    protected $fillable = ['name', 'icon', "conditions", "group_id", "level"];

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
        return static::query()->where('level', (int) $this->level + 1)->first();
    }

    public function previousLevel(): ?self
    {
        return static::query()->where('level', (int) $this->level - 1)->first();
    }
}
