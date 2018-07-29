<?php

namespace app\api\controller;

use app\api\model\Equipment;
use app\api\model\Tactical;
use app\api\model\UserAttribute;
use app\api\model\UserEquipment;
use app\api\model\UserResource;
use app\base\controller\ContentTpl;
use think\Loader;

class Base extends \app\base\controller\Base
{
    protected $post;
    protected $user;
    protected $user_id;
    protected $device;

    public function __construct()
    {
        parent::__construct();
        //解析请求数据

        if ($this->request->isPost()) {
            header("Access-Control-Allow-Origin: *"); // 允许任意域名发起的跨域请求
            header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
            $post = $this->getRequestPost($this->request->post('data'));
            $this->device = $post['header']['imei'];

            $this->user = Loader::model('user')->findMap(['device' => $post['header']['imei']]);
            $this->user_id = $this->user ? $this->user->user_id : null;
            $this->post = $post['element'];
        } else {
            //get请求抛出错误
//            throw new Exception('请求方式不正确');
        }
    }

    /**
     * 现实时间与游戏时间转换
     * @param int $time
     * @return int
     */
    public static function getTime($time, $create_time): int
    {
        $t = floor(($time - strtotime($create_time)) / 86400);
        return empty((int)$t * 100) ? 1 : (int)$t * 100;
    }

    /**
     * 获取日志模板
     * @param int $user_id
     * @param bool $status 战斗属性
     * @return int
     */
    public static function getUserLogContent($tpl, $key, $content = '')
    {

        $str = str_replace('XXXX', $content, ContentTpl::$$tpl[$key]);
        return $str;
    }

    /**
     * 数值 转换
     * @param int $user_id
     * @param bool $status 战斗属性
     * @return int
     */
    public static function typeConvert($num)
    {
        if ($num / 10000 >= 1) {
            $num = floor($num / 10000) . 'W';
        }
        return $num;
    }

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
                'is_get' => 0
            ];
        }
        if (!Loader::model('Email')->saveAll($data)) return false;
        return true;
    }

    /**
     * 获取玩家属性
     * @param int $user_id
     * @param bool $status 战斗属性
     * @return int
     */
    public static function getUserAttribute($user_id, $status = false)
    {
        //玩家基本属性
        $user_attribute = UserAttribute::findMap(['user_id' => $user_id], 'vita, attack, defense, dodge, speed, critical_strike, resistance, hit, tactical, skill_id')->toArray();
        //玩家装备
        $user_equipment = UserEquipment::getListByMap(['user_id' => $user_id], 'equipment_id');
        if (!empty($user_equipment)) {
            foreach ($user_equipment as $key => $value) {
                $equipment = Equipment::findMap(['equipment_id' => $value['equipment_id']], 'type, value')->toArray();
                $add_attribute[static::getType('equipment')[$equipment['type']]] = $equipment['value'];
            }
        }
        //玩家阵法
        if (!empty($user_attribute['tactical'])) {
            $tactical = Tactical::findMap(['tactical_id' => $user_attribute['tactical']], 'type, value')->toArray();
            $type = static::getType('tactical')[$tactical['type']];
            $user_attribute[$type] = ceil($user_attribute[$type] + $user_attribute[$type] * $tactical['value']);
            unset($user_attribute['tactical']);
        }
        // 玩家装备增幅属性  +  玩家基础属性
        foreach ($user_attribute as $key => $value) {
            if (!empty($add_attribute[$key])) {
                $user_attribute[$key] = $user_attribute[$key] + $add_attribute[$key];
            }
        }
        //战斗参数调整
        if ($status) {
            //神通
            if (!empty($user_attribute['skill_id'])) {
                $data = Loader::model('Skill')->getUserSkill($user_attribute['skill_id']);
                foreach ($data as $value) {
                    $k = 'skill_' . $value['type'];
                    $user_attribute[$k] = $value['value'];
                }
            } else {
                $user_attribute['skill_1'] = 0;
                $user_attribute['skill_2'] = 0;
            }
            $user_attribute['vita_full'] = $user_attribute['vita'];
            //月卡
            $user_resource = UserResource::findMap(['user_id' => $user_id], ' month_num, vip')->toArray();

            $month_num = empty($user_resource['month_num']) ? 0 : self::getMonthNum($user_resource['month_num']);
            $user_attribute['month_num'] = $month_num;
            //终身卡
            $vip = empty($user_resource['vip']) ? 0 : 1;
            $user_attribute['vip'] = $vip;

        }
        unset($user_attribute['skill_id']);

        return $user_attribute;
    }

    /**
     * 获取数据库对应类型
     * @param string $type
     * @return array
     */

    public static function getType($type)
    {
        return \app\base\controller\Type::$$type;
    }

    /**
     * 获取玩家月卡剩余天数
     * @param int $time
     * @return int
     */
    public static function getMonthNum($time): int
    {
        $t = 30 - floor((time() - $time) / 86400);

        return $t > 0 ? $t : 0;
    }

}