<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:12
 */

namespace app\api\model;

//用户背包表
class UserKnapsack extends Base
{
    protected $table = 'user_knapsack';
    protected $pk = 'knapsack_id';
    protected $comment = '用户背包表';
}