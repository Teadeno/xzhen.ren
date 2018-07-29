<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:10
 */

namespace app\api\model;

//用户王朝资源表
class UserDynasty extends Base
{
    protected $table = 'user_dynasty';
    protected $pk = 'dynasty_id';
    protected $comment = '用户王朝表';

    /**
     * building_id建筑 数组<=>字符转换
     * @param $value
     * @param $data
     * @return string
     */
    public function getBuildingIdAttr($value)
    {

        $data = [];
        if (!empty($value)) {
            $info = explode(',', $value);
            foreach ($info as $value) {
                $data[$value] = $value;
            }
        }

        return $data;
    }

    protected function setBuildingIdAttr($value, $data)
    {
        return implode(',', $value);
    }
}