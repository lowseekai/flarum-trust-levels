<?php

namespace Xypp\TrustLevels;

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @property string $condition_name
 * @property int $trust_level_id
 */
class TrustLevelCondition extends AbstractModel
{
    protected $table = 'trust_level_condition';
    public $timestamps = false;

    protected $fillable = ['trust_level_id', 'condition_name'];

    public function trustLevel(): BelongsTo
    {
        return $this->belongsTo(TrustLevel::class, 'trust_level_id');
    }
}
