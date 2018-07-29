<?php

namespace app\api\controller;

use app\api\model\DayAward;
use app\api\model\DayAwardLog;
use app\api\model\Equipment;
use app\api\model\Esoterica;
use app\api\model\Price;
use app\api\model\Realm;
use app\api\model\User as U;
use app\api\model\UserAttribute;
use app\api\model\UserElixirLog;
use app\api\model\UserEquipment;
use app\api\model\UserInvite;
use app\api\model\UserKnapsack;
use app\api\model\UserLog;
use app\api\model\UserResource;
use think\Db;
use think\Hook;
use think\Loader;


class User extends Base
{
    /**
     * 游戏头部信息
     */
    public function top()
    {
        //游戏头部信息  玩家昵称，性别，声望值，灵石 ，任务，任务同步时间
        //门派任务是否存在 同步更新位置   获取上次任务同步的时间是够超过10分钟  未超过不处理   超过查询任务奖励同步更新 调用任务同步行为
        $map = ['user_id' => $this->user_id];
        $user_resource = UserResource::findMap($map);
        if ((time() - $user_resource->execut_mission_time) >= 600) {
            $data = $user_resource->toArray();
            Hook::listen('sync_mission', $data);
        }
        //检测玩家是否有未读邮件


        $unread_email = Loader::model('email')->Where(array_merge($map, ['is_read' => 0]))->count();
        $list = [
            'username' => $this->user->username,
            'sex' => $this->user->sex,
            'unread_email' => $unread_email,
            'prestige' => $this->typeConvert($user_resource->prestige),
            'lingshi' => $this->typeConvert($user_resource->lingshi),
        ];
        return $this->showReturnCode(0, $list);
    }

    /**
     * 玩家首页
     *
     */
    public function index()
    {
        /* 前端需要数据  user表           username玩家昵称  sex性别
                         user_resource 表  lingshi灵石数量  prestige声望数量  quality修为值    practice_speed修炼速度  realm_id当前境界名称 当前境界为几阶
                         user_log 表    玩家日志信息
        */
        //在首页获取的时候增加修为值

        $map = ['user_id' => $this->user_id];
        Hook::listen('sync_quality', $map);
        $user_resource = UserResource::findMap($map, 'quality, realm_id, create_time');
        $user_attribute = UserAttribute::findMap($map, 'practice_speed');
        //当前境界
        $realm = Realm::findMap(['realm_id' => $user_resource->realm_id], 'name, grade,steps, f_id');

        //下一境界
        $price = Realm::findMap(['realm_id' => $realm->f_id], 'price')->price ?? 0;
        $user_log = UserLog::getListByMap($map, 'time, content, user_id', 'create_time desc', '', 5, ['username']);
        $quality = $this->typeConvert((int)$user_resource->quality);
        //判断是否第一次进入
        if ($realm->grade == 0 && $realm->steps == 1) $one = 1;
        $list = [
            'one' => isset($one) ? $one : 0,
            'quality' => (int)$user_resource->quality,
            'practice_speed' => $user_attribute->practice_speed,
            'realm' => $realm->name,
            'realm_steps' => $realm->steps,
            'realm_price' => $price,
            'time' => $this->getTime(time(), $user_resource->create_time),
            'user_log' => $user_log
        ];


        return $this->showReturnCode(0, $list);
    }

    /**
     * 玩家详情
     */
    public function userParticulars()
    {
        //人物属性，人物装备，人物阵法
        //人物属性 = user_attribute +  人物装备  + 人物阵法
        //1 获取人物装备   根据用户ID  获取user_equipment表中该用户的装备，
        //2 根据装备ID获取该装备增加的属性
        //3 获取用户的属性user_attribute
        //4 将装备增加的属性  用户本身的属性想加
        // 5 获取法宝
        //6 返回 数据
        $user_attribute = $this->getUserAttribute($this->user_id);
        $map['user_id'] = $this->user_id;
        $equipment = UserEquipment::getListByMap($map, 'id, equipment_name, equipment_type, img_url');
        $data['equipment'] = empty($equipment) ? [] : $equipment;
        $list = array_merge($user_attribute, $data);
        return $this->showReturnCode(0, $list);
    }

    /**
     * 玩家神通
     */
    public function userSkill()
    {
        $map['user_id'] = $this->user_id;
        $user_skill = UserAttribute::findMap($map, 'skill_id')->toArray()['skill_id'];

        $field = 'skill_id, name, level, value';
        $data = Loader::model('Skill')->getUserSkill($user_skill, $field);
        return $this->showReturnCode(0, $data);
    }

    /**
     * 玩家功法列表
     */
    public function userEsotericaList()
    {
        if (!isset($this->post['price_type'])) {
            return $this->showReturnWithCode(1001);
        }
        //1、获取user_attribute该用户功法字段值
        //2、获取以后处理 explode() 字符串转数组
        //3、拼接查询条件       索引条件  //属性增加类型筛选，前端没有 传递则获取全部     消耗资源类型  必须
        //4、循环查询该功法的  下一级功法信息
        //5、对功法信息拼装  需要功法名称，功法作用，消耗资源   修为，灵石，门贡  要根据不同情况返回不同的值
        $user_esoterica = UserAttribute::findMap(['user_id' => $this->user_id], 'esoterica_id')->toArray()['esoterica_id'];
        $map['esoterica_id'] = ['in', $user_esoterica];

        if (isset($this->post['type'])) $map['type'] = $this->post['type'];
        if (!isset($this->post['type']) && $this->post['price_type'] == 2) $map['type'] = ['neq', 1];

        $data = Esoterica::getListByMap($map, 'esoterica_id,name, price_id,type, level, value, f_id, steps', 'steps,esoterica_id desc');
        $list = $imet = [];
        foreach ($data as $key => $value) {
            //判断阶数是否满级满级直接放入不是满级 则查找下级id
            if ($value['steps'] == 11 || $value['f_id'] == 0) {
                unset($value['f_id']);
                $imet[] = $value;
            } else {
                $list[] = Esoterica::findMap(['esoterica_id' => $value['f_id']], 'esoterica_id,name,level,steps, price_id, type, value')->toArray();
            }
        }
        $list = array_merge($imet, $list);
        foreach ($list as $key => $value) {
            $list[$key]['name'] = explode('》', $value['name'])[0] . '》';
            if ($value['steps'] !== 11) {
                $list[$key]['level'] = $value['level'] . '星' . $value['steps'] . '重';
            } else {
                $list[$key]['level'] = '顶级';
            }
            $list[$key]['price_value'] = Price::findMap(['price_id' => $value['price_id'], 'type' => $this->post['price_type']], 'value')['value'];
            unset($value['steps']);
            unset($list[$key]['price_id']);
        }
        $type = $this->getType('resource')[$this->post['price_type']];
        $list = [
            'value' => UserResource::findMap(['user_id' => $this->user_id])->toArray()[$type],
            'list' => $list
        ];
        return $this->showReturnCode(0, $list);
    }

    /**
     * 玩家升级功法
     */
    public function userStudyeEsoterica()
    {
        //合法验证
        if (!empty($esoterica_id) && !empty($type) && !empty($user_id)) {
            $this->post['esoterica_id'] = $esoterica_id;
            $this->post['type'] = $type;
            $this->user_id = $user_id;
        }

        if (!isset($this->post['type']) || !isset($this->post['esoterica_id'])) {
            return $this->showReturnWithCode(1001);
        }
        // 获取功法信息
        $M = new Esoterica();
        $esoterica = $M::findMap(['esoterica_id' => $this->post['esoterica_id']], 'esoterica_id, name, price_id, type, value,steps,f_id')->toArray();
        //判断是否为顶级功法
        if ($esoterica['steps'] == 11) {
            return $this->showReturn('功法已大成');
        }
        $value = Price::findMap(['price_id' => $esoterica['price_id'], 'type' => $this->post['type']], 'value')['value'];
        //资源同步
        $map = ['user_id' => $this->user_id];
        switch ($this->post['type']) {
            case 1:         //灵石
                Hook::listen('sync_mission', $map);
                $str = '灵石';
                break;
            case 2:         //修为
                Hook::listen('sync_quality', $map);
                $str = '修为';
                break;
            case 4:         //门贡
                Hook::listen('sync_mission', $map);
                $str = '门派贡献';
                break;
        }
        //判断资源是否足够
        $user_resource = UserResource::findMap(['user_id' => $this->user_id]);
        $type = $this->getType('resource')[$this->post['type']];
        if ($user_resource[$type] < $value) {

            return $this->showReturn($str . '不足');
        }
        //功法升级
        $res = $M->upgradeEsoterica($this->user_id, $this->post['esoterica_id'], $esoterica);
        if (is_string($res)) return $this->showReturn($res);
        if (is_bool($res) && !$res) return $this->showReturn('网络错误');
        //减少资源
        $user_resource->$type = $user_resource->$type - $value;
        $user_resource->save();

        return $this->showReturnCode(0, ['status' => 1, 'f_id' => $esoterica['f_id']]);
    }

    /**
     * 玩家纳戒
     */
    public function userKnapsack()
    {
        //1、获取用户内物品数据
        $map = ['user_id' => $this->user_id];
        $user_knapsack = UserKnapsack::getListByMap($map, 'knapsack_id, name, type, num, img_url,describe,sell');
        //2 、重组数据
        $list = [
            'num' => UserResource::findMap($map, 'knapsack_num')->toArray()['knapsack_num'],
            'knapsack' => $user_knapsack
        ];
        return $this->showReturnCode(0, $list);
    }

    /**
     * 玩家使用物品
     */
    public function employKnapsack()
    {
        //合法验证
        if (!isset($this->post['knapsack_id']) || !isset($this->post['num'])) {
            return $this->showReturnWithCode(1001);
        }
        if ($this->post['knapsack_id'] == 0 || $this->post['num'] == 0) {
            return $this->showReturn('数量不足');
        }
        $knapsack_goods = UserKnapsack::findMap(['knapsack_id' => $this->post['knapsack_id']]);
        Db::startTrans();
        switch ($knapsack_goods['type']) {
            case 1:  //丹药
                $res = Loader::model('Elixir')->useElixir($this->user_id, $knapsack_goods->goods_id, $this->post['num']);
                break;
            case 2:  //装备
                $res = Loader::model('Equipment')->useEquipment($this->user_id, $knapsack_goods->goods_id);
                break;
            case 3:  //功法
                $res = Loader::model('Esoterica')->useEsoterica($this->user_id, $knapsack_goods->goods_id);
                break;
            case 4:  //资源
                $res = Loader::model('Resource')->useResource($this->user_id, $knapsack_goods->goods_id, $this->post['num']);
                break;
            case 5:  //王朝资源
                if (UserResource::findMap(['user_id' => $this->user_id], 'realm_id')->realm_id <= 80) {
                    $res = '暂无法使用';
                } else {
                    $res = Loader::model('Dynasty')->useDynasty($this->user_id, $knapsack_goods->goods_id, $this->post['num']);
                }
                break;
        }
        if (is_string($res)) return $this->showReturn($res);
        if (is_bool($res) && !$res) return $this->showReturn('网络错误');
        //背包删除
        $knapsack_goods->num = $knapsack_goods->num - $this->post['num'];
        if ($knapsack_goods->num <= 0) {
            $knapsack_goods->delete();
        } else {
            $knapsack_goods->save();
        }
        Db::commit();
        $list = [
            'status' => 1,
            'value' => $knapsack_goods->num  //使用后商品的剩余数量
        ];
        return $this->showReturnCode(0, $list); //1成功，0失败，
    }

    /**
     * 玩家卸载装备
     *
     */
    public function unEquipment()
    {
        //根据前端传递的装备id和user_id 找出这个件装备  然后再纳戒中增加一条数据  再删除装备表中的数据

        if (!isset($this->post['id'])) {
            return $this->showReturnWithCode(1001);
        }

        $id = $this->post['id'];
        $M = new Equipment();
        if (!$M->unEquipment($this->user_id, $id)) return $this->showReturnWithCode('网络错误');

        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 玩家食用丹药列表记录
     */
    public function elixirList()
    {
        $this->post['level'] = 1;
        if (!isset($this->post['level'])) {
            return $this->showReturnWithCode(1001);
        }
        $data = UserElixirLog::getListByMap(['user_id' => $this->user_id, 'level' => $this->post['level']], 'level,type, num');

        return $this->showReturnCode(0, $data);
    }

    /**
     * 玩家接引人
     */
    public function userInvite()
    {
        $map = ['user_id' => $this->user_id];
        $user_invite = UserInvite::findMap($map, 'invite_list');
        $list = [
            'invite' => $this->user->invite,
            'invite_list' => empty($user_invite) ? [] : explode(',', $user_invite->toArray()['invite_list']),
            'f_id' => empty($this->user->f_id) ? 0 : $this->user->f_id
        ];
        if ($list['f_id'] !== 0) {
            $list['f_name'] = U::findMap(['user_id' => $list['f_id']], 'username')['username'];
        }
        return $this->showReturnCode(0, $list);

    }

    /**
     * 玩家接引人填写
     */
    public function invite()
    {
        $invite = $this->post['invite'];
        if (!$user = U::findMap(['invite' => $invite], 'username, user_id')) return $this->showReturn('无效的接引码');

        $M = new UserInvite();
        if (!$M->setInvite($this->user, $user)) return $this->showReturn('网络错误');

        $list = ['status' => 1];
        return $this->showReturnCode(0, $list);
    }

    /**
     * 活动页面
     */
    public function activity()
    {
        //判断玩家是否购买成长基金
        $map = ['user_id' => $this->user_id];
        $user_resource = UserResource::findMap($map, 'grow_award, month_num, vip,rmb')->toArray();

        $growth = empty($user_resource['grow_award']) ? 0 : 1;
        $month_num = empty($user_resource['month_num']) ? 0 : $this->getMonthNum($user_resource['month_num']);
        $vip = empty($user_resource['vip']) ? 0 : 1;
        $rmb = empty($user_resource['rmb']) ? 0 : 1;
        $list = [
            'growth' => $growth,
            'month_num' => $month_num,
            'vip' => $vip,
            'rmb' => $rmb,
        ];
        return $this->showReturnCode(0, ['status' => $list]);
    }

    /**
     * 成长基金
     */
    public function growthund()
    {
        //判断玩家是否购买成长基金
        $map = ['user_id' => $this->user_id];
        $user_esource = UserResource::findMap($map, 'grow_award, realm_id');
        $growth = $user_esource->grow_award;
        if (empty($growth)) return $this->showReturn('未购买');
        if ($growth == 100) return $this->showReturn('暂无奖励');

        $map = [
            'type' => 3,
            'id' => $growth
        ];
        $M = new DayAward();
        $info = $M::findMap($map)->toArray();
        //判断等级是否满足
        $level = Realm::findMap(['realm_id' => $user_esource->realm_id], 'grade')->toArray()['grade'];
        if ($info['realm_level'] > $level) return $this->showReturn('境界不足');

        $award = $M->getAwardList($info['award_id'], true);
        if (!$M->getAward($award, $this->user_id)) return $this->showReturn('网络错误');
        $user_esource->grow_award = $info['f_id'];
        $user_esource->save();
        $user_log = [
            'user_id' => $this->user_id,
            'type' => 6,
            'content' => static::getUserLogContent('growth', 0)
        ];
        if (!UserLog::create($user_log)) {
            return false;
        }
        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 月卡
     */
    public function monthVip()
    {
        //判断玩家是否购买成长基金
        $map = ['user_id' => $this->user_id];
        $user_esource = UserResource::findMap($map, 'month_num');
        $month_num = $user_esource->month_num;
        if (empty($month_num)) return $this->showReturn('未购买');
        if ($this->getMonthNum($month_num) <= 0) return $this->showReturn('月卡已到期');
        $map = [
            'user_id' => $this->user_id,
            'type' => 1,
            'time' => ['>=', strtotime(date('Y-m-d 00:00:00', time()))]
        ];
        //判断今天是否领取
        $info = DayAwardLog::findMap($map);
        if (!empty($info)) return $this->showReturn('今日已领取');

        $map = [
            'type' => 1,
        ];
        $M = new DayAward();
        $info = $M::findMap($map)->toArray();

        //领取奖励
        $award = $M->getAwardList($info['award_id'], true);
        if (!$M->getAward($award, $this->user_id)) return $this->showReturn('网络错误');

        $day_award_log = [
            'user_id' => $this->user_id,
            'type' => 1,
            'time' => time(),
        ];
        if (!DayAwardLog::create($day_award_log)) {
            return false;
        }
        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 终身卡
     */
    public function everVip()
    {
        //判断玩家是否购买成长基金
        $map = ['user_id' => $this->user_id];
        $user_esource = UserResource::findMap($map, 'vip');
        $vip = $user_esource->vip;
        if (empty($vip)) return $this->showReturn('未购买');
        $map = [
            'user_id' => $this->user_id,
            'type' => 2,
            'time' => ['>=', strtotime(date('Y-m-d 00:00:00', time()))]
        ];
        $info = DayAwardLog::findMap($map);
        if (!empty($info)) return $this->showReturn('今日已领取');

        $map = [
            'type' => 2,
        ];
        $M = new DayAward();
        $info = $M::findMap($map)->toArray();
        //判断今天是否领取
        //领取奖励
        $award = $M->getAwardList($info['award_id'], true);
        if (!$M->getAward($award, $this->user_id)) return $this->showReturn('网络错误');

        $day_award_log = [
            'user_id' => $this->user_id,
            'type' => 1,
            'time' => time(),
        ];
        if (!DayAwardLog::create($day_award_log)) {
            return false;
        }
        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 首冲奖励
     */
    public function first()
    {
        $M = new \app\api\model\Activity();
        $award_id = $M->findMap(['type' => 2])->award_id;
        $award = $M->getAwardList($award_id);
        return $this->showReturnCode(0, $award);
    }
}