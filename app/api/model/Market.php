<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:25
 */

namespace app\api\model;

//集市表
use think\Exception;

class Market extends Base
{
    protected $table = 'market';
    protected $pk = 'id';
    protected $comment = '坊市表';

    /**
     * 集市商品购买操作
     * @ param array $info 商品信息
     * @ param array $user_resource 买家信息
     * @ return bool true | false
     */
    public function buyGoods($info, $user_resource)
    {
        try {
            $this->startTrans();
            //购买操作  买家增加相应资源，减少灵石数量，卖家增加相应灵石
            $this->commit();
            return true;
        } catch (Exception $exception) {

            $this->rollback();
            return false;
        }
    }
}