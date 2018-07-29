<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:29
 */

namespace app\api\model;

use Think\Hook;

//境界表
class Realm extends Base
{
    protected $table = 'realm';
    protected $pk = 'realm_id';
    protected $comment = '境界表';


    /**
     *境界修改
     * @param int $user_id 玩家ID
     * @param array $data 境界id
     * @param array $data 境界信息
     * @return bool true | false
     * */
    public function editRealm($user_id, $id, $info = null)
    {
        if (empty($info)) $info = $this->findId($id);
        $map = ['user_id' => $user_id];
        $user_attribute = UserAttribute::findMap($map);
        $user_resource = UserResource::findMap($map, 'quality, realm_id, wall_map_num');
        try {
            $this->startTrans();
            $update = [
                'user_id' => $user_id,
                'practice_speed' => $user_attribute['practice_speed'] + $info['practice_speed'],
                'vita' => $user_attribute['vita'] + $info['vita'],
                'attack' => $user_attribute['attack'] + $info['attack'],
                'defense' => $user_attribute['defense'] + $info['defense'],
                'dodge' => $user_attribute['dodge'] + $info['dodge'],
                'speed' => $user_attribute['speed'] + $info['speed'],
                'critical_strike' => $user_attribute['critical_strike'] + $info['critical_strike'],
                'resistance' => $user_attribute['resistance'] + $info['resistance'],
                'hit' => $user_attribute['hit'] + $info['hit'],
                'ratio' => $info['ratio'],
            ];
            Hook::listen('sync_quality', $map);  //修为同步
            if (!UserAttribute::editMapData($map, $update)) return false;   //属性增加
            //判断是否晋升分神期
            $update = [];
            if ($info->grade == 7 && $info->steps == 1) {
                $update['wall_map_num'] = $user_resource->wall_map_num + 100;
                //当天进入挂图 则增加
                $wall = WallMap::findMap($map);
                if (!empty($wall)) {
                    $wall->count += 100;
                    $wall->save();
                }
            }
            $update['realm_id'] = $info->realm_id;
            if (!UserResource::editMapData($map, $update)) return false;  //境界改变

            $user_resource->quality = $user_resource->quality - $info->price; //修为减少
            if (!$user_resource->save()) return false;
            // 日志记录
            $user_log = [
                'user_id' => $user_id,
                'type' => 6,
                'content' => $this->getUserLogContent('realm', 1, $info->toArray()['name'])
            ];
            if (!UserLog::create($user_log)) {
                return false;
            }
            $this->commit();
            return true;
        } catch (Exception $exception) {
            $this->rollback();
            return false;
        }

    }
}