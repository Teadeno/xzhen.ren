<?php

namespace app\index\controller;

use think\Controller;
use think\Hook;
use app\api\model\UserResource;
use app\api\model\UserAttribute;
use app\api\model\Realm;
use app\api\model\UserLog;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
}
