<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:57
 */

namespace app\api\behavior;

use think\Db;

class Log
{

    //资源变化记录
    public function resourceLog(&$params)
    {
        if (count($params) == count($params, 1)) {
            Db::name('resource_log')->insert($params);
        } else {
            Db::name('resource_log')->insertAll($params);
        }
        $params = null;
    }

    //用户日志变化记录
    public function userLog(&$params)
    {
        $params = 1;
    }

    //集市交易记录
    public function marketLog(&$params)
    {

    }

    //用户属性变化记录变化记录
    public function attributeLog(&$params)
    {

    }

    //王朝资源变化记录
    public function dynastyLog(&$params)
    {

    }

    //挂图日志记录
    public function wallMapLog(&$params)
    {

    }

    //使用丹药记录
    public function userElixirLog(&$params)
    {

    }

    //物品购买记录
    public function goodsBuyLog(&$params)
    {
        if (count($params) == count($params, 1)) {
            Db::name('goods_buy_log')->insert($params);
        } else {
            Db::name('goods_buy_log')->insertAll($params);
        }
        $params = null;
    }
}