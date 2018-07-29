<?php

namespace app\api\controller;

use app\api\model\UserAttribute;
use app\api\model\UserKnapsack;
use app\api\model\UserLog;
use app\api\model\UserResource;
use think\Hook;

class Realm extends Base
{
    /**
     *获取玩家渡劫成功率和渡劫丹
     * */
    public function getRatio()
    {
        $map = ['user_id' => $this->user_id];
        //渡劫成功率
        $ratio = UserAttribute::findMap($map, 'ratio')->toArray()['ratio'];
        //玩家当前境界
        $realm_id = UserResource::findMap($map, 'realm_id')->toArray()['realm_id'];
        $info = \app\api\model\Realm::findMap(['realm_id' => $realm_id], 'grade, f_id');

        $realm_level = $info->grade;
        if ($realm_level != 0) {
            //渡劫丹个数
            $elixir = \app\api\model\Elixir::findMap(['type' => 10, 'level' => $realm_level], 'elixir_id,img_url,name');
            $goods_id = $elixir->elixir_id;
            $UserKnapsack = UserKnapsack::findMap(['user_id' => $this->user_id, 'type' => 1, 'goods_id' => $goods_id]);
            $name = $elixir->name;
            $img_url = $elixir->img_url;
        } else {
            $name = '无';
            $img_url = '';
        }

        $num = empty($UserKnapsack) ? 0 : $UserKnapsack->num;

        $list['ratio'] = $ratio;
        $list['price'] = \app\api\model\Realm::findMap(['realm_id' => $info->f_id], 'price')->price;
        $list['elixir'] = [
            'knapsack_id' => empty($UserKnapsack) ? 0 : $UserKnapsack->knapsack_id,
            'num' => $num,
            'name' => $name,
            'img_url' => $img_url,
        ];
        return $this->showReturnCode(0, $list);
    }

    //晋级
    public function promotion()
    {
        //1、判断用户修为是否满足晋级要求
        //2、判断用户阶级是否为10 10表示该境界满级不能晋级只能飞升
        //3、成功调用  成功处理函 $this->yes($user_id, $f_id);
        //重新获取
        $map = ['user_id' => $this->user_id];
        Hook::listen('sync_quality', $map);  //同步修为值
        $user_resource = UserResource::findMap($map, 'quality, realm_id');

        $M = new \app\api\model\Realm();
        $realm = $M::findMap(['realm_id' => $user_resource->realm_id]);
        if ($realm->grade == 10 && $realm->steps == 10) {
            return $this->showReturn('已达巅峰');
        }
        $f_realm = $M::findMap(['realm_id' => $realm->f_id]);

        //验证合法性
        if ($user_resource->quality < $f_realm->price) return $this->showReturn('修为不足');

        //判断是否需要渡劫
        if ($realm->steps == 10) {  //渡劫
            //获取渡劫成功率
            $ratio = UserAttribute::findMap($map, 'ratio')->toArray()['ratio'];
            if ($ratio < rand(0, 100)) {  //失败
                $user_resource->quality = $user_resource->quality - $f_realm->price * $f_realm->punish;
                $user_resource->save();
                $user_log = [
                    'user_id' => $this->user_id,
                    'type' => 6,
                    'content' => static::getUserLogContent('realm', 2, $f_realm->name)
                ];
                if (!UserLog::create($user_log)) {
                    return false;
                }
                return $this->showReturnCode(0, ['status' => 0]);  //渡劫失败
            }
        }
        //渡劫成功
        if (!$M->editRealm($this->user_id, $realm->f_id, $f_realm)) return $this->showReturn('网络错误');


        return $this->showReturnCode(0, ['status' => 1]);
    }

    //飞升
    public function soaring()
    {
        //判断用户修为是否满足晋级要求
        /*  if ($info){
              return toJson(0, '修为不足，速去修炼');
          }
          //判断用户阶级是否为10 10表示该境界满级不能晋级只能飞升
          if (){
              return toJson(0,'境界未满,速去晋级') ;
          }

          //获取用户渡劫成功率， 随机判断是否成功
          if (){
              //成功调用  成功处理函数
          }else{

          }*/
        $data = [
            'status' => 1
        ];
        return $this->showReturnCode(0, $data);
    }

    public function yes($user_id, $f_id)
    {
        //获取下一个境界的信息
        //事物处理
        // 将user_resource表的realm_id改为下一个境界的id
        // quality值减少晋级所消耗的修为值  加上晋级前的修为值（晋级后修炼速度发生）
        //在user_attribute 表增加飞升境界后增加的属性
        //事物提交

        //attribute_log 表 记录属性变化情况
        //resource_log 表 记录修为值减少情况
        //user_log 表  记录 升级情况
    }


}