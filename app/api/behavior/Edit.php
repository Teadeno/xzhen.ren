<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/26
 * Time: 12:14
 */

namespace app\api\behavior;

use app\api\model\UserAttribute;
use app\api\model\UserResource;
use think\Db;

class Edit
{
    /**
     * 修为值改变
     * $param arrar 包含最后一次修改时间，增长速度, 增加用户,当前修为值
     */
    public function editQuality(&$params)
    {
        if (!isset($params['user_id'])) {
            return false;
        }
        if (!isset($params['quality_edit_time']) || !isset($params['practice_speed']) || !isset($params['quality'])) {
            $params = UserResource::findMap(['user_id' => $params['user_id']], 'user_id, quality, quality_edit_time')->toArray();
            $params = array_merge($params, UserAttribute::findMap(['user_id' => $params['user_id']], 'practice_speed')->toArray());
        }
        $time = time();
        $quality = ($time - $params['quality_edit_time']) / 5 * $params['practice_speed'];

        $result = UserResource::where('user_id', $params['user_id'])->isUpdate(true)->save([
            'quality' => $quality + $params['quality'],
            'quality_edit_time' => $time
        ]);
        if (!$result) {
            return false;
        } else {
            return true;
        }
    }
}