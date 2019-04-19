<?php

namespace App\Services;

class View
{
    /**
     * generate a renderer
     * 
     * @return callable
     */
    public static function renderer()
    {
        $user = Auth::getUser();

        if ($user->isLogin) {
            $theme = $user->theme;
        } else {
            $theme = Config::get('theme');
        }
        
        $renderer = new \Slim\Views\PhpRenderer(BASE_PATH . '/resources/views/' . $theme . '/');
        $renderer->addAttribute('user', $user);
        return $renderer;
    }
}
