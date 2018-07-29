<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:23
 */

namespace app\api\model;

// 邮箱表
use think\Exception;

class Email extends Base
{
    protected $table = 'email';
    protected $pk = 'id';
    protected $comment = '邮箱表';


    /**
     * 删除邮件
     * @param int $id 邮箱ID
     * @param object $info 邮箱对象
     * @return bool true | false
     */
    public function delEmail($id, $info = null)
    {
//        if (empty($info)) $info = $this->findId($id);
//        $info = $info->toArray();
        try {
            $this->startTrans();
            /* if (!empty($info['award_id'])) {  //删除奖励
                 $award = Award::getListByMap(['award_id' => $info['award_id']]);
                 foreach ($award as $value) {
                     if ($value['is_goods'] == 1) {
                         $value['award_goods_id'] = explode(',', $value['award_goods_id']);

                         if (!AwardGoods::destroy($value['award_goods_id'])) return false;
                     }
                 }
                 if (!$d = Award::destroy(['award_id' => $info['award_id']])) return false;
             }*/
            //删除邮件
            if (!$this->destroy($id)) return false;
            $this->commit();
            return true;
        } catch (Exception $exception) {
            $this->rollback();
            return false;
        }

    }
}