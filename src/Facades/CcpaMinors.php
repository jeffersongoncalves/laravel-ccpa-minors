<?php

namespace JeffersonGoncalves\CcpaMinors\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\CcpaMinors\MinorsManager;

/**
 * @method static \JeffersonGoncalves\CcpaMinors\Models\AgeAssurance assure(\Illuminate\Database\Eloquent\Model $subject, int $age, \JeffersonGoncalves\CcpaMinors\Enums\AssuranceMethod $method, ?string $provider = null)
 * @method static int|null age(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool isVerifiedAdult(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool isUnderOptInAge(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool needsParent(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static \JeffersonGoncalves\CcpaMinors\Models\OptIn optIn(\Illuminate\Database\Eloquent\Model $subject, ?\JeffersonGoncalves\CcpaMinors\Models\GuardianLink $byParent = null)
 * @method static void revokeOptIn(\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool hasOptedIn(\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool allowsSaleOrSharing(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool hasGuardian(\Illuminate\Database\Eloquent\Model $minor)
 * @method static \JeffersonGoncalves\CcpaMinors\Models\GuardianLink link(\Illuminate\Database\Eloquent\Model $minor, \Illuminate\Database\Eloquent\Model $guardian, ?string $relationship = null, bool $verified = false)
 * @method static \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> guardians(\Illuminate\Database\Eloquent\Model $minor)
 * @method static \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> withoutOptIn()
 *
 * @see MinorsManager
 */
class CcpaMinors extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MinorsManager::class;
    }
}
