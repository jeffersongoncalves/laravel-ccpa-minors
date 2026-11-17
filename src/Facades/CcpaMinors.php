<?php

namespace JeffersonGoncalves\CcpaMinors\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\CcpaMinors\CcpaMinors
 */
class CcpaMinors extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-ccpa-minors';
    }
}
