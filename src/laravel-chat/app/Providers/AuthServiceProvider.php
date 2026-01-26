<?php

namespace App\Providers;

use App\Models\Group;
use App\Policies\GroupPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Group::class => GroupPolicy::class,
    ];
}
