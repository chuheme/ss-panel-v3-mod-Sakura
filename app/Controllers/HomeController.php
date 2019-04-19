<?php

namespace App\Controllers;

use App\Models\InviteCode;
use App\Services\Config;
use App\Utils\Pay;
use App\Utils\TelegramProcess;

/**
 *  HomeController
 */
class HomeController extends BaseController
{
    public function index($request, $response, $args)
    {
        $this->renderer->render($response, 'index.phtml', [
            'user' => $this->user,
        ]);
    }

    public function code($request, $response, $args)
    {
        $codes = InviteCode::where('user_id', '=', '0')->take(10)->get();
        $this->renderer->render($response, 'code.phtml', [
            'user' => $this->user,
            'codes' => $codes,
        ]);
    }

    public function tos($request, $response, $args)
    {
        $this->renderer->render($response, 'tos.phtml', [
            'user' => $this->user,
        ]);
    }

    public function staff($request, $response, $args)
    {
        $this->renderer->render($response, 'staff.phtml', [
            'user' => $this->user,
        ]);
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
        $pics = scandir(BASE_PATH . '/public/theme/material/images/error/404/');

        if (count($pics) > 2) {
            $pic = $pics[rand(2, count($pics)-1)];
        } else {
            $pic = "4041.png";
        }

        $pic = '/theme/material/images/error/404/'.$pic;
        $response = $response->withStatus(404);
        $this->renderer->render($response, '404.phtml', [
            'user' => $this->user,
            'pic' => $pic,
        ]);
        return $response;
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
        $response = $response->withStatus(405);
        $this->renderer->render($response, '405.phtml', [
            'user' => $this->user,
            'pic' => $pic,
        ]);
        return $response;
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
        $response = $response->withStatus(500);
        $this->renderer->render($response, '500.phtml', [
            'user' => $this->user,
            'pic' => $pic,
        ]);
        return $response;
    }

    public function pay_callback($request, $response, $args)
    {
        Pay::callback($request);
    }
}
