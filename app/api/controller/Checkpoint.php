<?php

namespace app\api\controller;

use app\api\model\Checkpoint as C;
use app\api\model\UserCheckpoint;
use app\api\model\UserResource;
use app\api\model\WallMap;
use app\api\model\WallMapLog;
use app\api\model\Zoology;
use think\Db;
use think\Loader;

class Checkpoint extends Base
{
    protected $checkpoin_id = [  //关卡id映射   键对应前端发送的值   值对应数据库id
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
    ];

    public function __construct()
    {
        parent::__construct();
        //关卡ID映射
        if (isset($this->post['checkpoin_id'])) {
            $this->post['checkpoin_id'] = $this->checkpoin_id[$this->post['checkpoin_id']];
        }
    }

    /**
     * 获取云游首页
     *
     */
    public function index()
    {
        // 判断是否在挂图
        $map = ['user_id' => $this->user_id];

        $wall = WallMap::findMap($map);
        if (!empty($wall) && !empty($wall->checkpoin_id)) {
            $wall = $wall->toArray();
            if ($wall['ent'] == 1) {
                $name = C::findMap(['checkpoin_id' => $wall['checkpoin_id']])->toArray()['name'];
                $time = $wall['start_time'] + $wall['num'] * 300 - time();
                if ($time <= 0) {
                    $time = 0;
                    //获取全部剩余奖励
                    $num = $wall['num'] - $wall['get_num'];

                    while ($num > 0) {
                        if (!$this->wallMapAward()) return $this->showReturn('网络错误');
                        $num--;
                    }
                }
                $list = [
                    'status' => 0,
                    'item' => [
                        'name' => $name,
                        'num' => $wall['num'],
                        'time' => (int)$time,
                    ]
                ];

                return $this->showReturnCode(0, $list);
            }
        }
        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 挂图奖励
     */
    public function wallMapAward()
    {
        //wall_map_log 获取玩家最新一个挂图未领取的奖励信息
        //根据地图id找到对应的奖励信息
        //调用公共模型  领取奖励  挂图轮数  循环  返回信息根据url 相同则数量想加，不相同则显示
        //判断是否有自动出售的装备设置  如果有先将装备出售 加为灵石  释放相应变量
        //奖励分发  根据奖励的物品将对应的资源想加，
        $map = ['user_id' => $this->user_id];
        $M = new WallMap();
        $wall = $M::findMap($map)->toArray();
        //判断奖励是否可以领取
        if ($wall['num'] <= $wall['get_num']) return $this->showReturn('已领取');
        //获取奖励
        $award_id = C::findMap(['checkpoin_id' => $wall['checkpoin_id']])->toArray()['award_id'];
        $award = $M->getAwardList($award_id, true);
        //获取自动出售装备
        if (!$level = WallMap::findMap(['user_id' => $this->user_id])) {
            $level = false;
        } else {
            $level = $level->equipment_level;
        }
        //领取
        if (!$M->wallMapAward($this->user_id, $award, $level)) return $this->showReturn('网络错误');

        return $this->showReturnCode(0, $award);
    }

    /**
     * 判断是否通关
     */
    public function is_succeed()
    {
        if (empty($this->post['checkpoin_id'])) {
            return $this->showReturnWithCode(1001);
        }
        if ($this->post['checkpoin_id'] > 1) {
            return $this->showReturn('暂未开放');
        }
        $levle = \app\api\model\Checkpoint::findMap(['checkpoin_id' => $this->post['checkpoin_id']], 'level')->level;
        if ($levle !== 0) {
            $f_id = \app\api\model\Checkpoint::findMap(['level' => $levle - 1], 'checkpoin_id')->checkpoin_id;
            $succeed = UserCheckpoint::findMap(['user_id' => $this->user_id, 'checkpoin_id' => $f_id]);
            if (empty($succeed) || $succeed->is_succeed == 0) return $this->showReturn('请通关上一关卡');
        }

        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 获取战斗信息
     *
     */
    public function getinfo()
    {

        //前端传递打的图，然后数据库检索出怪物信息

        if (!isset($this->post['checkpoin_id'])) {
            return $this->showReturnWithCode(1001);
        }
        //判断是否通关上一层
        $levle = \app\api\model\Checkpoint::findMap(['checkpoin_id' => $this->post['checkpoin_id']], 'level')->level;
        if ($levle !== 0) {
            $f_id = \app\api\model\Checkpoint::findMap(['level' => $levle - 1], 'checkpoin_id')->checkpoin_id;
            $succeed = UserCheckpoint::findMap(['user_id' => $this->user_id, 'checkpoin_id' => $f_id]);
            if (empty($succeed) || $succeed->is_succeed == 0) return $this->showReturn('请通关上一关卡');
        }

        $user_attribute = $this->getUserAttribute($this->user_id, true);
        //获取人物属性
        $data = Zoology::getListByMap(['checkpoin_id' => $this->post['checkpoin_id']], '', 'is_boss,type');

        //组装返回
        $list['user'] = $user_attribute;
        $list['zoology'] = $data;
        return $this->showReturnCode(0, $list);
    }

    /**
     * 获取战斗奖励
     */
    public function getAward()
    {

        //根据打的怪物id  获取对应属性  调用模型公共方法获取奖励
        //将奖励信息  增加对应的资源  背包
        //前端返回
//        $this->post['zoology_id'] = 25;
        if (!isset($this->post['zoology_id'])) {
            return $this->showReturnWithCode(1001);
        }
        $M = new Zoology();
        $award_id = $M->findId($this->post['zoology_id'], 'award_id')->toArray()['award_id'];
        $award = $M->getAwardList($award_id, true);  //获取奖励

        if (!$M->getAward($award, $this->user_id)) return $this->showReturn('网络错误');

        return $this->showReturnCode(0, $award);
    }

    /**
     * 战斗通关记录
     */
    public function succeed()
    {

        if (!isset($this->post['checkpoin_id'])) {
            return $this->showReturnWithCode(1001);
        }
        $succeed = UserCheckpoint::findMap(['user_id' => $this->user_id, 'checkpoin_id' => $this->post['checkpoin_id']]);
        if (empty($succeed)) {
            $data = [
                'user_id' => $this->user_id,
                'checkpoin_id' => $this->post['checkpoin_id'],
                'num' => 10,
                'is_succeed' => 1
            ];
            if (!UserCheckpoint::create($data)) return $this->showReturn('网络错误');
        }
        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 挂图信息
     */
    public function wallMap()
    {
        //地图ID映射
        //每天重置挂图次数  判断最后一个修改时间 是否为今天，否  重置  是不处理
        //返回当前剩余挂图次数
        //每次挂图轮数限制
        //获取最后一次挂图信息
        $map = ['user_id' => $this->user_id];
        $info = WallMap::findMap($map);

        if (empty($info)) {
            WallMap::create($map);
            $info = WallMap::findMap($map);
        }
        $user_resource = UserResource::findMap($map);

//        是否重置
        $time = date('Y-m-d', strtotime($info->update_time));
        if ($time != date('Y-m-d', time()) || $info->count === null) {
            $count = $user_resource['wall_map_num'];
            //判断是否月卡加成
            if (!empty($user_resource['month_num'])) {
                if ($this->getMonthNum($user_resource['month_num']) > 0) {
                    $count += 10;
                }
            }
            if (!WallMap::editMapData($map, ['count' => $count])) return $this->showReturn('网络错误');
        }
        $wall = WallMap::findMap($map)->toArray();
        //判断是否分身符
        if (!empty($user_resource['cloned'])) {
            $wall['count'] += $user_resource['cloned'];
        }

        return $this->showReturnCode(0, ['wall_map_num' => $wall['count'], 'wall_map_wheel' => $user_resource['wall_map_wheel']]);

    }

    /**
     * 挂图开始
     */
    public function wallMapStatus()
    {
        //根据前端传递的  地图ID  和挂图次数  3种情况， 1、正在挂图， 2、挂图结束  ， 3、没有挂图
        //   查询wall_map_log表查看用户最后的一次挂图时间是否 到现在的时间是否超过20分钟
        // 未超过 则返回状态码 status = 0 即还剩余的时间  20分钟  -  （当前时间-挂图开始时间）
        //超过判断奖励是否领取   是  添加新的数据  返回状态码status  = 0  和20分钟时间    否 跳出弹框  让用户点击领取返回状态码 status = 1

        if (empty($this->post['checkpoin_id']) || empty($this->post['num'])) {
            return $this->showReturn('请选择挂图场景和次数');
        }
        //判断是否通关
        $succeed = UserCheckpoint::findMap(['user_id' => $this->user_id, 'checkpoin_id' => $this->post['checkpoin_id']]);
        if (empty($succeed) || $succeed->is_succeed == 0) return $this->showReturn('请先通关');

        //修改挂图记录
        $num = $this->post['num'];
        $map = ['user_id' => $this->user_id];
        //判断次数是否足够
        $wall = WallMap::findMap($map)->toArray();

        if ($wall['count'] < $num) {
            //是否有分神符
            $user_resource = UserResource::findMap($map);
            if (!empty($user_resource['cloned'])) {  //有
                if ($wall['count'] + $user_resource['cloned'] >= $num) {
                    $user_resource->cloned -= $num;
                    $user_resource->save();
                } else { //分身符次数不足
                    return $this->showReturn('次数不足');
                }
                $count = 0;
            } else { //没有
                return $this->showReturn('次数不足');
            }
        } else {
            $count = $wall['count'] - $num;
        }

        $update = [
            'count' => $count,
            'num' => $num,
            'get_num' => 0,
            'checkpoin_id' => $this->post['checkpoin_id'],
            'start_time' => time(),
            'ent' => 1,
        ];
        if (!WallMap::editMapData($map, $update)) return $this->showReturn('网络错误');

        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 获取自动出售装备等级
     */
    public function getAutoEquipment()
    {
        //判断是否开启随身商人
        $info = WallMap::findMap(['user_id' => $this->user_id]);
        if (empty($info) || $info->is_sell == 0) {
            return $this->showReturn('随身商人开启需消耗10000灵石');
        }
        //已开启 返回已选择出售的装备等级
        $info = array_merge($info->equipment_level);


        return $this->showReturnCode(0, $info);
    }

    /**
     * 开启随身商人
     */
    public function openAutoSell()
    {
        $map = ['user_id' => $this->user_id];
        //判断灵石是否足够
        $user_resource = UserResource::findMap($map, 'lingshi');

        if ($user_resource->lingshi < 10000) return $this->showReturn('灵石不足');

        Db::startTrans();
        $user_resource->lingshi = $user_resource->lingshi - 10000;
        if (!$user_resource->save()) return $this->showReturn('网络错误');
        if (!WallMap::editMapData($map, ['is_sell' => 1])) return $this->showReturn('网络错误');
        Db::commit();

        return $this->showReturnCode(0, ['status' => 1]);

    }

    /**
     * 设置自动出售装备等级
     */
    public function setAutoEquipment()
    {
        if (!isset($this->post['level'])) {
            return $this->showReturnWithCode(1001);
        }
        if (!Loader::model('WallMap')->setEquipmentLevel($this->user_id, $this->post['level'])) return $this->showReturn('网络错误');
        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 奖励列表
     */
    public function awardList()
    {
        $map = ['user_id' => $this->user_id];
        $list = WallMapLog::getListByMap($map, '', 'id');
        return $this->showReturnCode(0, $list);
    }

    /**
     * 挂图离开
     *
     */
    public function leave()
    {
        $map = ['user_id' => $this->user_id];
        $M = new WallMap();
        $M::editMapData($map, ['ent' => 0]);
        WallMapLog::destroy($map);
        return $this->showReturnCode(0, ['status' => 1]);
    }

}