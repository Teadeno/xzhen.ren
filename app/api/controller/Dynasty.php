<?php

namespace app\api\controller;


use app\api\model\Building;
use app\api\model\Consumption;
use app\api\model\Tactical;
use app\api\model\UserAttribute;
use app\api\model\UserCheckpoint;
use app\api\model\UserDynasty;
use app\api\model\UserResource;
use think\Db;
use think\Hook;

class Dynasty extends Base
{

    /**
     * 王朝资源
     */
    public function getUserDynasty()
    {
        //查询该玩家是否存在王朝数据
        $map = ['user_id' => $this->user_id];
        if (!$dynasty = UserDynasty::findMap($map)) {
            //不存在判断用户是否达洞虚
//            $maps = array_merge($map, ['checkpoin_id' => 5]);
            if (UserResource::findMap($map, 'realm_id')->realm_id <= 80) {
                return $this->showReturn('达到洞虚境方可开启');
            }
            //通关 增加王朝资源
            $insert = [
                'user_id' => $this->user_id,
                'population' => 5,
                'building_id' => [1, 2, 3, 4, 5],
                's_population' => 1,
                's_food' => 1,
                's_mineral' => 1,
                's_grass' => 1,
                's_wood' => 2,
                'max_population' => 375,
                'max_food' => 75,
                'max_mineral' => 75,
                'max_grass' => 75,
                'max_wood' => 156,

                'sync_time' => time(),
            ];
            $dynasty = UserDynasty::create($insert);
        }

        Hook::listen('sync_dynasty', $map);
        $dynasty = UserDynasty::findMap(['user_id' => $this->user_id]);
        //达到增加  玩家数据初始化 人口初始5，其他初始0  未达到结束返回状态码
        //存在数据  同步王朝资源  Hook::listen('sync_dynasty', $data);
        //返回数据

        return $this->showReturnCode(0, $dynasty);
    }

    /**
     * 建筑资源
     */
    public function buildingList()
    {
        //根据用户id获取用户建筑id情况
        // 查询对应的建筑id
        //将建筑情况和  安置人口  组装返回
        $map = ['user_id' => $this->user_id];
        Hook::listen('sync_dynasty', $map);
        $dynasty = UserDynasty::findMap(['user_id' => $this->user_id])->toArray();
        $data = Building::getListByMap(['building_id' => ['in', $dynasty['building_id']]]);
        foreach ($data as &$value) {
            $value['value'] = $dynasty[$this->getType('building')[$value['type']]];
        }
        return $this->showReturnCode(0, $data);
    }

    /**
     * 建筑升级
     */
    public function studyBuilding()
    {

        if (!isset($this->post['building_id'])) {
            return $this->showReturnWithCode(1001);
        }
        $M = new Building();
        $f_id = $M->findId($this->post['building_id'])->f_id;
        //获取升级ID 信息
        $f_info = $M->findId($f_id);
        //玩家现有资源 同步
        $map = ['user_id' => $this->user_id];
        Hook::listen('sync_dynasty', $map);
        
        $user_dynasty = UserDynasty::findMap(['user_id' => $this->user_id]);
        if ($user_dynasty->wood < $f_info->price) return $this->showReturn('木材不足');

        //更新
        Db::startTrans();
        $building_id = $user_dynasty->building_id;
        $building_id[$this->post['building_id']] = $f_id; //建筑升级

        $user_dynasty->wood = $user_dynasty->wood - $f_info->price;  // 木材减少
        $user_dynasty->building_id = $building_id;

        $type = $this->getType('building')[$f_info->type];  //玩家最大值和增长值修改
        $ceiling_type = 's_' . $type;
        $max_type = "max_" . $type;
        $user_dynasty->$ceiling_type = $f_info->s_ceiling;
        $user_dynasty->$max_type = $f_info->resources_ceiling;

        if (!$user_dynasty->save()) return $this->showReturn('网络错误');
        Db::commit();


        return $this->showReturnCode(0, ['status' => 1]);  // 1 升级成功 0 失败  3
    }

    /**
     * 建筑安置人口修改 未用
     */
    /*    public function sBuilding()
        {
            //获取前端传递的类型  ，  数值
            //修改 表: user_dynasty 的增加速度
            $list = [
                'status' => 1
            ];
            return $this->showReturnCode(0 ,$list);  // 1 升级成功 0 失败  3
        }*/


    /**
     * 用户装载阵法更换
     */
    public function TacticalLoading()
    {
        $price = 3;  //更换阵法 消耗灵石数量
        if (!isset($this->post['type'])) {
            return $this->showReturnWithCode(1001);
        }
        //判断用户资源是否足够
        $map = ['user_id' => $this->user_id];
        $type = $this->post['type'];
        $user_attribute = UserAttribute::findMap($map)->toArray();
        $M = new Tactical();
        $flog = true;
        if (empty($user_attribute['tactical'])) {
            $level = 1;
            $flog = false;
        } else {
            $level = $M->findId($user_attribute['tactical'])->toArray()['level'];
            $user_resource = UserResource::findMap($map);
            if ($user_resource->lingshi < $price) return $this->showReturn('灵石不足');
        }
        if (!$M->tacticalLoading($this->user_id, $type, $level)) return $this->showReturn('网络错误');
        //减少资源
        if ($flog) {
            $user_resource->lingshi = $user_resource->lingshi - $price;
            $user_resource->save();
        }


        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 玩家当前阵法
     */
    public function userTactical()
    {
        //需要玩家当前装载阵法的下级信息
        $map = ['user_id' => $this->user_id];
        $user_attribute = UserAttribute::findMap($map)->toArray();
        if (empty($user_attribute['tactical'])) {
            return $this->showReturn('未装载阵法');
        }
        $info = Tactical::findMap(['tactical_id' => $user_attribute['tactical']], 'f_id')->toArray();
        $f_info = Tactical::findMap(['tactical_id' => $info['f_id']])->toArray();
        $price = Consumption::getListByMap(['consumption_id' => ['in', $f_info['consumption_id']]], 'type, value');

        $list = [
            'tactical_id' => $f_info['tactical_id'],
            'name' => $f_info['name'],
            'level' => $f_info['level'],
            'price' => $price,
            'attribute' => ['type' => $f_info['type'], 'value' => $f_info['value']],
        ];
        return $this->showReturnCode(0, $list);
    }

    /**
     * 学习升级阵法
     */
    public function studyeTactical()
    {
        //同步王朝资源数据

        if (!isset($this->post['tactical_id'])) {
            return $this->showReturnWithCode(1001);
        }
       
        $map = ['user_id' => $this->user_id];
        Hook::listen('sync_dynasty', $map);
        //1、获取前端阵法ID   判断使用资源是否够学习
        $M = new Tactical();

        $info = $M->findId($this->post['tactical_id'])->toArray();
        $price = Consumption::getListByMap(['consumption_id' => ['in', $info['consumption_id']]], 'type, value');
        $user_dynasty = UserDynasty::findMap(['user_id' => $this->user_id]);

        foreach ($price as $value) {
            $type = $this->getType('consumption')[$value['type']];
            if ($user_dynasty[$type] < $value['value']) {
                return $this->showReturn('资源不足');
            }
            $user_dynasty[$type] = $user_dynasty[$type] - $value['value'];
        }
        if (!UserAttribute::editMapData($map, ['tactical' => $this->post['tactical_id']])) return $this->showReturn('网络错误');
        //减少资源
        $user_dynasty->save();
        //减少玩家对应资源，修改玩家当前装载的阵法id
        //4、user_log记录
        $list['status'] = 1;
        return $this->showReturnCode(0, $list);   //1发放成功0失败
    }

}