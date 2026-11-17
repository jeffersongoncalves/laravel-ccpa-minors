<?php

namespace JeffersonGoncalves\CcpaMinors\Tests\Fixtures;

use Illuminate\Foundation\Auth\User;

class Person extends User
{
    protected $table = 'people';

    protected $guarded = [];

    public $timestamps = false;
}
