<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:17
 */

namespace app\api\model;

use think\Db;
use think\Loader;

//挂图表
class WallMap extends Base
{
    protected $table = 'wall_map';
    protected $pk = 'id';
    protected $comment = '挂图表';

    /**
     * equipment_level装备等级 数组<=>字符转换
     * @param $value
     * @param $data
     * @return string
     */
    public function getEquipmentLevelAttr($value)
    {
        $data = [];
        if (!empty($value)) {
            $info = explode(',', $value);
            foreach ($info as $value) {
                $data[$value] = $value;
            }
        }

        return $data;
    }

    /**
     * 设置自动出售装备等级
     * @param int $user_id 玩家ID
     * @param array $level 装备等级
     * @return bool true | false
     */
    public function setEquipmentLevel($user_id, $level)
    {
        if ($wall_info = $this->findMap(['user_id' => $user_id])) {
            $wall_info->equipment_level = $level;
            if (!$wall_info->save()) return false;
        } else {
            //获取玩家每天的挂图测试
            $wall_map_num = UserResource::findMap(['user_id' => $user_id], 'wall_map_num')->toArray()['wall_map_num'];
            $data = [
                'user_id' => $user_id,
                'num' => $wall_map_num,
                'equipment_level' => $level,
            ];

            if (!$this->save($data)) return false;
        }
        return true;
    }

    /**
     * 挂图奖励领取并记录
     * @param array $user_id 玩家ID
     * @param array $award 奖励
     * @return bool true | false
     */
    public function wallMapAward($user_id, $award, $level = false)
    {

        try {
            $this->startTrans();
            if (!$this->getAward($award, $user_id, $level)) return false;

            $info = $this::findMap(['user_id' => $user_id]);

            $info->get_num = $info->get_num + 1;

            if (!$info->save()) return false;
            //挂图奖励记录
            if (!$this->wallMapLog($award, $user_id, $level)) return false;
            $this->commit();
            return true;
        } catch (Exception $exception) {
            $this->rollback();
            return false;
        }


    }

    public function wallMapLog($award, $user_id, $status = false)
    {

        //获取相关类型
        $award_type = $this->getType('award');
        $award__goods_type = $this->getType('award_goods');
        try {
            $this->startTrans();
            reset($award);
            while ($value = current($award)) {
                switch ($value['award_type']) {
                    case 100:  //实物道具
                        //判断背包是否已满
                        //判断是否是装备
                        if ($value['award_goods_type'] == 2) {
                            //判断是否自动出售
                            if ($status) {
                                if (array_key_exists($value['level'], $status)) {
                                    $info = Loader::model('img_url')->findMap(['type' => 1])->toArray();
                                    $info['award_type'] = 1;
                                    $info['award_value'] = $value['sell'];
                                    $award[] = $info;
//                                    $wall_update['num'] = $wall_update['num'] + $value['sell'];
                                    break;
                                }

                            }
                            //装备不可叠加
                            $wall_insert[] = [
                                'user_id' => $user_id,
                                'name' => $value['name'],
                                'type' => array_search($award__goods_type[$value['award_goods_type']], $this->getType('wall_map_type')),
                                'goods_id' => $value[$award__goods_type[$value['award_goods_type']] . '_id'],
                                'num' => $value['award_value'],
                                'img_url' => $value['img_url']
                            ];
                        } else {
                            //其他物品可叠加  背包是否存在该物品如果存在直接增加数量，如果不存在新增数据
                            $map = null;
                            $map = [
                                'user_id' => $user_id,
                                'type' => array_search($award__goods_type[$value['award_goods_type']], $this->getType('wall_map_type')),
                                'goods_id' => $value[$award__goods_type[$value['award_goods_type']] . '_id']
                            ];
                            $result = Loader::model('wall_map_log')->findMap($map);
                            if ($result) {

                                $wall_update[] = [
                                    'id' => $result['id'],
                                    'num' => $result['num'] + $value['award_value']
                                ];
                            } else {
                                $wall_insert[] = [
                                    'user_id' => $user_id,
                                    'name' => $value['name'],
                                    'type' => array_search($award__goods_type[$value['award_goods_type']], $this->getType('knapsack_type')),
                                    'goods_id' => $value[$award__goods_type[$value['award_goods_type']] . '_id'],
                                    'num' => $value['award_value'],
                                    'img_url' => $value['img_url']
                                ];
                            }
                        }
                        break;
                    default:   // 资源道具
                        //资源领取存入用户资源
                        $map = null;
                        $map = [
                            'user_id' => $user_id,
                            'type' => 6,
                            'goods_id' => $value['award_type']
                        ];
                        $result = Loader::model('wall_map_log')->findMap($map);
                        if ($result) {
                            if (isset($wall_update[$result['id']])) {
                                $num = $value['award_value'] + $wall_update[$result['id']]['num'];
                            } else {
                                $num = $value['award_value'] + $result['num'];
                            }
                            $wall_update[$result['id']] = [
                                'id' => $result['id'],
                                'num' => $num
                            ];
                        } else {
                            $wall_insert[] = [
                                'user_id' => $user_id,
                                'name' => $value['name'],
                                'type' => 6,
                                'goods_id' => $value['award_type'],
                                'num' => $value['award_value'],
                                'img_url' => $value['img_url']
                            ];
                        }

                        break;
                }
                array_shift($award);
                reset($award);
            }

            if (isset($wall_insert)) {
                if (Loader::model('wall_map_log')->insertAll($wall_insert) == 0) {
                    return false;
                }
            }
            if (isset($wall_update)) {
                if (Loader::model('wall_map_log')->isUpdate()->saveAll($wall_update) == 0) {
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

    protected function setEquipmentLevelAttr($value, $data)
    {
        return implode(',', $value);
    }

}