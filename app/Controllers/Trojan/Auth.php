<?php

namespace App\Controllers\Trojan;

use App\Services\Config;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use App\Services\Factory;
use App\Utils\Helper;
use App\Models\Node;

class Auth
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $params = $request->getQueryParams();
        if ($params['key'] == null) {
            $res['ret'] = 0;
            $res['data'] = "key is null";
            $response->getBody()->write(json_encode($res));

            return $response;
        } elseif ($params['key'] != Config::get('muKey')) {
            $res['ret'] = 0;
            $res['data'] = "key is wrong!!!";
            $response->getBody()->write(json_encode($res));
            return $response;
        }

        $node = Node::find($params['id']);

        $response = $next($request, $response);
        return $response;
    }
}
