<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:21
 */

namespace app\api\model;


class Checkpoint extends Base
{
    protected $table = 'checkpoint';
    protected $pk = 'checkpoin_id';
    protected $comment = '关卡表';
}