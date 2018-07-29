<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:34
 */

namespace app\api\model;

//阵法表
class Tactical extends Base
{
    protected $table = 'tactical';
    protected $pk = 'tactical_id';
    protected $comment = '阵法表';

    /**
     * consumption_id 资源消耗 数组<=>字符转换
     * @param $value
     * @param $data
     * @return string
     */
    public function getConsumptionIdAttr($value)
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

    /**
     * 阵法更换
     * @param int $type 阵法类型
     * @param int $lavel 阵法等级
     * @return bool true | false
     * */
    public function tacticalLoading($user_id, $type, $lavel)
    {
        $id = $this->findMap(['type' => $type, 'level' => $lavel])->toArray()['tactical_id'];
        if (!UserAttribute::editMapData(['user_id' => $user_id], ['tactical' => $id])) return false;

        return true;
    }

    protected function setConsumptionIdAttr($value, $data)
    {
        return implode(',', $value);
    }
}