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
        //人物升级
        $this->user_id = 251;
        //学习各各门派功法
        $school = Db::name('school')->where('school_id', 1)->select();
        foreach ($school as $value) {
            //获取该门派的所有可学习功法
            $esoterica = Db::name('esoterica')->where('steps', 1)->where('school_id', $value['school_id'])->where('level', $value['level'])
                ->select();
            foreach ($esoterica as $v) {
                $this->post['esoterica_id'] = $v['esoterica_id'];
                Loader::controller('school')->studyeEsoterica();
            }
        }
        
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