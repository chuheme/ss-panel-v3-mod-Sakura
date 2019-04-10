<?php

namespace App\Controllers;

use App\Services\Auth;

/**
 * BaseController
 */

class BaseController
{
    public $user;
    
    public function __construct()
    {
        $this->user =  Auth::getUser();
    }

}
