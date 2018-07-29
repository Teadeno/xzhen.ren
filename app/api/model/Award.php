<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:19
 */

namespace app\api\model;

//奖励表
class Award extends Base
{
    protected $table = 'award';
    protected $pk = 'id';
    protected $comment = '奖励表';
}