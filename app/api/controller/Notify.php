<?php


namespace app\api\controller;


use app\api\model\Activity;
use app\api\model\UserResource;
use app\api\model\WallMap;
use think\Db;
use think\Request;
use think\Controller;
use think\Loader;
use think\Log;
class Notify extends Controller
{
    /**
     * 发送邮件
     * @ param array $award
     * @ return bool
     */
    public static function sendEmail($user, $award_id, $title, $content)
    {
        if (!is_array($user)) {
            $user = explode(',', $user);
        }
        foreach ($user as $value) {
            $data[] = [
                'user_id' => $value,
                'title' => $title,
                'content' => $content,
                'award_id' => $award_id,
                'is_read' => 0,
                'is_get' => 0,
            ];
        }
        if (!Loader::model('Email')->saveAll($data)) return false;
        return true;
    }
    
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
        if (!UserResource::editMapData(['user_id' => $user], ['grow_award' => 12])) {
            $flog = false;
        }
        
        if ($flog) {
            $str = "用户" . $user_id . '成长基金奖励已发放成功' . date('Y-m-d H:i:s') . "\\r\\n";
        } else {
            $str = "用户" . $user_id . '成长基金奖励已发放失败' . date('Y-m-d H:i:s') . "\\r\\n";
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
            $str = "用户" . $user_id . '月卡奖励已发放成功' . date('Y-m-d H:i:s') . "\\r\\n";
        } else {
            $str = "用户" . $user_id . '月卡奖励已发放失败' . date('Y-m-d H:i:s') . "\\r\\n";
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
            $str = "用户" . $user_id . '终身卡奖励已发放成功' . date('Y-m-d H:i:s') . "\\r\\n";
        } else {
            $str = "用户" . $user_id . '终身卡奖励已发放失败' . date('Y-m-d H:i:s') ."\\r\\n";
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
            $str = "用户" . $user_id . '首次充值奖励已发放成功' . date('Y-m-d H:i:s') . "\\r\\n";
        } else {
            $str = "用户" . $user_id . '首次充值奖励已发放失败' . date('Y-m-d H:i:s') . "\\r\\n";
        }
        file_put_contents('Activity.log', $str, FILE_APPEND);
        return $flog;
    }
    
    /**
     * 微信支付回调
     */
    public function wx_notify()
    {
    
        $xml = file_get_contents('php://input');
        vendor('wxpay.WxPayApi');
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
            return \WxPayApi::replyNotify($this->ToXml($return));
        }
        $return = ['return_code' => 'FAIL', 'return_msg' => 'error'];
        return \WxPayApi::replyNotify($this->ToXml($return));
    }
    
    public function ToXml($return)
    {
        if (!is_array($return)
            || count($return) <= 0) {
            throw new WxPayException("数组数据异常！");
        }
        
        $xml = "<xml>";
        foreach ($return as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
    private function queryOrder($transaction_id)
    {
        vendor('wxpay.WxPayApi');
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
    
    /**
     * 支付宝回调
     */
    public function ail_notify(Request $request)
    {
        $data = $request->post();
        $conf = config('pay.alipay');
        vendor('alipay.aop.AopClient');
        $aop = new \AopClient;
        $aop->alipayrsaPublicKey = $conf['alipayrsaPublicKey'];
        $signType = $conf['signType'];
        //验证签名
        $flag = $aop->rsaCheckV1($_POST, NULL, $signType);
        if ($flag) {
            $order_sn = $data['out_trade_no'];
            $order = Db::name('order')->where('order_sn', $order_sn)->find();
            if (!$order) {
                return 'order is not find';
            }
            if ($order['pay_state'] == 2) {
                return 'success';
            }
            
            $param = [];
            $param['pay_status'] = 2;
            $param['transaction_id'] = $data['trade_no'];
            $param['receipt_amount'] = $data['total_amount'];
            $param['buyer_id'] = isset($data['buyer_id']) ? $data['buyer_id'] : '';
            $param['time_end'] = isset($data['gmt_payment']) ? $data['gmt_payment'] : '';
            $result = $this->upOrder($param, $order_sn, $order);  // 更新
            if ($result == true) {
                return 'success';
            }
        }
        return 'error';
    }
    
    /**
     * 百度回调  暂未启用
     */
    public function any_notify()
    {
        defined('LOGIN_CHECK_URL') or define('LOGIN_CHECK_URL', 'http://oauth.anysdk.com/api/User/LoginOauth/');
        defined('ADTRACKING_REPORT_URL') or define('ADTRACKING_REPORT_URL', 'http://pay.anysdk.com/v5/AdTracking/Submit/');
        defined('DEBUG_MODE') or define('DEBUG_MODE', false);
    
        // 游戏ID              前往dev.anysdk.com => 游戏列表 获取
        defined('ANYSDK_GAME_ID') or define('ANYSDK_GAME_ID', 639797331);
        // 增强密钥             前往dev.anysdk.com => 游戏列表 获取，此参数请严格保密
        defined('ANYSDK_ENHANCED_KEY') or define('ANYSDK_ENHANCED_KEY', 'ODNjNmY3ZWEyMWY1MWY3ZGZhNTA');
        // private_key        前往dev.anysdk.com => 游戏列表 获取
        defined('ANYSDK_PRIVATE_KEY') or define('ANYSDK_PRIVATE_KEY', '58D00BD80CF7AB095318C357D140300A');
        /**
         * 如果你想配合dev后台的“模拟通知游服”功能进行内网调试，请取消注释此响应头设置代码。
         *  header("Access-Control-Allow-Origin: http://dev.anysdk.com");
         */
    
        $payment_params = $_POST;
        Log::record('[request_data] ' . var_export($payment_params, true), 'Notify_any');
    
        $anysdk = new \Sdk_AnySDK(ANYSDK_ENHANCED_KEY, ANYSDK_PRIVATE_KEY);
    
        /**
         * 设置调试模式
         */
        $anysdk->setDebugMode(\Sdk_AnySDK::DEBUG_MODE_ON);
    
        /**
         * ip白名单检查
         * $anysdk->pushIpToWhiteList('127.0.0.1');
         * $anysdk->checkIpWhiteList() or die(Sdk_AnySDK::PAYMENT_RESPONSE_FAIL . 'ip');
         */
    
        /**
         * SDK默认只检查增强签名，如果要检查普通签名和增强签名，则需要此设置
         */
        $anysdk->setPaymentSignCheckMode(\Sdk_AnySDK::PAYMENT_SIGN_CHECK_MODE_BOTH);
        $check_sign = $anysdk->checkPaymentSign($payment_params);
    
        Log::record('[check_sign] ' . var_export($check_sign, true), 'Notify_any');
    
        if (!$check_sign) {
            return \Sdk_AnySDK::PAYMENT_RESPONSE_FAIL . 'sign_error';
        }
    
        /**
         * 检查订单状态，1为成功
         */
        if (intval($anysdk->getPaymentStatus()) !== \Sdk_AnySDK::PAYMENT_STATUS_SUCCESS) {
            Log::record('[getPaymentStatus] ' . var_export(intval($anysdk->getPaymentStatus()), true), 'Notify_any');
            return \Sdk_AnySDK::PAYMENT_RESPONSE_OK;
        }
    
        /**
         * 获取支付通知详细参数
         * $amount = $anysdk->getPaymentAmount();
         * $product_name = $anysdk->getPaymentProductName();
         * $product_count = $anysdk->getPaymentProductCount();
         * $channel_product_id = $anysdk->getPaymentChannelProductId();
         * $order_id = $anysdk->getPaymentOrderId();
         * $channel_order_id = $anysdk->getPaymentChannelOrderId();
         * $private_data = $anysdk->getPaymentPrivateData();
         */
       
        $game_user_id = $anysdk->getPaymentGameUserId();
        $product_id = $anysdk->getPaymentProductId();
        $user_id = $anysdk->getPaymentUserId();
        $channel_number = $anysdk->getPaymentChannelNumber();
        $pay_time = $anysdk->getPaymentTime();
    
        //获取商品信息
        $id = $product_id;
        $goods = config('pay.goods');
        //订单入库
        $user = \app\api\model\User::findMap(['user_id' => $game_user_id]);
        if (empty($user)) {
            return ' error用户不存在';
        }
    
        $info['order_sn'] = guid();
        $info['goods_id'] = $product_id;
        $info['user_id'] = $user->user_id;
        $info['username'] = $user->username;
        $info['pay_type'] = 100;
        $info['pay_status'] = 1;  //订单状态1未付款2已付款3已到账
        $info['create_ip'] = request()->ip();
        $info['time_start'] = date('Y-m-d H:i:s', time());
        $data = array_merge($goods[$id], $info);
    
        $res = Db::name("order")->insert($data);
//            入库完成，回调
        if ($res) {
            $order = Db::name('order')->where('order_sn', $data['order_sn'])->find();
            $param = [];
            $param['pay_status'] = 2;
            $param['transaction_id'] = $channel_number;
            $param['receipt_amount'] = $goods[$id]['pay_total'];
            $param['buyer_id'] = isset($user_id) ? $user_id : '';
            $param['time_end'] = isset($pay_time) ? $pay_time : date('Y-m-d H:i:s', time());
        
            $result = $this->upOrder($param, $order['order_sn'], $order);  // 更新
            if ($result !== true) {
              return "error 订单发放失败";
            }
        }
        $response = $anysdk->getDebugInfo() . "\n=====我是分割线=====\n";
        Log::record('[debug_info] ' . var_export($response, true), 'Notify_any');
        return  \Sdk_AnySDK::PAYMENT_RESPONSE_OK;
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