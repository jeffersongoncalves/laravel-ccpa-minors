<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\CcpaMinors\Enums\AssuranceMethod;
use JeffersonGoncalves\CcpaMinors\Facades\CcpaMinors;
use JeffersonGoncalves\CcpaMinors\Tests\Fixtures\Person;

beforeEach(function () {
    $this->person = Person::create(['name' => 'Emma']);
});

it('allows sale for unknown ages by default, unless configured otherwise', function () {
    expect(CcpaMinors::age($this->person))->toBeNull()
        ->and(CcpaMinors::allowsSaleOrSharing($this->person))->toBeTrue();

    config()->set('ccpa-minors.treat_unknown_as_minor', true);

    expect(CcpaMinors::allowsSaleOrSharing($this->person))->toBeFalse()
        ->and(CcpaMinors::needsParent($this->person))->toBeTrue();
});

it('lets a 13 to 15 year old opt in', function () {
    CcpaMinors::assure($this->person, 14, AssuranceMethod::StoreSignal);

    expect(CcpaMinors::allowsSaleOrSharing($this->person))->toBeFalse()
        ->and(CcpaMinors::needsParent($this->person))->toBeFalse()
        ->and(CcpaMinors::withoutOptIn()->pluck('id')->all())->toBe([$this->person->id]);

    CcpaMinors::optIn($this->person);

    expect(CcpaMinors::allowsSaleOrSharing($this->person))->toBeTrue()
        ->and(CcpaMinors::withoutOptIn())->toBeEmpty();

    CcpaMinors::revokeOptIn($this->person);

    expect(CcpaMinors::allowsSaleOrSharing($this->person))->toBeFalse();
});

it('needs a verified parent under 13', function () {
    $mother = Person::create(['name' => 'Anna']);
    CcpaMinors::assure($this->person, 10, AssuranceMethod::Document);

    expect(fn () => CcpaMinors::optIn($this->person))->toThrow(LogicException::class, 'under 13');

    $link = CcpaMinors::link($this->person, $mother, 'mother');
    expect(fn () => CcpaMinors::optIn($this->person, $link))->toThrow(LogicException::class);

    $link->verify();
    CcpaMinors::optIn($this->person, $link);

    expect(CcpaMinors::allowsSaleOrSharing($this->person))->toBeTrue()
        ->and(CcpaMinors::guardians($this->person)->pluck('name')->all())->toBe(['Anna']);
});

it('ignores a self opt-in once the consumer turns out to be under 13', function () {
    CcpaMinors::assure($this->person, 14, AssuranceMethod::SelfDeclaration);
    CcpaMinors::optIn($this->person);

    CcpaMinors::assure($this->person, 11, AssuranceMethod::Document);

    expect(CcpaMinors::hasOptedIn($this->person))->toBeFalse();
});

it('allows sale from 16 and prefers reliable results', function () {
    CcpaMinors::assure($this->person, 30, AssuranceMethod::SelfDeclaration);
    expect(CcpaMinors::allowsSaleOrSharing($this->person))->toBeTrue();

    CcpaMinors::assure($this->person, 15, AssuranceMethod::FacialEstimation);
    expect(CcpaMinors::age($this->person))->toBe(15)
        ->and(CcpaMinors::allowsSaleOrSharing($this->person))->toBeFalse();
});

it('gates adult routes and sale tags', function () {
    Route::get('/adult', fn () => 'ok')->middleware('ccpa.adult');
    $view = '@ccpaMinorSaleAllowed pixel @else none @endccpaMinorSaleAllowed';

    CcpaMinors::assure($this->person, 14, AssuranceMethod::Document);
    $this->actingAs($this->person)->get('/adult')->assertForbidden();
    expect(trim(Blade::render($view)))->toBe('none');

    $adult = Person::create(['name' => 'Anna']);
    CcpaMinors::assure($adult, 40, AssuranceMethod::Document);
    $this->actingAs($adult)->get('/adult')->assertOk();
    expect(trim(Blade::render($view)))->toBe('pixel');
});

it('reports minors without an opt-in', function () {
    CcpaMinors::assure($this->person, 12, AssuranceMethod::Guardian);

    $this->artisan('ccpa:minors')->expectsOutputToContain('1 user(s) under 16 without an opt-in.')->assertSuccessful();
});
