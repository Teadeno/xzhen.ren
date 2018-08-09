<?php


namespace app\api\controller;


use app\api\model\Activity;
use app\api\model\UserResource;
use app\api\model\WallMap;
use think\Db;
use think\Request;

class Pay extends Base
{
    /**
     * 支付前处理
     */
    public function beforePay()
    {

        $param = $this->post;
    
    
        $id = $param['goods_id'];
        $goods = config('pay.goods');

        $data = array_merge($goods[$id], ['pay_type' => $param['pay_type']]);

        $result = $this->pay($data);
        if ($result === false) {
            return $this->showReturn('发生未知错误');
        } else {
    
            return $this->showReturnCode(0, $result);
        }
    }

    /**
     * 支付
     */
    private function pay(array $param)
    {
        //根据前端发送的价格，类型，充值金额，增加数值，描述，生成订单  入库order  支付类型
        //根据支付类型生成对应的预订单
        //将生成的预订单信息返回给前端
        if (!isset($param['name']) || !$param['name']) {
            return false;
        }
        if (!isset($param['pay_total']) || !$param['pay_total']) {
            return false;
        }
        if (!isset($param['type']) || !$param['type']) {
            return false;
        }
        if (!isset($param['describe']) || !$param['describe']) {
            return false;
        }
        if (!isset($param['pay_type']) || !$param['pay_type']) {
            return false;
        }

        $user = \app\api\model\User::findMap(['user_id' => $this->user_id]);
        if (empty($user)) {
            return false;
        }

        $data = [];
        $data['order_sn'] = guid();
        $data['user_id'] = $this->user_id;
        $data['name'] = $param['name'];
        $data['username'] = $user->username;
        $data['describe'] = $param['describe'];
        $data['num'] = isset($param['num']) ? $param['num'] : 1;
        $data['pay_type'] = $param['pay_type'];
        $data['type'] = $param['type'];
        $data['pay_total'] = $param['pay_total'];
        $data['pay_status'] = 1;  //订单状态1未付款2已付款3已到账
        $data['create_ip'] = request()->ip();
        $data['time_start'] = date('Y-m-d H:i:s', time());
        $res = Db::name("order")->insert($data);

        if (!$res) {
            return false;
        }

        if ($data['pay_type'] == 1) {  //支付宝
            $conf = config('pay.alipay');
            vendor('alipay.aop.AopClient');
            vendor('alipay.aop.request.AlipayTradeAppPayRequest');
            $aop = new \AopClient();
            $aop->appId = $conf['appId'];
            $aop->signType = $conf['signType'];
            $aop->rsaPrivateKey = $conf['rsaPrivateKey'];
            $aop->alipayrsaPublicKey = $conf['alipayrsaPublicKey'];
            //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
            $request = new \AlipayTradeAppPayRequest();
            // 异步通知地址
//        $notify_url = urlencode($conf['notify_url']);
            $notify_url = $conf['notify_url'];
            // 订单标题
            $subject = $data['name'];
            // 订单详情
            $body = $param['describe'];
            // 订单号，示例代码使用时间值作为唯一的订单ID号
            $out_trade_no = $data['order_sn'];
            //订单价格
            $total = $data['pay_total'];
            //SDK已经封装掉了公共参数，这里只需要传入业务参数
            $bizcontent = "{\"body\":\"" . $body . "\","
                . "\"subject\": \"" . $subject . "\","
                . "\"out_trade_no\": \"" . $out_trade_no . "\","
                . "\"timeout_express\": \"30m\","
                . "\"total_amount\": \"" . $total . "\","
                . "\"product_code\":\"QUICK_MSECURITY_PAY\""
                . "}";
            $request->setNotifyUrl($notify_url);
            $request->setBizContent($bizcontent);
            //这里和普通的接口调用不同，使用的是sdkExecute
            $response = $aop->sdkExecute($request);
//        $responseArray = [];
//         parse_str($response,$responseArray);
            // 注意：这里不需要使用htmlspecialchars进行转义，直接返回即可
            return $response;
        } elseif ($data['pay_type'] == 2) { //微信
            vendor('wxpay.WxPayApi');

            $conf = config('pay.wxpay');
            // 商品名称
            $subject = $data['name'];
            // 订单号，示例代码使用时间值作为唯一的订单ID号
            $total = $data['pay_total'];
            $out_trade_no = $data['order_sn'];
            $mchid = $conf['mchid']; // 商户号
            $appid = $conf['appId'];
            $unifiedOrder = new \WxPayUnifiedOrder();
            $unifiedOrder->SetAppid($appid);
            $unifiedOrder->SetMch_id($mchid);//商户号
            $unifiedOrder->SetBody($subject);//商品或支付单简要描述

            $unifiedOrder->SetOut_trade_no($out_trade_no);
            $unifiedOrder->SetTotal_fee($total);
            $unifiedOrder->SetNotify_url($conf['notify_url']);
            $unifiedOrder->SetTrade_type("APP");
            $result = \WxPayApi::unifiedOrder($unifiedOrder);
            $result['timestamp'] = time();
//            $str = 'appid='.$result['appid'].'&noncestr='.$result['nonce_str'].'&package=Sign=WXPay&partnerid='.$mchid.'&prepayid='.$result['prepay_id'].'&timestamp='.$result['timestamp'];
////重新生成签名
//            $result['sign'] = strtoupper(md5($str.'&key='.\WxPayConfig::KEY));
    
            $value = [
                'appid' => $result['appid'],
                'noncestr' => $result['nonce_str'],
                'partnerid' => $result['mch_id'],
                'prepayid' => $result['prepay_id'],
                'package' => "Sign=WXPay",
                'timestamp' => $result['timestamp'],
            ];
            $result['sign'] = $this->MakeSign($value);
    
            if (is_array($result)) {
                return $result;
            } else {
                return false;
            }
        } else if ($data['pay_type'] == 3) {
            vendor('wxpay.WxPayApi');
    
            $conf = config('pay.wxpay');
            // 商品名称
            $subject = $data['name'];
            // 订单号，示例代码使用时间值作为唯一的订单ID号
            $total = $data['pay_total'];
            $out_trade_no = $data['order_sn'];
            $mchid = $conf['mchid']; // 商户号
            $appid = $conf['appId'];
            $key = $conf['key'];
//            $scene_info ='{"h5_info": {"type":"Android","app_name": "修真破苍穹","package_name": "com.yesgame.xzhen"}}';
            $unifiedOrder = new \WxPayUnifiedOrder();
            $unifiedOrder->SetAppid($appid);
            $unifiedOrder->SetMch_id($mchid);//商户号
            $unifiedOrder->SetBody($subject);//商品或支付单简要描述
    
            $unifiedOrder->SetOut_trade_no($out_trade_no);
//            $unifiedOrder->SetProduct_id($scene_info);
    
    
            $unifiedOrder->SetTotal_fee($total);
            $unifiedOrder->SetNotify_url($conf['notify_url']);
            $unifiedOrder->SetTrade_type("MWEB");
            $result = \WxPayApi::unifiedOrder($unifiedOrder);
    
            $result['timestamp'] = 1533790140;
    
            if (is_array($result)) {
                return $result;
            } else {
                return false;
            }
        }
    }
    
    public function MakeSign($value)
    {
        //签名步骤一：按字典序排序参数
        ksort($value);
        $string = $this->ToUrlParams($value);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . \WxPayConfig::KEY;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    
    /**
     * 格式化参数格式化成url参数
     */
    public function ToUrlParams($values)
    {
        $buff = "";
        foreach ($values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        
        $buff = trim($buff, "&");
        return $buff;
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

    public function ios_pay()
    {
        $post = $this->post;
        if (!isset($post['status']) || !isset($post['goods_id'])) {
            return $this->showReturnWithCode(1001);
        }
        //凭证正确
        if ($post['status'] == 1) {
            //获取商品信息
            $id = $post['goods_id'];
            $goods = config('pay.goods');
            //订单入库
            $user = \app\api\model\User::findMap(['user_id' => $this->user_id]);
            if (empty($user)) {
                return $this->showReturn('用户不存在');
            }

            $info['order_sn'] = guid();
            $info['user_id'] = $this->user_id;
            $info['username'] = $user->username;
            $info['pay_type'] = 3;
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
                $param['transaction_id'] = 'Ios';
                $param['receipt_amount'] = $goods[$id]['pay_total'];
                $param['buyer_id'] = isset($post['buyer_id']) ? $post['buyer_id'] : '';
                $param['time_end'] = isset($post['gmt_payment']) ? $post['gmt_payment'] : date('Y-m-d H:i:s', time());
                $result = $this->upOrder($param, $order['order_sn'], $order);  // 更新
                if ($result == true) {
                    return $this->showReturnCode(0, ['status' => 1]);
                } else {
                    return $this->showReturnCode(0, ['status' => 0]);
                }
            }
        }
    }

}