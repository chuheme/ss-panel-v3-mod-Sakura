<?php

namespace App\Controllers;

use App\Services\Auth;

/**
 * BaseController
 */

class BaseController
{
    public $user;
    
    /**
     * Get user and define TEMPLATE_PATH
     */
    public function __construct()
    {
        $user = Auth::getUser();
        $this->user = $user;

        if ($user->isLogin) {
            define('TEMPLATE_PATH', BASE_PATH . '/resources/views/' . $user->theme . '/');
        } else {
            define('TEMPLATE_PATH', BASE_PATH . '/resources/views/' . $_ENV['theme'] . '/');
        }
    }

    /**
     * Response as encoded json
     */
    public function echoJson($response, $res)
    {
        return $response->getBody()->write(json_encode($res));
    }
}
