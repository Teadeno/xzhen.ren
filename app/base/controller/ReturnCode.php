<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/19
 * Time: 18:50
 */

namespace app\base\controller;

class ReturnCode
{
    /**
     * @返回状态码
     */
    static public $return_code = [
        0 => '操作成功',
        100 => "",  //弹出提示
        101 => '王朝暂未开放',
        1001 => '参数错误'

    ];
}