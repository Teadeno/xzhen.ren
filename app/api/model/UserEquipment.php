<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:11
 */

namespace app\api\model;

//用户装备表
class UserEquipment extends Base
{
    protected $table = 'user_equipment';
    protected $pk = 'id';
    protected $comment = '用户装备表';

}