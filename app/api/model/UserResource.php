<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:13
 */

namespace app\api\model;

//用户资源表
class UserResource extends Base
{
    protected $table = 'user_resource';
    protected $pk = 'resource_id';
    protected $comment = '用户资源表';

}