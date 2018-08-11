<?php

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Loader;

/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 15:23
 */
class Index extends \app\base\controller\Base
{
    
    public function upgrade()
    {
        $post = $this->getRequestPost($this->request->post('data'));
        
        $info = Db::name('upgrade')->order('create_time desc')->find();
        switch ($info['version'] <=> $post['element']['v']) {
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
                return $this->showReturn( '版本信息不正确');
                break;
        }
        $info = null;
        
        return $this->showReturnCode(0, $data);
    }
}