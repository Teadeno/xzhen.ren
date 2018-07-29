<?php

namespace app\api\controller;

use think\Db;
use think\Loader;

/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 15:23
 */
class Index extends Base
{

    public function index()
    {

    }

    public function upgrade()
    {
        $this->post = [
            'header' => [
                'api' => 'upgrade',
                'source' => 'ios',
                'version' => 1.0,
                'imei' => '12534352323'
            ],
            'element' => [
                'v' => 1.0
            ]
        ];
        $source = $this->post['header']['source'];
        $version = 2;
        $info = Db::name('upgrade')->where('source', strtoupper($source))->order('create_time desc')->find();
        switch ($info['version'] <=> $version) {
            case 0:
                $data = [
                    'is_up' => 0,
                    'app_url' => "",
                    'updatemsg' => ""
                ];
                break;
            case 1:
                $data = [
                    'is_up' => $info['is_up'],
                    'app_url' => $info['app_url'],
                    'updatemsg' => $info['updetemsg']
                ];
                break;
            case -1:
                return toJson(100, '版本信息不正确');
                break;
        }
        $info = null;

        return $this->showReturnCode(0, $data);
    }
}