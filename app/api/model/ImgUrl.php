<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/30
 * Time: 15:23
 */

namespace app\api\model;

use think\Request;

class ImgUrl extends Base
{
    protected $table = 'img_url';
    protected $pk = 'id';
    protected $comment = '图片位置表';

    public function getImgUrlAttr($value)
    {
        return Request::instance()->domain() . $value;
    }
}