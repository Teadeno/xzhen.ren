<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/7/2
 * Time: 10:58
 */

namespace app\api\model;

use think\Request;

class Dynasty extends Base
{
    protected $table = 'dynasty';
    protected $pk = 'dynasty_id';
    protected $comment = '王朝资源表';

    public function getImgUrlAttr($value)
    {
        return Request::instance()->domain() . $value;
    }

    /**
     * 使用王朝资源
     * @param int $user_id 玩家ID
     * @param int $id 资源ID
     * @param int $num 使用数量
     * @return bool true | false
     */
    public function useDynasty($user_id, $id, $num)
    {
        $resource_info = $this->findId($id)->toArray();
        $resource_info['value'] = $resource_info['value'] * $num;

        if (!$this->addDynasty($user_id, $resource_info['type'], $resource_info['value'], 'dynasty')) return false; //属性增加

        return true;
    }
}