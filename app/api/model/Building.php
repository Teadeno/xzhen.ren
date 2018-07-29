<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:21
 */

namespace app\api\model;

//建筑表
class Building extends Base
{
    protected $table = 'building';
    protected $pk = 'building_id';
    protected $comment = '建筑表';

}