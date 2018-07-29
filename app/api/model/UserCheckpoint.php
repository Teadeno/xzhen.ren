<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:09
 */

namespace app\api\model;


class UserCheckpoint extends Base
{
    protected $table = 'user_checkpoint';
    protected $pk = 'id';
    protected $comment = '用户关卡表';
}