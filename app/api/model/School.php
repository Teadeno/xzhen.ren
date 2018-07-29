<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:31
 */

namespace app\api\model;

//门派表
class School extends Base
{
    protected $table = 'school';
    protected $pk = 'school_id';
    protected $comment = '门派表';


}