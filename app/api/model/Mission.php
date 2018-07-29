<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:27
 */

namespace app\api\model;

//任务表
class Mission extends Base
{
    protected $table = 'mission';
    protected $pk = 'mission_id';
    protected $comment = '门派任务表';

}