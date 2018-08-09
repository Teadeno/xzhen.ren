<?php


namespace app\api\controller;


use app\api\model\Activity;
use app\api\model\UserResource;
use app\api\model\WallMap;
use think\Db;
use think\Request;
use think\Controller;

class Notify extends Controller
{
    /**
     * 成长基金
     */
    private function growthund($user_id)
    {
        //直接获取2000顶级灵石
        //修改user_Resource表的成长基本字段
        $flog = true;
        //直接获取1000顶级灵石
        $user = $user_id;
        $award_id = Activity::findMap(['type' => 3])->toArray()['award_id'];
        $title = "成长基金福利";
        $content = "恭喜你获取成长基金，请收下奖励";
        if (!$this->sendEmail($user, $award_id, $title, $content)) {
            $flog = false;
        }
        if (!UserResource::editMapData(['user_id' => $user], ['grow_award' => 1])) {
            $flog = false;
        }
        
        if ($flog) {
            $str = "用户" . $user_id . '成长基金奖励已发放成功' . date('Y-m-d H:i:s') . '<br/>';
        } else {
            $str = "用户" . $user_id . '成长基金奖励已发放失败' . date('Y-m-d H:i:s') . '<br/>';
        }
        file_put_contents('Activity.log', $str, FILE_APPEND);
        return $flog;
    }
    
    /**
     * 月卡
     */
    private function monthVip($user_id)
    {
        $flog = true;
        //直接获取1000顶级灵石
        $user = $user_id;
        $award_id = Activity::findMap(['type' => 4])->toArray()['award_id'];
        $title = "月卡福利";
        $content = "恭喜你成功月卡会员用户，请收下奖励";
        if (!$this->sendEmail($user, $award_id, $title, $content)) {
            $flog = false;
        }
        if (!UserResource::editMapData(['user_id' => $user], ['month_num' => time()])) {
            $flog = false;
        }
        //当天进入挂图 则增加
        $wall = WallMap::findMap(['user_id' => $user]);
        if (!empty($wall)) {
            $wall->count += 10;
            $wall->save();
        }
        if ($flog) {
            $str = "用户" . $user_id . '月卡奖励已发放成功' . date('Y-m-d H:i:s') . '<br/>';
        } else {
            $str = "用户" . $user_id . '月卡奖励已发放失败' . date('Y-m-d H:i:s') . '<br/>';
        }
        file_put_contents('Activity.log', $str, FILE_APPEND);
        return $flog;
        //修改user_Resource表的月卡字段
        
    }
    
    /**
     * 终身卡
     */
    private function everVip($user_id)
    {
        //直接获取2000顶级灵石
        //修改user_Resource表的终身卡字段
        $flog = true;
        //直接获取1000顶级灵石
        $user = $user_id;
        $award_id = Activity::findMap(['type' => 5])->toArray()['award_id'];
        $title = "终身卡福利";
        $content = "恭喜你成功终身会员用户，请收下奖励";
        if (!$this->sendEmail($user, $award_id, $title, $content)) {
            $flog = false;
        }
        if (!UserResource::editMapData(['user_id' => $user], ['vip' => 1])) {
            $flog = false;
        }
        
        if ($flog) {
            $str = "用户" . $user_id . '终身卡奖励已发放成功' . date('Y-m-d H:i:s') . '<br/>';
        } else {
            $str = "用户" . $user_id . '终身卡奖励已发放失败' . date('Y-m-d H:i:s') . '<br/>';
        }
        file_put_contents('Activity.log', $str, FILE_APPEND);
        return $flog;
    }
    
    /**
     * 首充
     */
    private function first($user_id)
    {
        //获取首充奖励
        //发放奖励
        $flog = true;
        //直接获取1000顶级灵石
        $user = $user_id;
        $award_id = Activity::findMap(['type' => 2])->toArray()['award_id'];
        $title = "首冲福利";
        $content = "首次充值，赠送奖励";
        if (!$this->sendEmail($user, $award_id, $title, $content)) {
            $flog = false;
        }
//        if (!UserResource::editMapData(['user_id'=>$user],['vip'=>1])){
//            $flog = false;
//        }
        
        if ($flog) {
            $str = "用户" . $user_id . '首次充值奖励已发放成功' . date('Y-m-d H:i:s') . '<br/>';
        } else {
            $str = "用户" . $user_id . '首次充值奖励已发放失败' . date('Y-m-d H:i:s') . '<br/>';
        }
        file_put_contents('Activity.log', $str, FILE_APPEND);
        return $flog;
    }
    
    /**
     * 微信支付回调
     */
    public function wx_notify()
    {
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        file_put_contents('wxpay.txt', $xml);
        vendor('wxpay.WxPay.Api');
        $data = \WxPayResults::Init($xml);
        $wxPayData = new \WxPayDataBase();
        if (!$data) {
            $return = ['return_code' => 'FAIL', 'return_msg' => '数据错误'];
            return \WxPayApi::replyNotify($wxPayData->ToXml($return));
        }
        
        if (!array_key_exists("transaction_id", $data)) {
            $return = ['return_code' => 'FAIL', 'return_msg' => '输入参数不正确'];
            return \WxPayApi::replyNotify($wxPayData->ToXml($return));
        }
        $transaction_id = $data['transaction_id'];
        $checkOrder = $this->queryOrder($transaction_id);
        if (!$checkOrder) {
            $return = ['return_code' => 'FAIL', 'return_msg' => '订单查询失败'];
            return \WxPayApi::replyNotify($wxPayData->ToXml($return));
        }
        
        $order_sn = $data['out_trade_no'];
        $order = Db::name('order')->where('order_sn', $order_sn)->find();
        if (!$order) {
            $return = ['return_code' => 'FAIL', 'return_msg' => 'order is not find'];
            return \WxPayApi::replyNotify($wxPayData->ToXml($return));
        }
        if ($order['pay_status'] == 2) {
            return 'success';
        }
        
        $param = [];
        $param['pay_status'] = 2;
        $param['transaction_id'] = $data['transaction_id'];
        $param['receipt_amount'] = $data['total_fee'];
        $param['buyer_id'] = $data['openid'];
        $param['time_end'] = $data['time_end'];
        $result = $this->upOrder($param, $order_sn, $order);  // 更新
        if ($result == true) {
            $return = ['return_code' => 'SUCCESS', 'return_msg' => 'OK'];
            return \WxPayApi::replyNotify($wxPayData->ToXml($return));
        }
        $return = ['return_code' => 'FAIL', 'return_msg' => 'error'];
        return \WxPayApi::replyNotify($wxPayData->ToXml($return));
    }
    
    private function queryOrder($transaction_id)
    {
        vendor('wxpay.WxPay.Api');
        $checkOrder = new \WxPayOrderQuery();
        $checkOrder->SetTransaction_id($transaction_id);
        $result = \WxPayApi::orderQuery($checkOrder);
        if (array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS") {
            return true;
        }
        return false;
    }
    
    private function upOrder($param = [], $order_sn = '', $order = '')
    {
        Db::startTrans();
        $res = Db::name("order")->where("order_sn", $order_sn)->update($param);
        //业务处理
        if ($res) {
            Db::commit();
        } else {
            Db::rollback();
        }
        $info = Db::name('order')->where("order_sn", $order_sn)->find();
        if ($info['pay_status'] == 2) {
            switch ($info['type']) {  //	类型1成长基金2月卡3终身卡4充值
                case 1:
                    $res = $this->growthund($info['user_id']);
                    break;
                case 2:
                    $res = $this->monthVip($info['user_id']);
                    break;
                case 3:
                    $res = $this->everVip($info['user_id']);
                    break;
                case 4:
                    $user_resource = Db::name('user_resource')->where('user_id', $info['user_id'])->find();
                    $update['top_lingshi'] = $user_resource['top_lingshi'] + $info['num'];
                    $res = Db::name('user_resource')->where('user_id', $info['user_id'])->update($update);
                    break;
            }
            if ($res) {
                $user_resource = Db::name('user_resource')->where('user_id', $info['user_id'])->find();
                if ($user_resource['rmb'] == 0) {
                    $this->first($user_resource['user_id']);
                }
                $update['rmb'] = $user_resource['rmb'] + $info['receipt_amount'];
                Db::name('user_resource')->where('user_id', $info['user_id'])->update($update);
            }
            return true;
        }
    }
}