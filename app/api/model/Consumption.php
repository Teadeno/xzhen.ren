<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:22
 */

namespace app\api\model;

//阵法消耗资源表
class Consumption extends Base
{
    protected $table = 'consumption';
    protected $pk = 'consumption_id';
    protected $comment = '阵法消耗资源表';

}