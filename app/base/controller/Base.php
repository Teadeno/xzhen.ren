<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/19
 * Time: 18:58
 */

namespace app\base\controller;


use think\Controller;
use think\Log;
abstract class Base extends Controller
{

    /**
     * 解析请求数据
     * @param string $data
     * @return array
     */
    public static function getRequestPost($data)
    {
        return json_decode($data, true);

    }

    /**
     * 获取返回码数组  不带数据
     * @param string $code
     * @param string $msg
     * @return array
     */
    public function showReturnWithCode($errnoe = '', $errmsg = '')
    {
        return $this->showReturnCode($errnoe, [], $errmsg);
    }

    /**
     * 获取返回码数组
     * @param string $errnoe
     * @param array $data
     * @param string $errmsg
     * @return string json
     */
    public function showReturnCode($errnoe = '', $data = [], $errmsg = '')
    {
        $return_data = [
            'message' => [
                'version' => '1.0',
                'body' => [
                    'errno' => 500,
                    'errmsg' => '未定义消息',
                    'data' => $errnoe === 0 ? $data : [],
                ]
            ]
        ];
        if (empty($errnoe) && $errnoe !== 0) return json_encode($return_data);
        $return_data['message']['body']['errno'] = $errnoe;
        if (!empty($errmsg)) {
            $return_data['message']['body']['errmsg'] = $errmsg;
        } else if (isset(ReturnCode::$return_code[$errnoe])) {
            $return_data['message']['body']['errmsg'] = ReturnCode::$return_code[$errnoe];
        }
        Log::record('[ PARAM ] ' . var_export($return_data, true), 'return');
        return json_encode($return_data);
    }

    /**
     * 获取错误提示信息
     * @param string $msg
     * @return array
     */
    public function showReturn($errmsg = '参数错误')
    {
        return $this->showReturnCode(0, ['status' => 100], $errmsg);
    }

}