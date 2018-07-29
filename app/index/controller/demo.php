<?php

namespace app\index\controller;

use think\Controller;

/**
 * @title 测试demo
 * @description 接口说明
 * @header name:key require:1 default: desc:秘钥(区别设置)
 */
class Demo extends Controller
{
    /**
     * @title 接口名称
     * @description 接口说明
     * @author 王琼凯
     * @url /index/demo
     * @method POST
     * @module 门派
     *
     * @header name:device require:1 default: desc:设备号
     *
     * @param name:id type:int require:1 default:1 other: desc:唯一ID
     *
     * @return_success errno:0
     * @return_success errmsg:操作成功
     * @return_success data:返回信息@!
     * @data status:1;
     */
    public function index()
    {
        //接口代码
        $device = $this->request->header('device');
        echo json_encode(["code" => 200, "message" => "success", "data" => ['device' => $device]]);
    }

    /**
     * @title 登录接口
     * @description 接口说明
     * @author 开发者
     * @url /api/demo
     * @method GET
     * @module 用户模块
     *
     * @return name:名称
     * @return mobile:手机号
     *
     */
    public function login(Request $request)
    {
        //接口代码
        $device = $request->header('device');
        echo json_encode(["code" => 200, "message" => "success", "data" => ['device' => $device]]);
    }
}
