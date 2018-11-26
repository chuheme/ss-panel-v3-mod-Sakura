<?php


namespace App\Middleware;

use App\Services\Config;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use App\Services\Factory;
use App\Utils\Helper;
use App\Models\Node;

class Mod_Mu
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $params = $request->getQueryParams();
        if ($params['key'] == null) {
            $res['ret'] = 0;
            $res['data'] = "key is null";
            $response->getBody()->write(json_encode($res));

            return $response;
        }elseif($params['key'] != $_ENV['muKey']) {
            $res['ret'] = 0;
            $res['data'] = "muKey is wrong!!!";
            $response->getBody()->write(json_encode($res));
            return $response;
        }

        $node = Node::find($params['id'])->first();

        $response = $next($request, $response);
        return $response;
    }
}
