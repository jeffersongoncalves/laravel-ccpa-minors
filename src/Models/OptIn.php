<?php

namespace JeffersonGoncalves\CcpaMinors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Affirmative authorization to sell or share the personal information of a consumer under 16
 * (Cal. Civ. Code §1798.120(c)): from the consumer at 13 to 15, from a parent or guardian under 13.
 *
 * @property string $subject_type
 * @property int|string $subject_id
 * @property int|null $guardian_link_id
 * @property Carbon $opted_in_at
 * @property Carbon|null $revoked_at
 * @property-read Model|null $subject
 * @property-read GuardianLink|null $guardianLink
 */
class OptIn extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('ccpa-minors.tables.opt_ins', 'ccpa_minor_opt_ins');
    }

    protected function casts(): array
    {
        return [
            'opted_in_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<GuardianLink, $this>
     */
    public function guardianLink(): BelongsTo
    {
        return $this->belongsTo(GuardianLink::class);
    }

    public function byParent(): bool
    {
        return $this->guardian_link_id !== null;
    }
}
