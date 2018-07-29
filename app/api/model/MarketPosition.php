<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:27
 */

namespace app\api\model;

//坊市职位表
class MarketPosition extends Base
{
    protected $table = 'market_position';
    protected $pk = 'position_id';
    protected $comment = '坊市职位表';

    /**
     *集市职位挑战
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
                $info->user_id = $user_id;
                $info->sex = $user['sex'];
                $info->data['name'] = $user['username'];
                if (!$info->save()) return false;
                $user_resource->market = $info->position_id;
                if (!$user_resource->save()) return false;

                $this->commit();
                return true;
            } catch (Exception $exception) {
                $this->rollback();
                return false;
            }
        } else { //失败
            $user_resource->prestige = $user_resource->prestige - 2000;
            if (!$user_resource->save()) return false;
        }
    }

}