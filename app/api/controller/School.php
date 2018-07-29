<?php

namespace app\api\controller;

use app\api\model\Elixir;
use app\api\model\Esoterica;
use app\api\model\Mission;
use app\api\model\Price;
use app\api\model\Resource;
use app\api\model\School as S;
use app\api\model\SchoolPosition;
use app\api\model\ShopSchool;
use app\api\model\Skill;
use app\api\model\UserAttribute;
use app\api\model\UserResource;
use think\Db;
use Think\Hook;
use think\Loader;

class School extends Base
{
    /**
     * 加入门派
     */
    public function joinSchool()
    {
        //合法性验证
        if (!isset($this->post['level'])) {
            return $this->showReturnWithCode(1001);
        }
        //获取玩家境界
        $realm = UserResource::findMap(['user_id' => $this->user_id])->toArray()['realm_id'];
        //获取境界等级
        $level = \app\api\model\Realm::findMap(['realm_id' => $realm])->toArray()['grade'];
        if ($level < $this->post['level']) return $this->showReturn('境界不足');
        $school = S::getListByMap(['level' => $this->post['level']], 'school_id');
        $school_id = $school[rand(0, count($school) - 1)]['school_id'];
        $position_id = SchoolPosition::findMap(['school_id' => $school_id, 'level' => 0], 'position_id')->position_id;
        $update = ['school_id' => $school_id, 'position_id' => $position_id];

        if (!UserResource::editMapData(['user_id' => $this->user_id], $update)) return $this->showReturn('网络错误');

        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 叛出门派减少声望值显示
     */
    public function exitSchoolPst()
    {
        //验证资源是否足够
        $map = ['user_id' => $this->user_id];
        $user_resource = UserResource::findMap($map, 'school_id, prestige');
        $school = S::findMap(['school_id' => $user_resource->school_id], 'punishment')->toArray();

        return $this->showReturnCode(0, ['punishment' => $school['punishment']]);
    }

    /**
     * 叛出门派
     */
    public function exitSchool()
    {
        //验证资源是否足够
        $map = ['user_id' => $this->user_id];
        $user_resource = UserResource::findMap($map, 'school_id, prestige');
        $school = S::findMap(['school_id' => $user_resource->school_id], 'punishment')->toArray();

        if ($user_resource->prestige < $school['punishment']) return $this->showReturn('声望不足');
        $update = ['school_id' => 0, 'position_id' => 0, 'prestige' => $user_resource['prestige'] - $school['punishment']];

        $user_resource->school_id = 0;
        $user_resource->position_id = 0;
        $user_resource->execut_mission = 0;
        $user_resource->prestige = $user_resource->prestige - $school['punishment'];

        if (!$user_resource->save()) return $this->showReturn('网络错误');
        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 门派首页
     */
    public function index()
    {
        //合法性验证
        $map = ['user_id' => $this->user_id];
        $user_resource = UserResource::findMap($map, 'school_id, position_id, school_contribution')->toArray();
        if (empty($user_resource['school_id'])) return $this->showReturnCode(1002, [], '没有门派');
        //组装数据
        $position_level = SchoolPosition::findMap(['position_id' => $user_resource['position_id']], 'level')->toArray()['level'];
        $school = S::findMap(['school_id' => $user_resource['school_id']], 'name, leader,level')->toArray();
        //获取本门掌门姓名
        $leader = SchoolPosition::findMap(['school_id' => $user_resource['school_id'], 'level' => 9])->name;
        $list = [
            'school_name' => $school['name'],
            'school_level' => $school['level'],
            'position_name' => $this->getType('school')[$position_level],
            'school_contribution' => $user_resource['school_contribution'],
            'leader' => $leader
        ];
        return $this->showReturnCode(0, $list);
    }

    /**
     * 门派大厅
     */
    public function positionList()
    {
        $map = ['user_id' => $this->user_id];
        $user_resource = UserResource::findMap($map, 'school_id, position_id, school_contribution')->toArray();
        //获取用户所属门派等级获取该门派下所有职位
        $data = SchoolPosition::getListByMap(['school_id' => $user_resource['school_id']], 'position_id,level', 'level');

        $list['level'] = SchoolPosition::findMap(['position_id' => $user_resource['position_id']], 'level')->toArray()['level']; // 当前用户的职位等级
        foreach ($data as $key => $value) {
            $list['position']['a' . $value['level']] = $value['position_id'];
        }
        return $this->showReturnCode(0, $list);
    }

    /**
     * 传功殿
     */
    public function esotericaList()
    {
//        $school_id = $this->post['element']['school_id'];
        //每个门派的功法是固定的可以用缓存  以school_  开头  加上门派id 为键值
        //获取缓存  存在值直接返回  不存在获取并添加缓存
        $map = ['user_id' => $this->user_id];
        $user_resource = UserResource::findMap($map, 'school_id, position_id, school_contribution')->toArray();
        $map = [
            'school_id' => $user_resource['school_id'],
            'steps' => 1
        ];

        $list = Esoterica::getListByMap($map, 'esoterica_id, name, price_id, type, value, img_url');
        foreach ($list as $key => &$value) {
            $value['name'] = explode('》', $value['name'])[0] . '》';
            $value['price_value'] = Price::findMap(['price_id' => $value['price_id'], 'type' => 4], 'value')['value'];
            unset($value['price_id']);
        }
        return $this->showReturnCode(0, $list);
    }

    /**
     * 任务殿
     */
    public function missionList()
    {

        /* $map = ['user_id' => $this->user_id];
         $user_resource = UserResource::findMap($map, 'school_id, position_id, school_contribution')->toArray();

         $position_level = SchoolPosition::findMap(['position_id' => $user_resource['position_id']], 'level')->toArray()['level'];
         $data = Mission::getListByMap();
         $list = [];
         $list['position_id'] = $position_level;

         foreach ($data as $key => $value) {
             $value['position_level']++;
             $k = 'a' . $value['position_level'];
             $list['mission'][$k] = $value['mission_id'];
         }*/
        //获取当前正在执行的任务
        $map = ['user_id' => $this->user_id];
        $execut_mission = UserResource::findMap($map, 'execut_mission')->toArray()['execut_mission'];
        $list['execut_mission'] = empty($execut_mission) ? 0 : $execut_mission;

        return $this->showReturnCode(0, $list);
    }

    /**
     * 神通殿
     */
    public function skillList()
    {
        //神通殿获取的是该用户当前学习的神通的下级ID和未学习的神通
        $map['user_id'] = $this->user_id;
        $user_skill = UserAttribute::findMap($map, 'skill_id')->toArray()['skill_id'];
        $field = 'skill_id, name, price, f_id, type';
        if (!empty($user_skill)) {
            $data = Loader::model('Skill')->getUserSkill($user_skill, $field, true);
        }
        $typeList = Skill::getListByMap('', 'type', '', 'type');

        if (!empty($data)) {
            $typeList = array_diff(array_column($typeList, 'type'), array_column($data, 'type'));
            $info = Skill::getListByMap(['type' => ['in', $typeList], 'level' => 1], $field);
            $list['list'] = array_merge($data, $info);
        } else {
            $typeList = array_column($typeList, 'type');
            $list['list'] = Skill::getListByMap(['type' => ['in', $typeList], 'level' => 1], $field);
        }
        $list['skill'] = UserResource::findMap($map, 'skill')->toArray()['skill'];
        return $this->showReturnCode(0, $list);
    }

    /**
     * 职位修改
     */
    public function editPosition()
    {
        //1、获取前端传递的职位ID
        //2、获取需要消耗的门贡
        //3、验证门贡是否足够

        //5、修改用户的门派职位
        if (!isset($this->post['position_id'])) {
            return $this->showReturnWithCode(1001);
        }
        $map['user_id'] = $this->user_id;
        $user_resource = UserResource::findMap($map, 'school_contribution,position_id');
        //判断是否可以挑战
        $info = SchoolPosition::findMap(['position_id' => $user_resource->position_id])->toArray();
        if ($info['level'] == 9) return $this->showReturn('已达顶峰');
        $field = 'position_id, price, level';
        $data = SchoolPosition::findMap(['position_id' => $this->post['position_id']], $field)->toArray();

        if (!array_key_exists($this->post['position_id'], $info['f_id'])) {
            $data = SchoolPosition::getListByMap(['position_id' => ['in', $info['f_id']]], 'level');
            $str = '请晋升' . $this->getType('school')[$data[0]['level']];
            return $this->showReturn($str);
        }

        if ($user_resource->school_contribution < $data['price']) return $this->showReturn('门派贡献不足');

        $user_resource->school_contribution = $user_resource->school_contribution - $data['price'];
        $user_resource->position_id = $data['position_id'];

        if (!$user_resource->save()) $this->showReturn('网络错误');

        return $this->showReturnCode(0, ['status' => 1]);  //1成功0失败
    }

    public function getPosition()
    {

        //判断user_id是否能够挑战  不能返回提示
        //根据前端id查找相应职位
        //组装返回
        //合法性验证

        if (!isset($this->post['position_id'])) {
            return $this->showReturnWithCode(1001);
        }
        $map['user_id'] = $this->user_id;
        $user_resource = UserResource::findMap($map, 'school_contribution,position_id');
        //判断是否可以挑战
        $info = SchoolPosition::findMap(['position_id' => $user_resource->position_id])->toArray();
        if ($info['level'] == 9) return $this->showReturn('已达顶峰');

        if (!array_key_exists($this->post['position_id'], $info['f_id'])) {
            $data = SchoolPosition::getListByMap(['position_id' => ['in', $info['f_id']]], 'level');
            $str = '请晋升' . $this->getType('school')[$data[0]['level']];
            return $this->showReturn($str);
        }

        $field = 'position_id, name,price, sex,level, vita, attack, defense, speed, critical_strike, dodge, resistance, hit';
        $data = SchoolPosition::findMap(['position_id' => $this->post['position_id']], $field)->toArray();
        if ($user_resource->school_contribution < $data['price']) return $this->showReturn('门派贡献不足');

        unset($data['price']);
        unset($data['level']);
        $user_attribute = $this->getUserAttribute($this->user_id, true);
        $list['status'] = 1;
        $list['user'] = $user_attribute;
        $list['zoology'] = $data;

        return $this->showReturnCode(0, $list);
    }

    public function setPosition()
    {

        if (!isset($this->post['position_id']) || !isset($this->post['status'])) {
            return $this->showReturnWithCode(1001);
        }
        $status = $this->post['status'] == 1 ? true : false;
        if (!Loader::model('SchoolPosition')->setPosition($this->user_id, $this->post['position_id'], $status)) {
            return $this->showReturn('网络错误');
        }

        return $this->showReturnCode(0, ['status' => 1]);  //1成功0失败
    }

    /**
     * 门派任务执行
     */
    public function missionSuccess()
    {
        //合法性验证

        if (!isset($this->post['mission_id'])) {
            return $this->showReturnWithCode(1001);
        }
        $map = ['user_id' => $this->user_id];
        $user_resource = UserResource::findMap($map);
        //判断境界是否达到
        $realm = \app\api\model\SchoolPosition::findMap(['position_id' => $user_resource->position_id])->toArray()['level'];
        $level = Mission::findMap(['mission_id' => $this->post['mission_id']])->toArray()['position_level'];
        if ($realm < $level) return $this->showReturn('职位不符合要求');

        if ((time() - $user_resource->execut_mission_time) >= 600) {
            $data = $user_resource->toArray();
            Hook::listen('sync_mission', $data);
        }

        $user_resource->execut_mission = $this->post['mission_id'];
        $user_resource->execut_mission_time = time();
        if (!$user_resource->save()) return $this->showReturn('网络错误');

        return $this->showReturnCode(0, ['status' => 1]);   //1成功0失败
    }

    /**
     * 学习神通
     */
    public function studySkill()
    {
        //神通的学习和升级全部在神通殿进行
        //1、获取前端神通ID   判断使用神通点是否够学习
        //2、判断该神通是升级还是学习
        //3、学习则增加用户神通属性，升级则将原id修改为升级后的ID(根据学习的ID查找前一等级的ID   将用户神通列表属性更换)
        //4、user_log记录

        if (!isset($this->post['skill_id'])) {
            return $this->showReturnWithCode(1001);
        }

        $skill = Skill::findMap(['skill_id' => $this->post['skill_id']]);
        $map = ['user_id' => $this->user_id];
        //判断神通是否达到顶级
        $skill_id = UserAttribute::findMap($map, 'skill_id')->skill_id;
        if ($skill->level == 16 && array_key_exists($this->post['skill_id'], $skill_id)) {
            return $this->showReturn('已修炼大成');
        }

        $user_resource = UserResource::findMap($map);
        if ($user_resource->skill < $skill->price) return $this->showReturn('神通点不足');
        Db::startTrans();
        if (!Loader::model('Skill')->studySkill($this->user_id, $this->post['skill_id'], $skill)) return $this->showReturn('网络错误');
        //资源减少
        $user_resource->skill = $user_resource->skill - $skill->price;
        $user_resource->save();
        Db::commit();
        return $this->showReturnCode(0, ['status' => 1]);   //1发放成功0失败
    }

    /**
     * 学习功法
     */
    public function studyeEsoterica()
    {
        //门派功法学习
        //1、获取前端功法ID   判断门贡是否够
        //2、判断用户是否已经学习   递归获取该功法内所有等级id  验证 //根据类型，用户学习id  增加属性 查看是否有数据
        //3、学习则增加用户功法属性  并增加对应的属性  如果增加的是修炼速度（type =1）则需要调用行为扩展修为值增加
        //4、user_log记录

        if (!isset($this->post['esoterica_id'])) {
            return $this->showReturnWithCode(1001);
        }
        $esoterica = Esoterica::findMap(['esoterica_id' => $this->post['esoterica_id']]);

        $price = Price::findMap(['price_id' => $esoterica->price_id, 'type' => 4])->toArray()['value'];
        $map = ['user_id' => $this->user_id];
        $user_resource = UserResource::findMap($map);
        if ($user_resource->school_contribution < $price) return $this->showReturn('门派贡献不足');

        $res = Loader::model('Esoterica')->useEsoterica($this->user_id, $esoterica->esoterica_id, $esoterica->toArray());

        if (is_string($res)) return $this->showReturn($res);
        if (is_bool($res) && !$res) return $this->showReturn('网络错误');

        $user_resource->school_contribution -= $price;
        $user_resource->save();

        return $this->showReturnCode(0, ['status' => 1]);   //1发放成功0失败
    }

    /**
     * 门派商城
     * 消耗门贡增加
     */
    // 渡劫丹，古神精血,属性丹
    public function shopSchool()
    {
        //古神精血  属性丹
        //获取古神精血
        $map = ['user_id' => $this->user_id];
        $school_id = UserResource::findMap($map)->toArray()['school_id'];
        $school_level = \app\api\model\School::findMap(['school_id' => $school_id])->toArray()['level'];
        $list = [];
        $field = 'price_id,name,img_url,describe';

        //获取资源
        $resource_ids = ShopSchool::getListByMap(['level' => $school_level, 'type' => 2], 'id,goods_id');
        foreach ($resource_ids as $value) {
            $itme = Resource::findMap(['resource_id' => $value['goods_id']], $field)->toArray();
            $itme['id'] = $value['id'];
            $itme['price'] = Price::findMap(['price_id' => $itme['price_id'], 'type' => 4], 'value')->toArray()['value'];
            unset($itme['price_id']);
            $list[] = $itme;
        }
        //获取丹药
        $elixir_ids = ShopSchool::getListByMap(['level' => $school_level, 'type' => 1], 'id, goods_id', 'id');
        foreach ($elixir_ids as $value) {
            $itme = Elixir::findMap(['elixir_id' => $value['goods_id']], $field)->toArray();
            $itme['id'] = $value['id'];
            $itme['price'] = Price::findMap(['price_id' => $itme['price_id'], 'type' => 4], 'value')->toArray()['value'];
            unset($itme['price_id']);
            $list[] = $itme;
        }

        return $this->showReturnCode(0, $list);
    }

    /**
     * 门派商城
     * 购买道具
     */
    public function buyGoods()
    {
        //合法性验证

        if (!isset($this->post['id'])) {
            return $this->showReturnWithCode(1001);
        }
        $map = ['user_id' => $this->user_id];
        $id = $this->post['id'];
        //获取资源信息
        $M = new ShopSchool();
        $goods = $M::findMap(['id' => $id], 'goods_id, type, limit')->toArray();
        $type = $this->getType('school_position')[$goods['type']];

        $itme = Loader::model($type)->findId($goods['goods_id'])->toArray();
        $itme['price'] = Price::findMap(['price_id' => $itme['price_id'], 'type' => 4], 'value')->toArray()['value'];
        $itme['table'] = $type;
        //获取玩家信息
        $user_resouce = UserResource::findMap($map);
        if ($user_resouce->school_contribution < $itme['price']) return $this->showReturn('门派贡献不足');
        //今日购买上限
        switch ($goods['type']) {
            case 1:  //丹药
                $where = [
                    'user_id' => $user_resouce->user_id,
                    'type' => 1,
                    'goods_type' => array_search($type, $this->getType('goods_buy')),
                ];
                break;
            case 2: //资源
                $where = [
                    'user_id' => $user_resouce->user_id,
                    'type' => 1,
                    'goods_type' => array_search($type, $this->getType('goods_buy')),
                    'goods_id' => $goods['goods_id']
                ];
                break;
        }
        $start_time = date('Y-m-d 00:00:00', time());
        $ent_time = date('Y-m-d 24:00:00', time());
        $count = Db::name('goods_buy_log')->where($map)->where('create_time', '>=', $start_time)->where('create_time', '<=', $ent_time)->count();
        if ($goods['limit'] <= $count) {
            return $this->showReturn('今日已达上限');
        }
        //购买
        if (!$M->buyGoods($itme, $user_resouce)) return $this->showReturn('网络错误');
        return $this->showReturnCode(0, ['status' => 1]);
    }
}