<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:32
 */

namespace app\api\model;

//门派职位表
class SchoolPosition extends Base
{
    protected $table = 'school_position';
    protected $pk = 'position_id';
    protected $comment = '门派职位表';

    /**
     * f_id下一等级 数组<=>字符转换
     * @param $value
     * @param $data
     * @return string
     */
    public function getFIdAttr($value)
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
     *掌门，供奉挑战
     * @param int $user_id 玩家ID
     * @param int $position_id 职位ID
     * @param bool $status 挑战状态
     * @return array
     * */
    public function setPosition($user_id, $position_id, $status)
    {

        $user_resource = UserResource::findMap(['user_id' => $user_id]);
        $info = $this->findId($position_id);

        if ($status) {  //成功
            $user_attribute = $this->getUserAttribute($user_id);
            $user = User::findMap(['user_id' => $user_id])->toArray();
            try {
                $this->startTrans();
                //职位属性变更
                $info->vita = $user_attribute['vita'];
                $info->attack = $user_attribute['attack'];
                $info->defense = $user_attribute['defense'];
                $info->speed = $user_attribute['speed'];
                $info->critical_strike = $user_attribute['critical_strike'];
                $info->resistance = $user_attribute['resistance'];
                $info->hit = $user_attribute['hit'];
                $info->dodge = $user_attribute['dodge'];
                $info->sex = $user['sex'];
                $info->data['name'] = $user['username'];
                if (!$info->save()) return false;
                $user_resource->position_id = $info->position_id;
                if (!$user_resource->save()) return false;
                switch ($info->level) {
                    case 9:
                        $res = School::editMapData(['school_id' => $info->school_id], ['leader' => $user_id]);
                        break;
                    case 8:
                        $res = School::editMapData(['school_id' => $info->school_id], ['manager7' => $user_id]);
                        break;
                    case 7:
                        $res = School::editMapData(['school_id' => $info->school_id], ['manager8' => $user_id]);
                        break;
                }
                if (!$res) return false;
                $this->commit();
                return true;
            } catch (Exception $exception) {
                $this->rollback();
                return false;
            }


        } else { //失败
            $user_resource->school_contribution = $user_resource->school_contribution - $info->price;
            if (!$user_resource->save()) return false;
        }
        return true;
    }

    protected function setFIdAttr($value, $data)
    {
        return implode(',', $value);
    }

}