<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:24
 */

namespace app\api\model;

//装备表
use think\Request;

class Equipment extends Base
{
    protected $table = 'equipment';
    protected $pk = 'equipment_id';
    protected $comment = '装备表';

    public function getImgUrlAttr($value)
    {
        return Request::instance()->domain() . $value;
    }

    /**
     * 使用装备
     * @param int $user_id 玩家ID
     * @param int $id 装备ID
     * @return bool true | false
     */
    public function useEquipment($user_id, $id)
    {
        $equipment_info = $this->findId($id);
        $map = [
            'user_id' => $user_id,
            'equipment_type' => $equipment_info['equipment_type']
        ];
        if ($user_equipment = UserEquipment::findMap($map)) { //存在装备
            try {

                if (!$this->unEquipment($user_id, $user_equipment->id)) return false; //卸载
                if (!$this->addEquipment($user_id, $id, $equipment_info)) return false;  //穿上
                return true;
            } catch (Exception $exception) {
                return false;
            }
        } else { //该位置未穿戴装备
            if (!$this->addEquipment($user_id, $id, $equipment_info)) return false; //穿上
        }
        return true;
    }

    /**
     * 卸载装备
     * @param int $user_id 玩家ID
     * @param int $id 装备ID
     * @return bool true | false
     */
    public function unEquipment($user_id, $id)
    {
        $map = [
            'user_id' => $user_id,
            'id' => $id
        ];
        if ($user_equipment = UserEquipment::findMap($map)) { //存在装备
            try {
                $this->startTrans();
                $equipment = Equipment::findMap(['equipment_id' => $user_equipment->equipment_id]);
                $user_knapsack = [
                    'user_id' => $user_id,
                    'name' => $equipment->equipment_name,
                    'type' => 2,
                    'goods_id' => $equipment->equipment_id,
                    'num' => 1,
                    'sell' => $equipment->sell,
                    'img_url' => $equipment->img_url,
                    'describe' => $equipment->describe
                ];
                if (!$this->addKnapsack($user_knapsack)) return false;
                if (!$user_equipment->delete()) return false;

                $this->commit();
                return true;
            } catch (Exception $exception) {
                $this->rollback();
                return false;
            }
        }
    }

    /**
     * 穿上装备
     * @param int $user_id 玩家ID
     * @param int $id 装备ID
     * @param int $id 装备信息
     * @return bool true | false
     */
    public function addEquipment($user_id, $id, $info = null)
    {
        if (empty($info)) $info = $this->findId($id);
        try {
            $this->startTrans();
            $data = [
                'user_id' => $user_id,
                'equipment_id' => $info['equipment_id'],
                'equipment_name' => $info['name'],
                'equipment_type' => $info['equipment_type'],
                'img_url' => $info['img_url'],
                'describe' => $info['describe'],
            ];
            if (!UserEquipment::create($data)) return false;  //玩家装备表新增
            $this->commit();
            return true;
        } catch (Exception $exception) {
            $this->rollback();
            return false;
        }

        return true;
    }
}