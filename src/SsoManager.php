<?php

namespace Jtif\SsoJtif;

use Jtif\SsoJtif\Http\Controllers\SsoAuthController;

class SsoManager
{
    /**
     * Mengembalikan instance driver autentikasi.
     */
    public function driver($driver = 'sso')
    {
        return new SsoAuthController();
    }
}