<?php

namespace JeffersonGoncalves\CcpaMinors;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JeffersonGoncalves\CcpaMinors\Enums\AssuranceMethod;
use JeffersonGoncalves\CcpaMinors\Models\AgeAssurance;
use JeffersonGoncalves\CcpaMinors\Models\GuardianLink;
use JeffersonGoncalves\CcpaMinors\Models\OptIn;
use LogicException;

class MinorsManager
{
    /**
     * Record the result of an age check. Pass the age, never the document.
     */
    public function assure(Model $subject, int $age, AssuranceMethod $method, ?string $provider = null): AgeAssurance
    {
        if ($age < 0 || $age > 130) {
            throw new InvalidArgumentException("Invalid age [{$age}].");
        }

        $validFor = config('ccpa-minors.valid_for.'.$method->value);

        return AgeAssurance::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'age' => $age,
            'method' => $method,
            'provider' => $provider,
            'assured_at' => now(),
            'expires_at' => $validFor === null ? null : now()->add(CarbonInterval::make((string) $validFor)),
        ]);
    }

    /**
     * Current age, or null when unknown. Reliable results outweigh self-declaration; among the
     * results that count, the youngest wins.
     */
    public function age(?Model $subject): ?int
    {
        return $this->decide($subject)['age'];
    }

    public function isVerifiedAdult(?Model $subject): bool
    {
        $decision = $this->decide($subject);

        return $decision['reliable'] && $decision['age'] !== null && $decision['age'] >= 18;
    }

    /** Known to be under 16 (or unknown, when treat_unknown_as_minor is on). */
    public function isUnderOptInAge(?Model $subject): bool
    {
        $age = $this->age($subject);

        return $age === null
            ? (bool) config('ccpa-minors.treat_unknown_as_minor', false)
            : $age < (int) config('ccpa-minors.opt_in_age', 16);
    }

    /** Under 13: only a parent or guardian can opt in (§1798.120(c)). */
    public function needsParent(?Model $subject): bool
    {
        $age = $this->age($subject);

        return $age === null
            ? (bool) config('ccpa-minors.treat_unknown_as_minor', false)
            : $age < (int) config('ccpa-minors.parent_age', 13);
    }

    /**
     * Opt in to the sale and sharing of a minor's personal information. From 13 to 15 the consumer
     * opts in; under 13 pass the verified guardian link of the parent who authorizes it.
     */
    public function optIn(Model $subject, ?GuardianLink $byParent = null): OptIn
    {
        if ($this->needsParent($subject)) {
            if ($byParent === null || ! $byParent->isActive() || ! $this->linkBelongsTo($byParent, $subject)) {
                throw new LogicException('Consumers under 13 need the opt-in of a verified parent or guardian (Cal. Civ. Code §1798.120(c)).');
            }
        }

        return OptIn::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'guardian_link_id' => $byParent?->getKey(),
            'opted_in_at' => now(),
        ]);
    }

    /** The consumer, or the parent, takes the opt-in back. */
    public function revokeOptIn(Model $subject): void
    {
        $this->optIns($subject)->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    public function hasOptedIn(Model $subject): bool
    {
        $optIn = $this->optIns($subject)->whereNull('revoked_at')->latest('id')->first();

        if ($optIn === null) {
            return false;
        }

        // A consumer's own opt-in no longer counts if they turn out to be under 13.
        return ! $this->needsParent($subject) || $optIn->byParent();
    }

    /**
     * Whether you may sell or share this consumer's personal information as far as their age goes:
     * 16 or older, or under 16 with a valid opt-in. Combine with laravel-ccpa-opt-out.
     */
    public function allowsSaleOrSharing(?Model $subject): bool
    {
        if ($subject === null) {
            return ! config('ccpa-minors.treat_unknown_as_minor', false);
        }

        return ! $this->isUnderOptInAge($subject) || $this->hasOptedIn($subject);
    }

    public function hasGuardian(Model $minor): bool
    {
        return $this->links($minor)->scopes('active')->exists();
    }

    /**
     * Link a minor to their parent or guardian. Verify it before relying on it.
     */
    public function link(Model $minor, Model $guardian, ?string $relationship = null, bool $verified = false): GuardianLink
    {
        return GuardianLink::create([
            'minor_type' => $minor->getMorphClass(),
            'minor_id' => $minor->getKey(),
            'guardian_type' => $guardian->getMorphClass(),
            'guardian_id' => $guardian->getKey(),
            'relationship' => $relationship,
            'verified_at' => $verified ? now() : null,
        ]);
    }

    /**
     * @return Collection<int, Model>
     */
    public function guardians(Model $minor): Collection
    {
        return $this->links($minor)->scopes('active')->with('guardian')->get()
            ->map(fn (GuardianLink $link): ?Model => $link->guardian)
            ->filter()
            ->values();
    }

    /**
     * Users known to be under 16 without a valid opt-in: you may not sell or share their information.
     *
     * @return Collection<int, Model>
     */
    public function withoutOptIn(): Collection
    {
        // ponytail: loads every subject with a valid result; paginate by subject if this grows large
        return AgeAssurance::query()->scopes('valid')->with('subject')->get()
            ->map(fn (AgeAssurance $assurance): ?Model => $assurance->subject)
            ->filter()
            ->unique(fn (Model $subject): string => $subject->getMorphClass().'#'.$subject->getKey())
            ->filter(fn (Model $subject): bool => $this->isUnderOptInAge($subject) && ! $this->hasOptedIn($subject))
            ->values();
    }

    /**
     * @return array{age: int|null, reliable: bool}
     */
    protected function decide(?Model $subject): array
    {
        if ($subject === null) {
            return ['age' => null, 'reliable' => false];
        }

        $valid = AgeAssurance::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->scopes('valid')
            ->get();

        $reliable = $valid->filter(fn (AgeAssurance $a): bool => $a->method->isReliable());
        $counted = $reliable->isNotEmpty() ? $reliable : $valid;
        $age = $counted->map(fn (AgeAssurance $a): int => $a->currentAge())->min();

        return ['age' => $age === null ? null : (int) $age, 'reliable' => $reliable->isNotEmpty()];
    }

    protected function linkBelongsTo(GuardianLink $link, Model $minor): bool
    {
        return $link->minor_type === $minor->getMorphClass() && (string) $link->minor_id === (string) $minor->getKey();
    }

    /**
     * @return Builder<GuardianLink>
     */
    protected function links(Model $minor): Builder
    {
        return GuardianLink::query()
            ->where('minor_type', $minor->getMorphClass())
            ->where('minor_id', $minor->getKey());
    }

    /**
     * @return Builder<OptIn>
     */
    protected function optIns(Model $subject): Builder
    {
        return OptIn::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }
}
