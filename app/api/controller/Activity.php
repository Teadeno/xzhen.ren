<?php

namespace app\api\controller;


use app\api\model\UserCheckpoint;
use app\api\model\UserResource;
use think\Db;

class Activity extends Base
{
    /*
     * 填写邀请码
     * */

    public function activityCode()
    {
        $this->post['code'] = 78523;
        if (!isset($this->post['code'])) {
            return $this->showReturnWithCode(1001);
        }

        $code = $this->post['code'];
        if ($code == 78523) {
            $update['lingshi'] = 1000000;
            $update['quality'] = 10000000000000;
            $update['prestige'] = 1000000;
            $update['top_lingshi'] = 1000000;
            $update['school_contribution'] = 1000000;
            $update['rmb'] = 1000000;
            $update['skill'] = 1000000;
            $update['wall_map_num'] = 10;
            $update['wall_map_wheel'] = 10;
            $update['realm_id'] = 70;

            $i = 1;
            while ($i <= 10) {
                $insert = [
                    'user_id' => $this->user_id,
                    'checkpoin_id' => $i,
                    'is_succeed' => 1
                ];
                UserCheckpoint::create($insert);
                $i++;
            }

            $l = UserResource::editMapData(['user_id' => $this->user_id], $update);
        } else {
            return $this->showReturn('邀请码不存在');
        }
        return $this->showReturnCode(0, ['status' => 1]);
    }
}