<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:18
 */

namespace app\api\model;

//怪物表
class Zoology extends Base
{
    protected $table = 'zoology';
    protected $pk = 'zoology_id';
    protected $comment = '怪物表';

}