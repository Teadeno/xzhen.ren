<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/15
 * Time: 11:45
 */

namespace app\api\controller;

use think\Loader;

class Email extends Base
{
    /**
     *用户邮箱列表
     * */
    public function email()
    {
        $map['user_id'] = $this->user_id;
        $field = 'id, title, is_read';
        $order = 'is_read, create_time desc';
        $list = Loader::model('email')->getListByMap($map, $field, $order);
        return $this->showReturnCode(0, $list);
    }

    /**
     *邮箱点击查看
     * */
    public function look_email()
    {
        $id = $this->post['id'];

        $M = Loader::model('email');
        $M->where('id', $id)->setField('is_read', 1);

        $field = ['is_del, user_id', true];
        $data = $M->findId($id, $field)->toArray();
        //判断邮件是否领取  已领取不显示奖励物品
        if ($data['is_get'] == 1) {
            $data['award'] = [];
            return $this->showReturnCode(0, $data);
        }
        //根据奖励ID获取奖励物品，调用公共方法
        $data['award'] = $M->getAwardList($data['award_id']);
        return $this->showReturnCode(0, $data);
    }

    /**
     *邮箱礼物领取
     * */
    public function get_email()
    {
        $id = $this->post['id'];
        $map = [
            'id' => $id,
            'user_id' => $this->user_id
        ];
        $M = Loader::model('email');
        //获取邮箱奖励id
        $email = $M->findMap($map)->toArray();
        if (!$email) return $this->showReturn('参数错误');
        if ($email['is_get'] === 1) return $this->showReturn('已领取');

        //获取奖励
        if (empty($email['award_id'])) {
            $data = $M->getAwardList($email['award_id'], true);
            //领取奖励
            if (!$M->getAward($data, $this->user_id)) return $this->showReturn('领取失败');
        }

        $M->where($map)->update(['is_get' => 1]);
        return $this->showReturnCode(0, ['status' => 1]);
    }

    /**
     * 邮件删除*/
    public function del_email()
    {
        $id = $this->post['id'];
        if (!isset($this->post['id'])) {
            return $this->showReturnWithCode(1001);
        }
        $M = new \app\api\model\Email();
        $email = $M->findId($this->post['id']);
        //判断邮箱是否领取，未领取不允许删除
        if ($email->is_get == 0 && !empty($email->award_id)) return $this->showReturn('奖励未领取');
        //查找邮件并删除邮件奖励
        if ($M->delEmail($this->post['id'], $email)) return $this->showReturn('网络错误');

        return $this->showReturnCode(0, ['status' => 1]);
    }

}