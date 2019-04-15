<?php

namespace App\Controllers;

use App\Services\Auth;

/**
 * BaseController
 */

class BaseController
{  
    /**
     * Get user and define TEMPLATE_PATH
     */
    public function __construct()
    {
        global $user;
        $user = Auth::getUser();

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
