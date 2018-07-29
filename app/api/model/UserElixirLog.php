<?php


namespace app\api\model;


class UserElixirLog extends Base
{
    protected $table = 'user_elixir_log';
    protected $pk = 'id';
    protected $comment = '玩家使用丹药记录表';

    public function saveData($where, $num)
    {
        if (empty($where) || empty($num)) return false;
        if ($data = $this->findMap($where)) {
            $data->num = (int)$data->num + $num['num'];
            if (!$data->save()) return false;
        } else {
            $insert = array_merge($where, $num);
            if (!static::create($insert)) return false;
        }
        return true;
    }
}