<?php

namespace Jtif\SsoJtif\Facades;

use Illuminate\Support\Facades\Facade;

class OauthInformatika extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'oauth-informatika';
    }
}