<?php

namespace app\api\controller;

use app\api\model\User;
use think\Db;
use Sdk_AnySDK;
use think\Log;
class Login extends \app\base\controller\Base
{
    private $post; //post解析后数据
    private $header;

    public function __construct()
    {
        parent::__construct();
        //解析请求数据

        if ($this->request->isPost()) {
            header("Access-Control-Allow-Origin: *"); // 允许任意域名发起的跨域请求
            header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
            $post = $this->getRequestPost($this->request->post('data'));
            $this->post = $post['element'];
            $this->header = $post['element'];

        } else {
            //get请求抛出错误
//            throw new Exception('请求方式不正确');
        }
    }

    /**
     * 账户注册
     * @param $user //账号
     * @param  $pass //密码
     * @return string $user_id  //用户id
     */
    public function accountRegister()
    {

        if (!isset($this->post['user']) || !isset($this->post['pass'])) {
            return $this->showReturnWithCode(1001);
        }
        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $this->post['user']) === 1) return $this->showReturn('账号不合法');
        if (User::findMap(['user' => $this->post['user']])) return $this->showReturn('账号已存在');
        $data = [
            'user' => $this->post['user'],
            'pass' => $this->post['pass'],
        ];
        $user = User::create($data);

        return $this->showReturnCode(0, ['status' => 1, 'user_id' => $user->user_id]);

    }

    /**
     * 账户登录
     * @param $user //账号
     * @param  $pass //密码
     * @return string $user_id  //用户id
     */
    public function accountLogin()
    {

        if (!isset($this->post['user']) || !isset($this->post['pass'])) {
            return $this->showReturnWithCode(1001);
        }
        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $this->post['user']) === 1) return $this->showReturn('账号不存在');
        if (!$user = User::findMap(['user' => $this->post['user']])) return $this->showReturn('账号不存在');
        if ($user->pass != $this->post['pass']) return $this->showReturn('密码不正确');

        if (empty($user->device)) {
            //没有设备码  注册未完成
            return $this->showReturnCode(0, ['status' => 0, 'user_id' => $user->user_id]);
        } else {
            //存在设备码
            return $this->showReturnCode(0, ['status' => 1, 'device' => $user->device]);
        }
    }

    /**
     * 微信登录  获取open_id
     * @param $code //code
     * @return_success int status:1   strting open_id:$open_id  //未注册
     * @return_success int status:2   strting device:$device  //已注册
     * @return_error int status:0  //异常
     */
    public function getOpenId()
    {

        if (!isset($this->post['code'])) {
            return $this->showReturnWithCode(1001);
        }
        $config = config('pay.wxpay');
        $appid = $config['appId'];
        $appsecret = $config['secret'];


        $code = $this->post['code'];
        @file_put_contents('weixincode.log', $code . '\\r\\n', FILE_APPEND);
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code";

        $res = get($url);//通过code获取openid
        $userinfo = json_decode($res, true);
        if (!isset($userinfo['openid']) || empty($userinfo['openid'])) return $this->showReturnCode(0, ['status' => $res]);
        $openid = $userinfo['openid'];
        //获$userinfo['openid']
        //保存用户usionid信息
        $access_token = $userinfo['access_token'];
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid";
        $res = get($url);//获取用户个人信息（UnionID机制）
        $unionID = json_decode($res, true);
        if (!Db::name('wx_userinfo')->where('openid', $unionID['openid'])->find()) {
            $data = [
                'openid' => $unionID['openid'],
                'nickname' => $unionID['nickname'],
                'sex' => $unionID['sex'],
                'province' => $unionID['province'],
                'city' => $unionID['city'],
                'country' => $unionID['country'],
                'headimgurl' => $unionID['headimgurl'],
                'privilege' => $unionID['privilege'],
                'unionid' => $unionID['unionid'],
            ];
        
            Db::name('wx_userinfo')->insert($data);
        }
       
        
        
        //判断用户是否注册
        $user = User::findMap(['open_id' => $userinfo['openid']]);
        if (empty($user)) {
            $list = [
                'status' => 1,   //未注册
                'open_id' => $openid,
            ];
        } else {
            $list = [
                'status' => 2,   //已注册
                'device' => $user->device
            ];
        }
        return $this->showReturnCode(0, $list);

    }

    /**
     * 用户注册
     */
    public function userRegister()
    {
        if (!isset($this->post['user_id']) && !isset($this->post['open_id'])) {
            return $this->showReturnWithCode(1001);
        }
        //必须参数
        if (!isset($this->post['username']) || !isset($this->post['sex'])) {
            return $this->showReturnWithCode(1001);
        }
        if (Db::name('demand_sensitive_word')->where('badword', $this->post['username'])->find())
            return $this->showReturn('昵称不合法');

        /**
         *   设备绑定微信   判断是否发送device  保留
         *      if (isset($post['device']) || !empty($post['device'])) {
         * //绑定微信
         * //验证合法性
         * if ($user = \app\api\model\User::findMap(['device' => $post['device']])) {
         * $user->open_id = $post['open_id'];
         * if (!$user->save()) return $this->showReturn('网络错误');
         * } else {
         * return $this->showReturn('device未注册');
         * }
         * return $this->showReturnCode(0, ['status' => 1, 'device' => $post['device']]);
         * }*/

        //可选注册1、账号密码注册2、微信注册
        //验证合法性
        $M = new User();
        if ($M->findMap(['username' => $this->post['username']])) return $this->showReturn('昵称已被占用');
        do {
            $this->post['device'] = rand(1000000, 9999999);
        } while ($M->findMap(['device' => $this->post['device']]));

        //增加
        if (!$info = $M->addUserInfo($this->post)) return $this->showReturn('网络错误');

        return $this->showReturnCode(0, ['status' => 1, 'device' => $this->post['device']]);
    }

    /**
     * 判断用户是否存在
     */
    public function isUser(\app\api\model\User $user)
    {
        //根据前端传来的唯一标识，查询是否已经注册
        $map = [
            'device' => $this->device
        ];
        $list['status'] = $user::findMap($map) ? 1 : 0;

        return $this->showReturnCode(0, $list);
    }
    
    /**
     * 百度登陆验证  暂未启用
     */
    public function bdLogin()
    {
        
        header("Content-type: application/json; charset=utf-8");
        defined('LOGIN_CHECK_URL')        or define('LOGIN_CHECK_URL',          'http://oauth.anysdk.com/api/User/LoginOauth/');
        defined('ADTRACKING_REPORT_URL')  or define('ADTRACKING_REPORT_URL',    'http://pay.anysdk.com/v5/AdTracking/Submit/');
        defined('DEBUG_MODE')             or define('DEBUG_MODE',               FALSE);

        // 游戏ID              前往dev.anysdk.com => 游戏列表 获取
        defined('ANYSDK_GAME_ID')         or define('ANYSDK_GAME_ID', 639797331);
        // 增强密钥             前往dev.anysdk.com => 游戏列表 获取，此参数请严格保密
        defined('ANYSDK_ENHANCED_KEY')    or define('ANYSDK_ENHANCED_KEY','ODNjNmY3ZWEyMWY1MWY3ZGZhNTA');
        // private_key        前往dev.anysdk.com => 游戏列表 获取
        defined('ANYSDK_PRIVATE_KEY')     or define('ANYSDK_PRIVATE_KEY','58D00BD80CF7AB095318C357D140300A');
        $login_params = $_REQUEST;
        $anysdk = new \Sdk_AnySDK();
    
        $response = $anysdk->loginForward($login_params);
   
//        Log::record('[ PARAM0 ] ' . json_encode($response), 'login_any');
        if ($anysdk->getLoginStatus()) {
         
            // 获取登录结果的一些字段
            $channel = $anysdk->getLoginChannel();
            $uid = $anysdk->getLoginUid();
            $user_sdk = $anysdk->getLoginUserSdk();
            $plugin_id = $anysdk->getLoginPluginId();
            $server_id = $anysdk->getLoginServerId();
            $userinfo = $anysdk->getLoginData();   // 获取登录验证渠道返回的原始内容
            $arr = [
                'channel'=>$channel,
                'uid' =>$uid,
                'user_sdk' =>$user_sdk,
                'plugin_id' =>$plugin_id,
                'server_id' =>$server_id,
                'data' =>$userinfo,
            ];
     
//            Log::record('[ PARAM1 ] ' . var_export($arr, true), 'return');
            // 获取登录结果字段值示例结束
        }
      
        $resp_arr = json_decode($response, TRUE);
        $user = User::findMap(['open_id' => $userinfo['openid']]);
        if (empty($user)) {
            $list = [
                'status' => 1,   //未注册
                'open_id' => $userinfo['openid'],
            ];
        } else {
            $list = [
                'status' => 2,   //已注册
                'device' => $user->device
            ];
        }
        
        $resp_arr['ext'] = $this->getRequestPost($this->showReturnCode(0, $list));
        $response = json_encode($resp_arr);
 
//        Log::record('[ PARAM2 ] ' . var_export($response, true), 'return');
        return is_scalar($response)? $response: json_encode($response);
    }

}