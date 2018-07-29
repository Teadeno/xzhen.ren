<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:29
 */

namespace app\api\model;

use think\Request;

//资源表
class Resource extends Base
{
    protected $table = 'resource';
    protected $pk = 'resource_id';
    protected $comment = '资源表';


    public function getImgUrlAttr($value)
    {
        return Request::instance()->domain() . $value;
    }

    /**
     * 使用资源
     * @param int $user_id 玩家ID
     * @param int $id 资源ID
     * @param int $num 使用数量
     * @return bool true | false
     */
    public function useResource($user_id, $id, $num)
    {
        $resource_info = $this->findId($id)->toArray();
        $resource_info['value'] = $resource_info['value'] * $num;
        if (!$this->addResource($user_id, $resource_info['type'], $resource_info['value'], 'resource')) return false; //属性增加

        return true;
    }

}