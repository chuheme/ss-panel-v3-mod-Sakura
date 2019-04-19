<?php

namespace App\Controllers\Admin;

use App\Models\Auto;
use App\Controllers\BaseController;

use Ozdemir\Datatables\Datatables;
use App\Utils\DatatablesHelper;

class autoController extends BaseController
{
    public function index($request, $response, $args)
    {
        $table_config['total_column'] = array("id" => "ID",
                              "datetime" => "时间", "type" => "类型", "value" => "内容");
        $table_config['default_show_column'] = array("op", "id",
                                  "datetime", "type", "value");
        $table_config['ajax_url'] = 'auto/ajax';
        $this->renderer->render($response, 'admin/auto/index.phtml', [
            'table_config' => $table_config,
        ]);
    }

    public function create($request, $response, $args)
    {
        $this->renderer->render($response, 'admin/auto/create.phtml');
    }

    public function add($request, $response, $args)
    {
        $auto = new Auto();
        $auto->datetime =  time();
        $auto->value =  $request->getParam('content');
        $auto->sign =  $request->getParam('sign');
        $auto->type =  1;

        if (!$auto->save()) {
            $rs['ret'] = 0;
            $rs['msg'] = "添加失败";
            return $response->getBody()->write(json_encode($rs));
        }
        $rs['ret'] = 1;
        $rs['msg'] = "添加成功";
        return $response->getBody()->write(json_encode($rs));
    }


    public function delete($request, $response, $args)
    {
        $id = $request->getParam('id');
        $auto = Auto::find($id);
        if (!$auto->delete()) {
            $rs['ret'] = 0;
            $rs['msg'] = "删除失败";
            return $response->getBody()->write(json_encode($rs));
        }
        $rs['ret'] = 1;
        $rs['msg'] = "删除成功";
        return $response->getBody()->write(json_encode($rs));
    }

    public function ajax($request, $response, $args)
    {
        $datatables = new Datatables(new DatatablesHelper());
        $datatables->query('Select id,datetime,type,value from auto');

        $datatables->edit('datetime', function ($data) {
            return date('Y-m-d H:i:s', $data['datetime']);
        });

        $datatables->edit('type', function ($data) {
            return $data['type'] == 1 ? '命令下发' : '命令被执行';
        });

        $body = $response->getBody();
        $body->write($datatables->generate());
    }
}
