<?php


namespace app\api\model;

use think\exception\PDOException;
use Think\Hook;

class ShopHj extends Base
{
    protected $table = 'shop_hj';
    protected $pk = 'id';
    protected $comment = '鸿运商店';

    /**
     * 鸿运商品增加
     * @param int $user_id 玩家ID
     * @return bool true | false
     */
    public function addGoods($user_id, $level)
    {
        try {
            $this->startTrans();

            //折扣
            $discount = 1 - ($level - 1) * 0.05;  //每层减少0.05
            //功法
            $school = School::getListByMap(['level' => $level], 'school_id,level');
            $school_id = $school[rand(0, count($school) - 1)]['school_id'];
    
            $info = Esoterica::getListByMap(['level' => $school[0]['level'], 'steps' => 1, 'school_id' => $school_id], 'esoterica_id');
            $info = array_column($info, 'esoterica_id');
            shuffle($info);
            $info = array_slice($info, 3);
            $info = Esoterica::getListByMap(['esoterica_id' => ['in', $info]]);

            foreach ($info as $value) {
                $price = Price::findMap(['price_id' => $value['price_id'], 'type' => 6])->toArray()['value'];
                $data[] =
                    [
                        'user_id' => $user_id,
                        'name' => explode('》', $value['name'])[0] . '》',
                        'type' => array_search('esoterica', $this->getType('knapsack_type')),
                        'goods_id' => $value['esoterica_id'],
                        'level' => $level,
                        'img_url' => $value['img_url'],
                        'describe' => $value['describe'],
                        'price' => $price * $discount,
                    ];
            }
            //丹药
            $info = Elixir::getListByMap(['level' => $level], 'elixir_id');
            $info = array_column($info, 'elixir_id');
            shuffle($info);
            $info = array_slice($info, 3);
            $info = Elixir::getListByMap(['elixir_id' => ['in', $info]]);
            foreach ($info as $value) {
                $price = Price::findMap(['price_id' => $value['price_id'], 'type' => 6])->toArray()['value'];
                $data[] =
                    [
                        'user_id' => $user_id,
                        'name' => $value['name'],
                        'type' => array_search('elixir', $this->getType('knapsack_type')),
                        'goods_id' => $value['elixir_id'],
                        'level' => $level,
                        'img_url' => $value['img_url'],
                        'describe' => $value['describe'],
                        'price' => $price * $discount,
                    ];
            }
            //资源
    
            $resource_id = [1, 2, 4, 5, 6];
            if ($level > 3) $resource_id[] = 8;
            if ($level > 5) $resource_id[] = 7;
            $map['resource_id'] = ['in', $resource_id];
            $info = [];
            $info = Resource::getListByMap($map);
            foreach ($info as $value) {
                $price = Price::findMap(['price_id' => $value['price_id'], 'type' => 6])->toArray()['value'];
                $data[] =
                    [
                        'user_id' => $user_id,
                        'name' => $value['name'],
                        'type' => array_search('resource', $this->getType('knapsack_type')),
                        'goods_id' => $value['resource_id'],
                        'level' => $level,
                        'img_url' => $value['img_url'],
                        'describe' => $value['describe'],
                        'price' => $price * $discount,
                    ];
            }
            //王朝资源
            $info = [];
            $info = Dynasty::getListByMap();
            foreach ($info as $value) {
                $price = Price::findMap(['price_id' => $value['price_id'], 'type' => 6])->toArray()['value'];
                $data[] =
                    [
                        'user_id' => $user_id,
                        'name' => $value['name'],
                        'type' => array_search('dynasty', $this->getType('knapsack_type')),
                        'goods_id' => $value['dynasty_id'],
                        'level' => $level,
                        'img_url' => $value['img_url'],
                        'describe' => $value['describe'],
                        'price' => $price * $discount,
                    ];
            }
            if (!$this->saveAll($data)) return false;

            $this->commit();
            return true;
        } catch (PDOException $exception) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 鸿运商品购买操作
     * @ param array $info 商品信息
     * @ param object $user_resource 买家信息
     * @ param int $num 购买数量
     * @ return bool true | false
     */
    public function buyGoods($info, $user_resource, $num = 1)
    {
        try {
            $this->startTrans();

            //减少资源
            $user_resource->top_lingshi = $user_resource->top_lingshi - $info['price'] * $num;
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
                'type' => 3,
                'goods_type' => array_search($info['table'], $this->getType('goods_buy')),
                'goods_id' => $info[$info['table'] . '_id'],
                'num' => $num
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