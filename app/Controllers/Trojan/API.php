<?php

namespace App\Controllers\Trojan;

use App\Models\User;
use App\Models\Node;

class API extends \App\Controllers\BaseController
{
    /**
     * get user connect info for Trojan
     */
    public function getUser($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $node_id = $params['id'];

        $node = Node::find($node_id);
        if ($node == null) {
            $res = [
                "ret" => 0,
                'data' => 'no such node',
            ];
            return $this->echoJson($response, $res);
        }
        $node->node_heartbeat = time();
        $node->save();

        if ($node->node_group!=0) {
            $users_raw = User::where(
                function ($query) use ($node){
                    $query->where(
                      function ($query1) use ($node){
                          $query1->where("class", ">=", $node->node_class)
                              ->where("node_group", "=", $node->node_group);
                      }
                    )->orwhere('is_admin', 1);
                }
            )
            ->where("enable", 1)->where("expire_in", ">", date("Y-m-d H:i:s"))->whereRaw('u + d < transfer_enable')->get();
        } else {
            $users_raw = User::where(
                function ($query) use ($node){
                    $query->where(
                      function ($query1) use ($node){
                          $query1->where("class", ">=", $node->node_class);
                      }
                    )->orwhere('is_admin', 1);
                }
            )->where("enable", 1)->where("expire_in", ">", date("Y-m-d H:i:s"))->whereRaw('u + d < transfer_enable')->get();
        }
        if ($node->node_bandwidth_limit != 0) {
            if ($node->node_bandwidth_limit < $node->node_bandwidth) {
                $users = null;
                $res = [
                    "ret" => 1,
                    "data" => $users
                ];
                return $this->echoJson($response, $res);
            }
        }
        $users = array();
        foreach ($users_raw as $user_raw) {
            $user = [
                'id' => $user_raw->id,
                'password' => $user_raw->id . ':' . $user_raw->passwd,
                'quota' => $user_raw->transfer_enable - ($user_raw->u - $user_raw->d),
            ];
            array_push($users, $user);
        }
        $res = [
            "ret" => 1,
            "data" => $users
        ];
        return $this->echoJson($response, $res);
    }

    /**
     * update Trojan data
     */
    public function updateInfo($request, $response, $args)
    {
        $params = $request->getQueryParams();

        $data = $request->getParsedBody();
        $this_time_total_bandwidth = 0;
        $node_id = $params['id'];
        $node = Node::find($node_id);
        if ($node == null) {
            $res = [
                "ret" => 0
            ];
            return $this->echoJson($response, $res);
        }
        if (count($data) > 0) {
            foreach ($data as $log) {
                $u = $log['upload'];
                $d = $log['download'];
                $id = $log['id'];
                $user = User::find($id);

                if($user == NULL) {
                    continue;
                }

                $user->t = time();
                $user->u += $u * $node->traffic_rate;
                $user->d += $d * $node->traffic_rate;
                $this_time_total_bandwidth += $u + $d;
                if (!$user->save()) {
                    $res = [
                        "ret" => 0,
                        "data" => "update failed",
                    ];
                    return $this->echoJson($response, $res);
                }

                // log
                $traffic = new \App\Models\TrafficLog();
                $traffic->user_id = $id;
                $traffic->u = $u;
                $traffic->d = $d;
                $traffic->node_id = $node_id;
                $traffic->rate = $node->traffic_rate;
                $traffic->traffic = \App\Utils\Tools::flowAutoShow(($u + $d) * $node->traffic_rate);
                $traffic->log_time = time();
                $traffic->save();
            }
        }

        $node->node_bandwidth += $this_time_total_bandwidth;
        $node->save();

        $online_log = new \App\Models\NodeOnlineLog();
        $online_log->node_id = $node_id;
        $online_log->online_user = count($data);
        $online_log->log_time = time();
        $online_log->save();

        $res = [
            "ret" => 1,
            "data" => "ok",
        ];
        return $this->echoJson($response, $res);
    }
}
