<?php

namespace app\api\controller;

use app\api\model\ShopHj;
use app\api\model\UserResource;
use think\Db;
use think\Loader;

class Shop extends Base
{
    /**
     * RMB商店
     *消耗RMB增加高级灵石
     */
    public function shopRmb()
    {

    }

    /**
     * 鸿钧商店
     */
    public function shopHj($status = 0)
    {
        //1、获取前端传递的层级

        if (!isset($this->post['level'])) {
            return $this->showReturnWithCode(1001);
        }
        //判断是否能够开启本层
        $user_resource = UserResource::findMap(['user_id' => $this->user_id]);

        if ($user_resource->rmb < 50 * ($this->post['level'] - 1)) {
            return $this->showReturn('开启条件不足');
        }
        if (isset($this->post['status'])) {
            $status = $this->post['status'];
        }
        $start_time = date('Y-m-d 00:00:00', time());
        $ent_time = date('Y-m-d 24:00:00', time());


        $where = "create_time >= '{$start_time}' AND create_time <= '{$ent_time}' AND level = {$this->post['level']}";
        //刷新
        Db::startTrans();
        if ($status == 1) {
            //判断资源是否足够
            if ($user_resource->top_lingshi < 5) return $this->showReturn('顶级灵石不足');
            //刷新减少资源
            $user_resource->top_lingshi -= 5;
            $user_resource->save();
            ShopHj::destroy(function ($query) use ($where) {
                $query->where($where);
            });
        }
        $field = 'id, name, img_url, price, describe,type';
        $data = ShopHj::getListByMap($where, $field, 'type');
        //增加
        if (empty($data)) {
            //为空创建商品
            ShopHj::destroy(['user_id' => $this->user_id]);
            Loader::model('shophj')->addGoods($this->user_id, $this->post['level']);
            $data = ShopHj::getListByMap($where, $field, 'type');
        }
        Db::commit();

        $list = [];
        $list['top_lingshi'] = $user_resource->top_lingshi;
        $list['tier_num'] = floor($user_resource->rmb / 50) >= 10 ? 10 : floor($user_resource->rmb / 50);
        foreach ($data as $value) {
            switch ($value['type']) {
                case 1:
                    $list['elixir'][] = $value;
                    break;
                case 3:
                    $list['esoterica'][] = $value;
                    break;
                default:
                    $list['resource'][] = $value;
                    break;
            }
        }
        return $this->showReturnCode(0, $list);
    }

    /**
     * 鸿运商店购买道具
     */
    public function buyGoods()
    {
        //合法性验证
        if (!isset($this->post['id'])) {
            return $this->showReturnWithCode(1001);
        }
        if (empty($this->post['num']) || $this->post['num'] <= 0) {
            return $this->showReturn('购买数量不正确');
        }
        $num = empty($this->post['num']) ? 1 : $this->post['num'];
        $map = ['user_id' => $this->user_id];
        $id = $this->post['id'];
        //获取资源信息
        $M = new ShopHj();
        $goods = $M->findId($id, 'goods_id, type, price')->toArray();
        $type = $this->getType('knapsack_type')[$goods['type']];

        $itme = Loader::model($type)->findId($goods['goods_id'])->toArray();
        $itme['price'] = $goods['price'];
        $itme['table'] = $type;
        //获取玩家信息
        $user_resouce = UserResource::findMap($map);
        if ($user_resouce->top_lingshi < $itme['price'] * $num) return $this->showReturn('顶级灵石不足');

        //购买
        if (!$M->buyGoods($itme, $user_resouce, $num)) return $this->showReturn('购买失败');

        return $this->showReturnCode(0, ['status' => 1]);
    }
}