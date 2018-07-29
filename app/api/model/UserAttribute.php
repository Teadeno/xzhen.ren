<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:07
 */

namespace app\api\model;

//用户属性表
class UserAttribute extends Base
{
    protected $table = 'user_attribute';
    protected $pk = 'attribute_id';
    protected $comment = '用户属性表';

    /**
     * esoterica_id功法 数组<=>字符转换
     * @param $value
     * @param $data
     * @return string
     */
    public function getEsotericaIdAttr($value)
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
     * skill_id神通 数组<=>字符转换
     * @param $value
     * @param $data
     * @return string
     */
    public function getSkillIdAttr($value)
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
     * tactical_id阵法 数组<=>字符转换
     * @param $value
     * @param $data
     * @return string
     */
    public function getTacticalIdAttr($value)
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

    protected function setEsotericaIdAttr($value, $data)
    {
        return implode(',', $value);
    }

    protected function setSkillIdAttr($value, $data)
    {
        return implode(',', $value);
    }

    protected function setTacticalIdAttr($value, $data)
    {
        return implode(',', $value);
    }

}