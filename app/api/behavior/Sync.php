<?php

namespace app\api\behavior;


use app\api\model\Award;
use app\api\model\Mission;
use app\api\model\UserAttribute;
use app\api\model\UserDynasty;
use app\api\model\UserResource;
use app\api\controller\Base as B;
use app\base\model\Base;
use think\Hook;

class Sync
{
    /**
     * 修为值同步
     * $param arrar 包含最后一次修改时间，增长速度, 增加用户,当前修为值
     * @return bool
     */
    public function syncQuality(array &$params)
    {
        if (!isset($params['user_id'])) {
            return false;
        }
        if (!isset($params['quality_edit_time']) || !isset($params['practice_speed']) || !isset($params['quality'])) {
            $data = UserResource::findMap(['user_id' => $params['user_id']], 'user_id, quality, quality_edit_time')->toArray();
            $data = array_merge($data, UserAttribute::findMap(['user_id' => $params['user_id']], 'practice_speed')->toArray());
        }
        $time = time();
        $num = floor(($time - $data['quality_edit_time']) / 5);
        if ($num < 1) {
            return false;
        }
        $quality = $num * $data['practice_speed'];
        //资源记录
        $result = UserResource::where('user_id', $params['user_id'])->update([
            'quality' => $quality + $data['quality'],
            'quality_edit_time' => $time
        ]);
        //修为改变比较频繁  暂时不记录
        /*        $data[] = [
                    'user_id' => $params['user_id'],
                    'type' => array_search('quality', Base::getType('resource_log')),
                    'value' =>$quality,
                    'describe' => '自动增加'
                ];
                Hook::listen('resource_log', $data);*/
        $data = null;
        if (!$result) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 门派任务同步
     * $param arrar 包含最后一次修改时间，任务id, 增加用户,灵石数，声望数
     * @return bool
     */
    public function syncMission(array &$params)
    {
        if (!isset($params['user_id'])) {
            return false;
        }
        if (!isset($params['execut_mission_time']) || !isset($params['execut_mission']) || !isset($params['lingshi']) || !isset($params['school_contribution'])) {
            $params = UserResource::findMap(['user_id' => $params['user_id']], 'user_id, execut_mission_time, execut_mission, lingshi, school_contribution')->toArray();
        }
        $time = time();
        $num = floor(($time - $params['execut_mission_time']) / 600);
        $resource_update['execut_mission_time'] = $time - (($time - $params['execut_mission_time']) - $num * 600);
        if ($num <= 0 || empty($params['execut_mission'])) {
            return false;
        }
        //获取任务奖励
        $awardId = Mission::findMap(['mission_id' => $params['execut_mission']], 'award_id')->toArray()['award_id'];

        $award = Award::getListByMap(['award_id' => $awardId]);
        $award_type = B::getType('award');
        foreach ($award as $key => $value) {
            $type = $award_type[$value['type']];
            $resource_update[$type] = $params[$type] + $value['value'];
            //资源变化记录
            $data[] = [
                'user_id' => $params['user_id'],
                'type' => array_search($type, B::getType('resource_log')),
                'value' => $value['value'],
                'describe' => '任务'
            ];
        }

        $result = UserResource::where('user_id', $params['user_id'])->update($resource_update);
//        Hook::listen('resource_log', $data);
        //资源记录
        $params = null;
        if (!$result) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 王朝资源同步
     * $param arrar 包含最后一次修改时间，任务id, 增加用户,灵石数，声望数
     * @return bool
     */
    public function syncDynasty(array &$params)
    {
        if (!isset($params['user_id'])) {
            return false;
        }

        if (!isset($params['s_population']) || !isset($params['s_food']) || !isset($params['s_mineral']) || !isset($params['s_grass']) || !isset($params['s_wood']) || !isset($params['population']) || !isset($params['food']) || !isset($params['mineral']) || !isset($params['grass']) || !isset($params['wood']) || !isset($params['sync_time'])) {
            $params = UserDynasty::findMap(['user_id' => $params['user_id']])->toArray();
        }
        $time = time();
        $num = floor(($time - $params['sync_time']) / 10);
        if ($num <= 0) {
            return false;
        }

        $dynasty_update['population'] = $params['population'] + $params['s_population'] * $num;
        $dynasty_update['mineral'] = $params['mineral'] + $params['s_mineral'] * $num;
        $dynasty_update['grass'] = $params['grass'] + $params['s_grass'] * $num;
        $dynasty_update['wood'] = $params['wood'] + $params['s_wood'] * $num;
        $dynasty_update['food'] = $params['food'] + $params['s_food'] * $num;
        foreach ($dynasty_update as $key => $value) {
            $type = 'max_' . $key;
            $dynasty_update[$key] = $value > $params[$type] ? $params[$key] : $value;
        }
        $dynasty_update['sync_time'] = $time;

        $result = UserDynasty::editMapData(['user_id' => $params['user_id']], $dynasty_update);

        //埋点   记录暂定
        $params = null;
        if (!$result) {
            return false;
        } else {
            return true;
        }
    }

}