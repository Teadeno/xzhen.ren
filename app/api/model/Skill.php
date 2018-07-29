<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:32
 */

namespace app\api\model;

//神通表
class Skill extends Base
{
    protected $table = 'skill';
    protected $pk = 'skill_id';
    protected $comment = '神通表';


    /**
     *玩家神通
     * @param array | int $skill_id 神通id
     * @param string $field 获取字段
     * @param bool $status 获取当前神通还是下级神通
     * @return array
     * */
    public function getUserSkill($skill_id, $field = '', $status = false)
    {
        if (!is_array($skill_id)) $skill_id = explode(',', $skill_id);

        $data = $this->getListById(['in', $skill_id], $field);
        if ($status) {
            $ids = [];
            foreach ($data as $value) {
                if ($value['f_id'] == 0) {
                    $ids[] = $value['skill_id'];
                } else {
                    $ids[] = $value['f_id'];
                }

            }
            $data = $this->getListById(['in', $ids], $field);
        }
        return $data;
    }

    /**
     *玩家学习神通
     * @param  int $user_id 玩家ID
     * @param int $id 神通ID
     * @param object $info 神通对象
     * @return array
     * */
    public function studySkill($user_id, $id, $info = null)
    {
        if (empty($info)) $info = $this->findId($id);
        $user_attribute = UserAttribute::findMap(['user_id' => $user_id]);
        if ($info->level == 1) { //学习
            $skill_id = $user_attribute->skill_id;
            $skill_id [] = $info->skill_id;
            $user_attribute->skill_id = $skill_id;
            if (!$user_attribute->save()) return false;
        } else {  //升级
            $p_id = $this->findMap(['f_id' => $info->skill_id], 'skill_id')->skill_id;

            $skill_id = $user_attribute->skill_id;  //功法列表修改
            if (!array_key_exists($p_id, $skill_id)) return false;

            $skill_id[$p_id] = $info->skill_id;   // 上级id  替换为升级id
            $user_attribute->skill_id = $skill_id;
            if (!$user_attribute->save()) return false;

        }
        return true;
    }
}