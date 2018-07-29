<?php


namespace app\api\model;

use Think\Hook;

class ShopCbk extends Base
{
    protected $table = 'shop_cbk';
    protected $pk = 'id';
    protected $comment = '藏宝库';

    /**
     * 藏宝阁商品增加
     * @ param int $user_id 玩家ID
     * @ return bool true | false
     */
    public function addGoods($user_id)
    {
        try {
            $this->startTrans();
            $info = Resource::findMap(['resource_id' => 3])->toArray();
            $price = Price::findMap(['price_id' => $info['price_id'], 'type' => 1])->toArray()['value'];
            $data[] =
                [
                    'user_id' => $user_id,
                    'name' => $info['name'],
                    'type' => array_search('resource', $this->getType('knapsack_type')),
                    'goods_id' => $info['resource_id'],
                    'num' => 1,
                    'img_url' => $info['img_url'],
                    'describe' => $info['describe'],
                    'price' => $price
                ];
            $info = Equipment::findMap(['equipment_id' => 5])->toArray();
//            $price = Price::findMap(['price_id' => $info['price_id'], 'type' => 1])->toArray()['value'];
            $data[] =
                [
                    'user_id' => $user_id,
                    'name' => $info['name'],
                    'type' => array_search('equipment', $this->getType('knapsack_type')),
                    'goods_id' => $info['equipment_id'],
                    'num' => 1,
                    'img_url' => $info['img_url'],
                    'describe' => $info['describe'],
                    'price' => 1
                ];

            $info = Elixir::findMap(['type' => 10, 'level' => rand(1, 10)])->toArray();
            $price = Price::findMap(['price_id' => $info['price_id'], 'type' => 1])->toArray()['value'];
            $data[] =
                [
                    'user_id' => $user_id,
                    'name' => $info['name'],
                    'type' => array_search('elixir', $this->getType('knapsack_type')),
                    'goods_id' => $info['elixir_id'],
                    'num' => 1,
                    'img_url' => $info['img_url'],
                    'describe' => $info['describe'],
                    'price' => $price
                ];
            if (!$this->saveAll($data)) return false;

            $this->commit();
            return true;
        } catch (Exception $exception) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 藏宝商品购买操作
     * @ param array $info 商品信息
     * @ param object $user_resource 买家信息
     * @ param int $num 数量
     * @ return bool true | false
     */
    public function buyGoods($info, $user_resource, $num = 1)
    {
        try {
            $this->startTrans();
            //减少资源
            $user_resource->lingshi = $user_resource->lingshi - $info['price'] * $num;
            if (!$user_resource->save()) return false;
            //背包增加
            //其他物品可叠加  背包是否存在该物品如果存在直接增加数量，如果不存在新增数据
            $data = [
                'user_id' => $user_resource->user_id,
                'name' => $info['name'],
                'type' => array_search($info['table'], $this->getType('knapsack_type')),
                'goods_id' => $info[$info['table'] . '_id'],
                'num' => $num,
                'sell' => $info['sell'],
                'img_url' => $info['img_url'],
                'describe' => $info['describe'],
            ];
            if (!$this->addKnapsack($data)) return false;
            //日志记录
            $log = [
                'user_id' => $user_resource->user_id,
                'type' => 2,
                'goods_type' => array_search($info['table'], $this->getType('goods_buy')),
                'goods_id' => $info[$info['table'] . '_id'],
                'num' => $num,
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