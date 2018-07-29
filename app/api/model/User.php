<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:03
 */

namespace app\api\model;

//用户表
use think\Exception;

class User extends Base
{
    protected $table = 'user';
    protected $pk = 'user_id';
    protected $comment = '用户表';
    protected $insert = ['invite', 'ip'];

    /*   public function getSexAttr($value)
       {
           $status = [1=>'男',2=>'女'];
           return $status[$value];
       }*/

    /**
     * 增加用户信息
     * @param array $data 用户信息
     * @return bool true|false
     */
    public function addUserInfo($post)
    {
        try {
            $this->startTrans();

            $data = [
                'username' => $post['username'],
                'sex' => $post['sex'],
                'device' => $post['device'],
                'source' => 1, // 写死先
                'version' => $post['version'],
            ];
            //微信注册
            if (isset($post['open_id']) && !empty($post['open_id']) && $post['type'] == 2) {
                $data['open_id'] = $post['open_id'];
                if (!$user = $this->create($data)) {
                    return false;
                }
            }
            //账号注册
            if (isset($post['user_id']) && !empty($post['user_id']) && $post['type'] == 1) {
                if (!$user = $this->save($data, ['user_id' => $post['user_id']])) {
                    return false;
                }
                $user = $this->findId($post['user_id']);
            }

            $user_resource = [
                'user_id' => $user->user_id,
                'lingshi' => 0,
                'quality' => 0,
                'quality_edit_time' => time(),
                'prestige' => 0,
                'school_contribution' => 0,
                'top_lingshi' => 0,
                'rmb' => 0,
                'month_num' => 0,
                'grow_award' => 0,
                'skill' => 0,
                'wall_map_num' => 0,
                'wall_map_wheel' => 10,
                'cloned' => 0,
                'knapsack_num' => 30,
                'realm_id' => 1,
                'school_id' => 0,
                'position_id' => 0,
                'market' => 0,
            ];
            if (!$user_resource = UserResource::create($user_resource)) {
                return false;
            }
            $info = Realm::findMap(['realm_id' => $user_resource->realm_id]);
            $user_attribute = [
                'user_id' => $user->user_id,
                'practice_speed' => $info['practice_speed'],
                'vita' => $info['vita'],
                'attack' => $info['attack'],
                'defense' => $info['defense'],
                'dodge' => $info['dodge'],
                'speed' => $info['speed'],
                'critical_strike' => $info['critical_strike'],
                'resistance' => $info['resistance'],
                'hit' => $info['hit'],
                'ratio' => $info['ratio'],
            ];
            if (!UserAttribute::create($user_attribute)) {
                return false;
            }
            $this->commit();
        } catch (Exception $exception) {
            $this->rollback();
            return false;
        }
        //发送邮件
        $title = "新手攻略";
        $award_id = '';
        $content = $this->getUserLogContent('user', 0);
        if (!$this->sendEmail($user->user_id, $award_id, $title, $content)) {
            return false;
        }
        $user_log = [
            'user_id' => $user->user_id,
            'type' => 6,
            'content' => $this->getUserLogContent('realm', 0, $info->toArray()['name'])
        ];
        if (!UserLog::create($user_log)) {
            return false;
        }
        return true;

    }

    /**
     * source注册来源
     * @param $value
     * @param $data
     * @return string
     */
    protected function setSourceAttr($value, $data)
    {
        switch ($value) {
            case 1:
                return 'Android';
                break;
            case 2:
                return 'IOS';
                break;
        }
    }

    /**
     * ip
     * @param $value
     * @param $data
     * @return string
     */
    protected function setIpAttr($value, $data)
    {

        return request()->ip();
    }

    /**
     * 邀请码 含防重复筛查
     * @param $value
     * @param $data
     * @return string
     */
    protected function setInviteAttr($value, $data)
    {
        do {
            $invite = $this->createInvite(time());
        } while ($this->where('invite', $invite)->count() >= 1);
        return $invite;
    }

}