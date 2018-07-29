<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:23
 */

namespace app\api\model;

//丹药表

use think\Loader;
use think\Request;

class Elixir extends Base
{
    protected $table = 'elixir';
    protected $pk = 'elixir_id';
    protected $comment = '丹药表';

    public function getImgUrlAttr($value)
    {
        return Request::instance()->domain() . $value;
    }

    /**
     * 使用丹药
     * @param int $user_id 玩家ID
     * @param int $id 丹药ID
     * @param int $num 使用数量
     * @return bool true | false
     */
    public function useElixir($user_id, $id, $num)
    {
        $data = $this->findId($id, 'name, type, value, level');
        $data['value'] = $data['value'] * $num;
        if (!$this->addAttribute($user_id, $data['type'], $data['value'], 'elixir')) return false; //属性增加
        $user_elixir_log = [
            'user_id' => $user_id,
            'level' => $data['level'],
            'type' => $data['type'],
        ];
        if (!Loader::model('UserElixirLog')->saveData($user_elixir_log, ['num' => $num])) return false;  //使用丹药记录

        return true;
    }

}