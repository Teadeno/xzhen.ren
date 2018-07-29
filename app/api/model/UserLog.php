<?php


namespace app\api\model;

class UserLog extends Base
{
    protected $table = 'user_log';
    protected $pk = 'log_id';
    protected $comment = '用户日志表';
    protected $insert = ['time'];

    public function setTimeAttr($value, $data)
    {
        $create_time = User::findMap(['user_id' => $data['user_id']], 'create_time')->toArray()['create_time'];
        return static::getTime(time(), $create_time) . '年';
    }

    protected function getUsernameAttr($value, $data)
    {
        return User::findMap(['user_id' => $data['user_id']], 'username')->toArray()['username'];
    }
}