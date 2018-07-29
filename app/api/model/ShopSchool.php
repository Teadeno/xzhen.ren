<?php


namespace app\api\model;

use Think\Hook;

class ShopSchool extends Base
{
    protected $table = 'shop_school';
    protected $pk = 'id';

    protected $comment = '门派商店表';

    /**
     * 门派商品购买操作
     * @ param array $info 商品信息
     * @ param object $user_resource 买家信息
     * @ return bool true | false
     */
    public function buyGoods($info, $user_resource)
    {
        try {
            $this->startTrans();
            //减少资源
            $user_resource->school_contribution = $user_resource->school_contribution - $info['price'];
            if (!$user_resource->save()) return false;
            //背包增加
            //其他物品可叠加  背包是否存在该物品如果存在直接增加数量，如果不存在新增数据
            $data = [
                'user_id' => $user_resource->user_id,
                'name' => $info['name'],
                'type' => array_search($info['table'], $this->getType('knapsack_type')),
                'goods_id' => $info[$info['table'] . '_id'],
                'num' => 1,
                'sell' => $info['sell'],
                'img_url' => $info['img_url'],
                'describe' => $info['describe'],
            ];
            if (!$this->addKnapsack($data)) return false;
            //日志记录
            $log = [
                'user_id' => $user_resource->user_id,
                'type' => 1,
                'goods_type' => array_search($info['table'], $this->getType('goods_buy')),
                'goods_id' => $info[$info['table'] . '_id']
            ];
            Hook::listen('goods_buy_log', $log);
            $this->commit();
            return true;
        } catch (Exception $exception) {
            $this->rollback();
            return false;
        }
    }
}