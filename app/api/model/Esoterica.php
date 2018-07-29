<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:25
 */

namespace app\api\model;

use think\Loader;
use think\Request;

//功法表
class Esoterica extends Base
{
    protected $table = 'esoterica';
    protected $pk = 'esoterica_id';
    protected $comment = '功法表';

    public function getImgUrlAttr($value)
    {
        return Request::instance()->domain() . $value;
    }

    /**
     *学习功法
     * @param int $user_id 玩家ID
     * @param array $data 功法id
     * @param array $data 功法信息
     * @return bool true | false
     * */
    public function useEsoterica($user_id, $id, $info = null)
    {
        if (empty($info)) $info = $this->findId($id)->toArray();
        //验证玩家是否学习过该功法
        $update['esoterica_id'] = UserAttribute::findMap(['user_id' => $user_id])->toArray()['esoterica_id'];
        if ($d = $this->findMap(['type' => $info['type'], 'school_id' => $info['school_id'], 'level' => $info['level'], 'esoterica_id' => ['in', $update['esoterica_id']]])) {
            return '你已学习';
        }
        $update['esoterica_id'][] = $info['esoterica_id'];
        $user_log = [
            'user_id' => $user_id,
            'type' => 6,
            'content' => '天资卓越' . $info['name'] . '已练成'
        ];
        try {
            if (!Loader::model('UserAttribute')->save($update, ['user_id' => $user_id])) return false;
            if (!$this->addAttribute($user_id, $info['type'], $info['value'], 'esoterica')) return false;
            if (!UserLog::create($user_log)) return false;
            return true;
        } catch (Exception $exception) {

            return false;
        }

    }

    /**
     *升级功法
     * * @param int $user_id 玩家ID
     * * @param int $id 玩家ID
     * @param object $data 功法信息
     * @return bool true | false
     * */
    public function upgradeEsoterica($user_id, $id, $data = null)
    {
        if (empty($data)) $data = $this->findId($id, 'esoterica_id, name, price_id, type, value')->toArray();
        //获取该功法的下级功法
        $p_id = $this->findMap(['f_id' => $data['esoterica_id']], 'esoterica_id')->esoterica_id;
        try {
            $this->startTrans();
            if (!$this->addAttribute($user_id, $data['type'], $data['value'], 'esoterica')) return false; //属性增加

            $update['esoterica_id'] = UserAttribute::findMap(['user_id' => $user_id])->toArray()['esoterica_id'];  //功法列表修改
            if (array_key_exists($id, $update['esoterica_id'])) return '功法已学习';
            $update['esoterica_id'][$p_id] = $data['esoterica_id'];   // 上级id  替换为升级id
            if (!Loader::model('UserAttribute')->save($update, ['user_id' => $user_id])) return false;

            $user_log = [
                'user_id' => $user_id,
                'type' => 6,
                'content' => '天资卓越' . $data['name'] . '已练成'
            ];
            if (!UserLog::create($user_log)) return false;
            $this->commit();
            return true;
        } catch (Exception $exception) {
            $this->rollback();
            return false;
        }

        return true;
    }
}