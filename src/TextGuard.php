<?php

namespace Overtrue\TextGuard;

use Illuminate\Support\Facades\Facade;

class TextGuard extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Overtrue\TextGuard\TextGuardManager::class;
    }
}
