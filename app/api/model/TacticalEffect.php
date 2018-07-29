<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:35
 */

namespace app\api\model;

//阵法效果表
class TacticalEffect extends Base
{
    protected $table = 'tactical_effect';
    protected $pk = 'effect_id';
    protected $comment = '阵法消耗表';
}