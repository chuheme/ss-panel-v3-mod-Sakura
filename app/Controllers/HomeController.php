<?php

namespace App\Controllers;

use App\Models\InviteCode;
use App\Services\Auth;
use App\Services\Config;
use App\Utils\Pay;
use App\Utils\TelegramProcess;

/**
 *  HomeController
 */
class HomeController extends BaseController
{
    public function index()
    {
        $user = $this->user;
        require TEMPLATE_PATH . 'index.phtml';
    }

    public function code()
    {
        $user = $this->user;
        $codes = InviteCode::where('user_id', '=', '0')->take(10)->get();
        require TEMPLATE_PATH . 'code.phtml';
    }

    public function tos()
    {
        $user = $this->user;
        require TEMPLATE_PATH . 'tos.phtml';
    }

    public function staff()
    {
        $user = $this->user;
        require TEMPLATE_PATH . 'staff.phtml';
    }

    public function telegram($request, $response, $args)
    {
        $token = "";
        if (isset($request->getQueryParams()["token"])) {
            $token = $request->getQueryParams()["token"];
        }
        
        if ($token == Config::get('telegram_request_token')) {
            TelegramProcess::process();
        } else {
            echo("不正确请求！");
        }
    }

    public function page404($request, $response, $args)
    {
        $user = $this->user;
        $pics = scandir(BASE_PATH . '/public/theme/material/images/error/404/');

        if (count($pics)>2) {
            $pic=$pics[rand(2, count($pics)-1)];
        } else {
            $pic="4041.png";
        }

        $pic = '/theme/material/images/error/404/'.$pic;
        header('HTTP/2 404 Not Found');
        require TEMPLATE_PATH . '404.phtml';
        exit();
    }

    public function page405($request, $response, $args)
    {
        $user = $this->user;
        $pics = scandir(BASE_PATH . '/public/theme/material/images/error/405/');
        if (count($pics)>2) {
            $pic=$pics[rand(2, count($pics)-1)];
        } else {
            $pic="4051.png";
        }

        $pic = '/theme/material/images/error/405/'.$pic;
        header('HTTP/2 405 Method Not Allowed');
        require TEMPLATE_PATH . '405.phtml';
        exit();
    }

    public function page500($request, $response, $args)
    {
        $user = $this->user;
        $pics = scandir(BASE_PATH . '/public/theme/material/images/error/500/');
        if (count($pics)>2) {
            $pic=$pics[rand(2, count($pics)-1)];
        } else {
            $pic="5001.png";
        }

        $pic = '/theme/material/images/error/500/'.$pic;
        header('HTTP/2 500 Internal Server Error');
        require TEMPLATE_PATH . '500.phtml';
        exit();
    }

    public function pay_callback($request, $response, $args)
    {
        Pay::callback($request);
    }
}
