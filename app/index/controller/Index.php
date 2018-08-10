<?php

namespace app\index\controller;

use think\Controller;
use think\Hook;
use app\api\model\UserResource;
use app\api\model\UserAttribute;
use app\api\model\Realm;
use app\api\model\UserLog;

class Index extends Controller
{
    public function index()
    {
        $map = ['user_id' => 188];

        $user_resource = UserResource::findMap($map, 'quality, realm_id, create_time');
        $user_attribute = UserAttribute::findMap($map, 'practice_speed');
        //当前境界
        $realm = Realm::findMap(['realm_id' => $user_resource->realm_id], 'name, grade,steps, f_id');

        //下一境界
        $price = Realm::findMap(['realm_id' => $realm->f_id], 'price')->price ? 0 : Realm::findMap(['realm_id' => $realm->f_id], 'price')->price;
        $user_log = UserLog::getListByMap($map, 'time, content, user_id', 'create_time desc', '', 5, ['username']);
//        $quality = $this->typeConvert((int)$user_resource->quality);
        //判断是否第一次进入
        if ($realm->grade == 0 && $realm->steps == 1) $one = 1;
        $list = [
            'one' => isset($one) ? $one : 0,
            'quality' => (int)$user_resource->quality,
            'practice_speed' => $user_attribute->practice_speed,
            'realm' => $realm->name,
            'realm_steps' => $realm->steps,
            'realm_price' => $price,
            'time' => 1,
            'user_log' => $user_log
        ];
        var_dump($list);
//
//        $n = file_get_contents('run.log');
//        $n++;
//        file_put_contents('run.log',$n);

    }
}
