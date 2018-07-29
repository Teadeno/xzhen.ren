<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:28
 */

namespace app\api\model;

//价格表
class Price extends Base
{
    protected $table = 'price';
    protected $pk = 'id';
    protected $comment = '消耗表';

}