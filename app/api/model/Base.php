<?php

namespace app\api\model;

use think\Exception;
use think\Hook;
use think\Loader;

class Base extends \app\base\model\Base
{
    /**
     * 现实时间与游戏时间转换
     * @param int $time
     * @return int
     */
    public static function getTime($time, $create_time)
    {
        return \app\api\controller\Base::getTime($time, $create_time);
    }

    public function _initialize()
    {

    }

    /**
     * 根据award_id获取对应的奖励信息
     * @param string $award_id
     * @param bool $status //是否显示简洁数据
     * @return array
     */
    public function getAwardList($award_id, $status = false)
    {

        $map['award_id'] = $award_id;
        $field = ['create_time, update_time', true];
        $data = Loader::model('award')->getListByMap($map, $field);

        $list = [];
        foreach ($data as $key => $value) {
            //是否奖励实物
            if ($value['is_goods'] == 1) {
                //是否开启随机奖励
                if ($value['is_ratio'] == 1) {
                    //随机是否收到奖励
                    if ($value['ratio'] * 1000 < rand(1, 1000)) {
                        continue;
                    }
                }
                //获取实物信息
                $award_id_ids = explode(',', $value['award_goods_id']);
                $map = null;
                //随机出现物品
                $award_goods_id = $award_id_ids[rand(0, count($award_id_ids) - 1)];
                $map['award_goods_id'] = $award_goods_id;
                $v = Loader::model('award_goods')->findMap($map);
                $info = Loader::model($this->getType('award_goods')[$v['type']])->findId($v['id'])->toArray();
                if ($status) {
                    $info['award_type'] = $value['type'];
                    $info['award_value'] = $v['value'];
                    $info['award_goods_type'] = $v['type'];
                    $list[] = $info;
                } else {
                    $list[] = [
                        'url' => $info['img_url'],
                        'name' => $info['name'],
                        'value' => $v['value']
                    ];
                }

            } else {
                //返回的数据格式
                $map = null;
                $map['type'] = array_search($this->getType('award')[$value['type']], $this->getType('resource'));
                $info = Loader::model('img_url')->findMap($map)->toArray();
                if ($status) {
                    $info['award_type'] = $value['type'];
                    $info['award_value'] = $value['value'];
                    $list[] = $info;
                } else {
                    $list[] = [
                        'url' => $info['img_url'],
                        'name' => $info['name'],
                        'value' => $value['value']
                    ];
                }
            }

        }
        return $list;
    }

    /**
     * 获取数据库对应类型
     * @ param string $type
     */
    public function getType($type)
    {
        return \app\base\controller\Type::$$type;
    }

    /**
     * 给用户增加奖励
     * @ param array $award 奖励列表
     * @ param int $user_id 奖励用户
     * @ param array  $auto  自动出售装备
     * @ return bool
     */
    public function getAward($award, $user_id, $auto = false)
    {

        //获取用户资源
        $user_resource = Loader::model('user_resource')->findMap(['user_id' => $user_id]);
        //获取相关类型
        $award_type = $this->getType('award');
        $award__goods_type = $this->getType('award_goods');
        $knapsack_insert = [];
        $knapsack_update = [];
        try {
            $this->startTrans();
            reset($award);
            while ($value = current($award)) {
                switch ($value['award_type']) {
                    case 100:  //实物道具
                        //判断背包是否已满
                        if (Loader::model('user_knapsack')->where('user_id', $user_id)->count() >= $user_resource['knapsack_num']) {
                            return false;
                        }
                        //判断是否是装备
                        if ($value['award_goods_type'] == 2) {
                            //判断是否自动出售
                            if ($auto) {
                                if (array_key_exists($value['level'], $auto)) {
                                    $info = Loader::model('img_url')->findMap(['type' => 1])->toArray();
                                    $info['award_type'] = 1;
                                    $info['award_value'] = $value['sell'];
                                    $award[] = $info;
                                    break;
                                }
                            }
                        }
                        $data = [
                            'user_id' => $user_id,
                            'name' => $value['name'],
                            'type' => array_search($award__goods_type[$value['award_goods_type']], $this->getType('knapsack_type')),
                            'goods_id' => $value[$award__goods_type[$value['award_goods_type']] . '_id'],
                            'num' => $value['award_value'],
                            'sell' => $value['sell'],
                            'img_url' => $value['img_url'],
                            'describe' => $value['describe']
                        ];
                        if (!self::addKnapsack($data, $knapsack_insert, $knapsack_update)) return false;
                        break;
                    default:   // 资源道具
                        //资源领取存入用户资源
                        $type = $award_type[$value['award_type']];
                        if (isset($resource_update[$type])) {
                            $resource_update[$type] = $resource_update[$type] + $value['award_value'];
                        } else {
                            $resource_update[$type] = $user_resource[$type] + $value['award_value'];
                        }
                        //资源变化记录
                        $params[] = [
                            'user_id' => $user_id,
                            'type' => array_search($type, $this->getType('resource_log')),
                            'value' => $value['award_value'],
                            'describe' => $this->comment,
                        ];
                        break;
                }
                array_shift($award);
                reset($award);
            }
            if (isset($resource_update)) {
                if (!UserResource::editMapData(['user_id' => $user_id], $resource_update)) {
                    return false;
                }
//                Hook::listen('resource_log', $params);
            }
            if (!empty($knapsack_insert)) {
                if (Loader::model('user_knapsack')->insertAll($knapsack_insert) == 0) {
                    return false;
                }
            }
            if (!empty($knapsack_update)) {
                if (Loader::model('user_knapsack')->isUpdate()->saveAll($knapsack_update) == 0) {
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (Exception $exception) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 玩家背包增加物品
     * @param array $data 包含字段  user_id，name，type，goods_id，num，img_url，describe
     * @param bool $insert 是否新增  默认新增
     * @param bool $update 是否更新  默认更新
     * @return array
     */
    public function addKnapsack($data, &$insert = false, &$update = false)
    {

        if ($data['type'] == 2) {
            //装备不可叠加
            while ($data['num'] > 0) {
                $knapsack_insert[] = [
                    'user_id' => $data['user_id'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'goods_id' => $data['goods_id'],
                    'num' => 1,
                    'sell' => isset($data['sell']) ? $data['sell'] : 1,
                    'img_url' => $data['img_url'],
                    'describe' => $data['describe'],
                ];
                $data['num']--;
            }

        } else {
            $map = [
                'user_id' => $data['user_id'],
                'type' => $data['type'],
                'goods_id' => $data['goods_id']
            ];
            $result = Loader::model('user_knapsack')->findMap($map);
            if ($result) {
                $knapsack_update = [
                    'knapsack_id' => $result['knapsack_id'],
                    'num' => $data['num'] + $result['num']
                ];
            } else {
                if ($data['type'] == 3) {
                    $data['name'] = explode('》', $data['name'])[0] . '》';
                }
                $knapsack_insert[] = [
                    'user_id' => $data['user_id'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'goods_id' => $data['goods_id'],
                    'num' => $data['num'],
                    'sell' => isset($data['sell']) ? $data['sell'] : 1,
                    'img_url' => $data['img_url'],
                    'describe' => $data['describe'],
                ];
            }
        }


        if (isset($knapsack_insert)) {
            if ($insert === false) {
                if (Loader::model('user_knapsack')->isUpdate(false)->allowField(true)->insertAll($knapsack_insert) == 0) {
                    return false;
                }
            } else {
                $insert = array_merge($insert, $knapsack_insert);
            }
    
        }
        if (isset($knapsack_update)) {
            if ($update === false) {
                if (Loader::model('user_knapsack')->isUpdate(true)->allowField(true)->save($knapsack_update, ['knapsack_id' => $result['knapsack_id'],]) == 0) {
                    return false;
                }
            } else {
                $update[] = $knapsack_update;
            }
           
        }
        return true;
    }

    /**
     * 创建邀请码
     * @param string $value
     * @return array
     */
    function createInvite($value)
    {
        static $source_string = 'E5FCDG3HQA4B1NOPIJ2RSTUV67MWX89KLYZ';
        $num = $value;
        $code = '';
        while ($num > 0) {
            $mod = $num % 35;
            $num = ($num - $mod) / 35;
            $code = $source_string[$mod] . $code;
        }
        if (empty($code[3]))
            $code = str_pad($code, 4, '0', STR_PAD_LEFT);
        return $code;
    }

    /**
     * 发送邮件
     * @ param array $award
     * @ return bool
     */
    public function sendEmail($user, $award_id, $title, $content)
    {
        return \app\api\controller\Base::sendEmail($user, $award_id, $title, $content);
    }

    /**
     * 玩家属性增加
     * @param int $user_id 玩家ID
     * @param int $type 增加类型
     * @param int $value 增加数值
     * @param int $table 来源  默认使用表名
     * @return bool true | false
     */
    public function addAttribute($user_id, $type, $value, $table = null)
    {
        $user_attribute = UserAttribute::findMap(['user_id' => $user_id])->toArray();

        //增加属性
        if ($type == 1) {
            $map = ['user_id' => $user_id];
            Hook::listen('sync_quality', $map);
        }
        $table = $table ?? $this->table;  //默认使用表名
        $type = $this->getType($table)[$type];
        $update[$type] = $user_attribute[$type] + $value;

        if (!UserAttribute::editMapData(['user_id' => $user_id], $update)) return false;
        return true;
    }

    /**
     * 玩家资源增加
     * @param int $user_id 玩家ID
     * @param int $type 增加类型
     * @param int $value 增加数值
     * @param int $table 来源  默认使用表名
     * @return bool true | false
     */
    public function addResource($user_id, $type, $value, $table = null)
    {
        $user_resource = UserResource::findMap(['user_id' => $user_id])->toArray();
        if ($type == 7) {
            //当天进入挂图 则增加
            $wall = WallMap::findMap(['user_id' => $user_id]);
            if (!empty($wall)) {
                $wall->count += $value;
                $wall->save();
            }
        }
        //增加资源
        $table = $table ?? $this->table;  //默认使用表名
        $type = $this->getType($table)[$type];
        $update[$type] = $user_resource[$type] + $value;

        if (!UserResource::editMapData(['user_id' => $user_id], $update)) return false;
        return true;
    }

    /**
     * 玩家王朝资源
     * @param int $user_id 玩家ID
     * @param int $type 增加类型
     * @param int $value 增加数值
     * @param int $table 来源  默认使用表名
     * @return bool true | false
     */
    public function addDynasty($user_id, $type, $value, $table = null)
    {
        $user_resource = UserDynasty::findMap(['user_id' => $user_id])->toArray();

        //增加资源
        $table = $table ?? $this->table;  //默认使用表名
        $type = $this->getType($table)[$type];
        $update[$type] = $user_resource[$type] + $value;

        if (!UserDynasty::editMapData(['user_id' => $user_id], $update)) return false;
        return true;
    }

    /**
     * 获取玩家属性
     * @param int $user_id
     * @param bool $status 战斗属性
     * @return int
     */
    public function getUserAttribute($user_id, $status = false)
    {
        return \app\api\controller\Base::getUserAttribute($user_id, $status);
    }

    /**
     * 获取日志模板
     * @param int $user_id
     * @param bool $status 战斗属性
     * @return int
     */
    public function getUserLogContent($tpl, $key = 0, $content = '')
    {

        return \app\api\controller\Base::getUserLogContent($tpl, $key, $content);
    }
}