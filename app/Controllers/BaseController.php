<?php

namespace App\Controllers;

use App\Services\Auth;

/**
 * BaseController
 */

class BaseController
{
    protected $user;

    protected $renderer;

    /**
     * Get user and define TEMPLATE_PATH
     */
    public function __construct(\Slim\Container $container)
    {
        $this->user = Auth::getUser();
        $this->renderer = $container->get('renderer');

        if ($this->user->isLogin) {
            define('TEMPLATE_PATH', BASE_PATH . '/resources/views/' . $this->user->theme . '/');
        } else {
            define('TEMPLATE_PATH', BASE_PATH . '/resources/views/' . $_ENV['theme'] . '/');
        }
        
        $this->renderer->setTemplatePath(TEMPLATE_PATH);
        $this->renderer->addAttribute('user', $this->user);
    }

    /**
     * Response as encoded json
     */
    public function echoJson($response, $res)
    {
        return $response->getBody()->write(json_encode($res));
    }
}
