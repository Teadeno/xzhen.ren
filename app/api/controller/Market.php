<?php

namespace app\api\controller;


use app\api\model\Building;
use app\api\model\Market as M;
use app\api\model\MarketPosition;
use app\api\model\ShopCbk;
use app\api\model\User;
use app\api\model\UserKnapsack;
use app\api\model\UserResource;
use think\Loader;
use app\api\model\ShopHj;

class Market extends Base
{

    protected $position_id = [  //坊市职位id映射   键对应前端发送的值   值对应数据库id
        1 => 1,
        2 => 4,
        3 => 5,
        4 => 6,
        5 => 2,
        6 => 3,
    ];

    public function __construct()
    {
        parent::__construct();
        //关卡ID映射
        if (isset($this->post['position_id'])) {
            $this->post['position_id'] = $this->position_id[$this->post['position_id']];
        }
    }

    /**
     * 坊市首页
     */
    public function index()
    {
        //1、获取坊主姓名
        $username = MarketPosition::findMap(['level' => 3], 'name')->name;

        return $this->showReturnCode(0, ['name' => $username]);
    }

    /**
     * 坊市大厅
     */
    public function positionList()
    {
        $username = MarketPosition::findMap(['level' => 3], 'name')->name;
        //获取当前玩家的坊市职位等级
        $market = UserResource::findMap(['user_id' => $this->user_id], 'market')->toArray()['market'];
        if (empty($market)) {
            $level = 0;
        } else {
            $level = MarketPosition::findMap(['position_id' => $market], 'level')->toArray()['level'];
        }
        $list = [
            'name' => $username,
            'level' => $level
        ];
        return $this->showReturnCode(0, $list);
    }

    /**
     * 职位修改
     */
    public function editPosition()
    {
        //1、获取前端传递的职位ID
        //2、获取需要消耗的声望
        //3、验证门贡是否足够
        //4、判断是否成为坊主，坊主功法  需要战斗，1、获取玩家信息2、战斗算法调用，3、胜利修  为当前玩家，并将战斗前的属性值修改为当前门派职位的相应属性          失败直接返回失败信息
        $list['status'] = 1;
        return $this->showReturnCode(0, $list);  //1成功0失败
    }

    /**
     * 职位信息获取
     */
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
        $user_resource = UserResource::findMap($map, 'prestige,position_id');
        $field = 'position_id, price,name,vita, sex,attack, defense, speed, critical_strike, dodge, resistance, hit';
        $data = MarketPosition::findMap(['position_id' => $this->post['position_id']], $field)->toArray();

        if ($user_resource->prestige < $data['price']) return $this->showReturn('声望不足');

        $map['user_id'] = $this->user_id;
        $user_attribute = $this->getUserAttribute($this->user_id, true);


        $list['user'] = $user_attribute;
        $list['zoology'] = $data;

        return $this->showReturnCode(0, $list);
    }

    /**
     * 职位设置
     */
    public function setPosition()
    {
        //前端传递  供奉id， 战斗状态
        //获取该玩家所属门派  //根据不同职位等级  为该门派修改对应的职位 事物开始
        //修改该门派供奉或掌门对应的属性
        //修改玩家的门派职位  事物结束  返回信息

        if (!isset($this->post['position_id']) || !isset($this->post['status'])) {
            return $this->showReturnWithCode(1001);
        }

        $status = $this->post['status'] == 1 ? true : false;
        if (!Loader::model('MarketPosition')->setPosition($this->user_id, $this->post['position_id'], $status)) {
            return $this->showReturn('网络错误');
        }

        return $this->showReturnCode(0, ['status' => 1]);  //1成功0失败
    }

    /**
     * 藏宝阁
     * 消耗灵石增加
     */
    //固定五个格子，随机出现各种商品，每天12点 24点刷新
    public function shopCb($status = 0)
    {

        //shop_cb一个玩家对应一条数据每次情况这个接口  判断上次更新时间是否当天， 不是当天更新  是当天判断时间是否是 12点以后  之后不更新  之前判断现在时间是否是在  12点之后  在12点之后更新
        //获取今
        if (isset($this->post['status'])) {
            $status = $this->post['status'];
        }
        if (date('H', time()) < 12) {
            $start_time = date('Y-m-d 00:00:00', time());
            $ent_time = date('Y-m-d 12:00:00', time());
        } else {
            $start_time = date('Y-m-d 12:00:00', time());
            $ent_time = date('Y-m-d 24:00:00', time());
        }

        $where = "create_time >= '{$start_time}' AND create_time <= '{$ent_time}'";
        //刷新
        $user_resource = UserResource::findMap(['user_id' => $this->user_id]);
        if ($status == 1) {
            if ($user_resource->lingshi < 5) {
                return $this->showReturn('灵石不足');
            }
            //刷新减少资源
            $user_resource->lingshi = $user_resource->lingshi - 5;
            $user_resource->save();
            ShopCbk::destroy(function ($query) use ($where) {
                $query->where($where);
            });
        }
        $list = ShopCbk::getListByMap($where);
        //增加
        if (empty($list)) {
            //为空创建商品
            //古神精血  渡劫丹-
            Loader::model('shopcbk')->addGoods($this->user_id);
            $list = ShopCbk::getListByMap($where);
        }

        return $this->showReturnCode(0, $list);
    }

    /**
     * 藏宝阁购买
     * 购买道具
     */
    public function CbkBuyGoods()
    {
        //合法性验证

        if (!isset($this->post['id'])) {
            return $this->showReturnWithCode(1001);
        }
        if (empty($this->post['num']) || $this->post['num'] <= 0) {
            return $this->showReturn('购买数量不正确');
        }
        $map = ['user_id' => $this->user_id];
        $id = $this->post['id'];
        $num = empty($this->post['num']) ? 1 : $this->post['num'];
        //获取资源信息
        $M = new ShopCbk();
        $goods = $M->findId($id, 'goods_id, type, price')->toArray();
        $type = $this->getType('knapsack_type')[$goods['type']];

        $itme = Loader::model($type)->findId($goods['goods_id'])->toArray();
        $itme['price'] = $goods['price'];
        $itme['table'] = $type;
        //获取玩家信息
        $user_resouce = UserResource::findMap($map);
        if ($user_resouce->lingshi < $itme['price'] * $num) return $this->showReturn('灵石不足');

        //购买
        if (!$M->buyGoods($itme, $user_resouce, $num)) return $this->showReturn('网络错误');

        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 售宝楼出售商品
     *
     */
    public function sellGoods()
    {
        //获取前端发送背包id，数量
        //查找到该商品的出售价格
        //事物开始  玩家灵石增加  出售价格    减少玩家背包中该商品的数量  为空则删除

        if (!isset($this->post['knapsack_id']) || !isset($this->post['value'])) {
            return $this->showReturnWithCode(1001);
        }
        $knapsack_goods = UserKnapsack::findMap(['knapsack_id' => $this->post['knapsack_id']], 'sell,num');
//        $type = $this->getType('knapsack_type')[$knapsack_goods->type];

//        $data = Loader::model($type)->findId($knapsack_goods->goods_id);

        $user_resource = UserResource::findMap(['user_id' => $this->user_id]);

        $user_resource->lingshi += $knapsack_goods->sell * $this->post['value'];
        $user_resource->save();
        //背包删除
        $knapsack_goods->num -= $this->post['value'];
        if ($knapsack_goods->num <= 0) {
            $knapsack_goods->delete();
        } else {
            $knapsack_goods->save();
        }

        $list = [
            'status' => 1,
            'value' => $knapsack_goods->num   //使用后商品的剩余数量
        ];

        return $this->showReturnCode(0, $list); //1成功，0失败，
    }

    /**
     * 集市出售商品类型
     */
    public function marketType()
    {
        $data = Building::getListByMap('', 'img_url, type', '', 'type');

        return $this->showReturnCode(0, $data);
    }


    /**
     * 集市出售商品根据类型索引
     */
    public function getGoods()
    {
        $type = $this->post['type'];
        if (empty($type)) return $this->showReturnWithCode(1001);
        $data = M::getListByMap(['type' => $type], 'id, price, type, num');

        return $this->showReturnCode(0, $data);

    }

    /**
     * 集市商品购买
     */
    public function buyGoods()
    {
        if (empty($this->post['id']) || empty($this->post['num'])) return $this->showReturnWithCode(1001);
        $id = $this->post['id'];
        $num = $this->post['num'];
        $M = new M();
        //判断数量是否足够
        //判断买家资源是否足够
        //购买操作  买家增加相应资源，减少灵石数量，卖家增加相应灵石
        if (!$info = $M::findMap(['id' => $id])) return $this->showReturn('商品不存在');
        if ($info->num < $num) return $this->showReturn('商品数量不足');
        if (!$user_resource = UserResource::findMap(['user_id' => $this->user_id])) return $this->showReturn('网络错误');
        if ($user_resource->lingshi < ($num * $info->price)) return $this->showReturn('灵石不足');
        //购买
        if ($M->buyGoods($info, $user_resource)) return $this->showReturn('网络错误');

        return $this->showReturnCode(0, ['status' => 1]);

    }
}