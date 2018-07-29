<?php


namespace app\api\model;


use think\Exception;

class UserInvite extends Base
{
    protected $table = 'user_invite';
    protected $pk = 'id';
    protected $comment = '接引玩家表';


    /**
     * 玩家接引人填写
     * @param object $user 填写玩家
     * @param object $f_user 邀请玩家
     * @return bool true | false
     */
    public function setInvite($user, $f_user)
    {
        try {
            $this->startTrans();
            //填写者增加记录
            if (!$user->save(['f_id' => $f_user->user_id])) return false;
            //邀请者增加记录
            $invite = $this->findMap(['user_id' => $f_user->user_id], 'invite_list');
            if (empty($invite)) {
                //新增一条数据
                if (!$this::create(['user_id' => $f_user->user_id, 'invite_list' => $user->username])) return false;
            } else {
                //修改数据
                $invite->invite_list = $invite->invite_list . ',' . $user->username;
                if (!$invite->isUpdate(true)->save()) return false;
            }
            //发送邮箱奖励
            $award_id = Activity::findMap(['type' => 1])->toArray()['award_id'];  //接引奖励
            $title = '接引奖励';
            $content = $user->username . '经过你的接引，进入修真世界';
            if (!$this->sendEmail($f_user->user_id, $award_id, $title, $content)) return false;
            $content = '你寻找到志同道合的道友';
            if (!$this->sendEmail($user->user_id, $award_id, $title, $content)) return false;
            $this->commit();
            return true;
        } catch (Exception $exception) {
            $this->rollback();
            return false;
        }
    }

}