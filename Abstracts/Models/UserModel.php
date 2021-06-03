<?php

namespace Apiato\Core\Abstracts\Models;

use Apiato\Core\Traits\FactoryLocatorTrait;
use Apiato\Core\Traits\HashIdTrait;
use Apiato\Core\Traits\HasResourceKeyTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as LaravelAuthenticatableUser;
use Spatie\Permission\Traits\HasRoles;

abstract class UserModel extends LaravelAuthenticatableUser
{
    use FactoryLocatorTrait, HasFactory {
        FactoryLocatorTrait::newFactory insteadof HasFactory;
    }
    use HashIdTrait;
    use HasResourceKeyTrait;
    use HasRoles;
}
