<?php

namespace App\Services;

use App\Services\Auth\Cookie;
use App\Services\Token\DB;

class Factory
{
    public static function createAuth()
    {
        return new Cookie();
    }

    public static function createTokenStorage()
    {
        return new DB();
    }
}
