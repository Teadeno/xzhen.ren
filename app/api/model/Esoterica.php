<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 17:25
 */

namespace app\api\model;

use think\Db;
use think\Loader;
use think\Request;

//功法表
class Esoterica extends Base
{
    protected $table = 'esoterica';
    protected $pk = 'esoterica_id';
    protected $comment = '功法表';
    //功法配置
    private $config = [
        //功法类型
        'esoterica_type' => [1 => '册', 2 => '天书', 3 => '御法', 4 => '攻决', 5 => '身法', 6 => '遁法', 7 => '秘诀', 8 => '心经', 9 => '秘术'],
        //功法增加属性值  一维键对应值，二维键对应属性类型1、修炼速度2、生命3、防御4、攻击5、闪避6、速度7、暴击8、韧性9、命中
        "add_attribute" => [
            1 => [1 => 50, 2 => 50, 3 => 20, 4 => 25, 5 => 11, 6 => 5, 7 => 11, 8 => 10, 9 => 10],
            2 => [1 => 100, 2 => 100, 3 => 40, 4 => 50, 5 => 22, 6 => 10, 7 => 22, 8 => 20, 9 => 20],
            3 => [1 => 300, 2 => 300, 3 => 120, 4 => 150, 5 => 66, 6 => 30, 7 => 66, 8 => 60, 9 => 60],
            4 => [1 => 500, 2 => 500, 3 => 200, 4 => 250, 5 => 110, 6 => 50, 7 => 110, 8 => 100, 9 => 100],
            5 => [1 => 800, 2 => 800, 3 => 320, 4 => 400, 5 => 176, 6 => 80, 7 => 176, 8 => 160, 9 => 160],
            6 => [1 => 1000, 2 => 1000, 3 => 400, 4 => 500, 5 => 220, 6 => 100, 7 => 220, 8 => 200, 9 => 200],
            7 => [1 => 3000, 2 => 3000, 3 => 1200, 4 => 1500, 5 => 660, 6 => 300, 7 => 660, 8 => 600, 9 => 600],
            8 => [1 => 5000, 2 => 5000, 3 => 2000, 4 => 2500, 5 => 1100, 6 => 500, 7 => 1100, 8 => 1000, 9 => 1000],
            9 => [1 => 8000, 2 => 8000, 3 => 3200, 4 => 4000, 5 => 1760, 6 => 800, 7 => 1760, 8 => 1600, 9 => 1600],
            10 => [1 => 10000, 2 => 10000, 3 => 4000, 4 => 5000, 5 => 2200, 6 => 1000, 7 => 2200, 8 => 2000, 9 => 2000],
        ],
        'attribute_name' => [1 => '修炼速度', 2 => '生命', 3 => '防御', 4 => '攻击', 5 => '闪避', 6 => '速度', 7 => '暴击', 8 => '韧性', 9 => '命中'],
        //学习功法消耗门贡值
        'stu_S' => [0, 100, 300, 500, 1000, 3000, 5000, 10000, 13000, 15000, 20000],
        //学习功法消耗顶级灵石价格
        'stu_TL' => [0, 100, 200, 300, 400, 500, 600, 700, 800, 900, 1000],
        //功法升级消耗门贡，灵石价格
        'price_SL' => [
            1 => [0, 100, 100, 100, 100, 100, 100, 100, 100, 100, 100],
            2 => [0, 300, 300, 300, 300, 300, 300, 300, 300, 300, 300],
            3 => [0, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500],
            4 => [0, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000],
            5 => [0, 3000, 3000, 3000, 3000, 3000, 3000, 3000, 3000, 3000, 3000],
            6 => [0, 5000, 5000, 5000, 5000, 5000, 5000, 5000, 5000, 5000, 5000],
            7 => [0, 10000, 10000, 10000, 10000, 10000, 10000, 10000, 10000, 10000, 10000],
            8 => [0, 30000, 30000, 30000, 30000, 30000, 30000, 30000, 30000, 30000, 30000],
            9 => [0, 50000, 50000, 50000, 50000, 50000, 50000, 50000, 50000, 50000, 50000],
            10 => [0, 100000, 100000, 100000, 100000, 100000, 100000, 100000, 100000, 100000, 100000],
        ],
        //功法升级消耗修为值价格
        'price_quality' => [
            1 => [0, 1000, 1500, 2000, 2500, 3000, 4200, 4700, 5200, 5700, 6200],
            2 => [0, 13280, 31560, 37400, 49840, 68120, 86400, 104680, 122960, 141240, 159520],
            3 => [0, 218600, 323622, 428644, 533666, 638688, 743710, 848732, 953754, 1058776, 1163800],
            4 => [0, 1542000, 1813733, 2085466, 2357199, 2628932, 2900665, 3172398, 3444131, 3755864, 3987600],
            5 => [0, 5076000, 6405600, 7735200, 9064800, 10394400, 11724000, 13053600, 14383200, 15712800, 17042400],
            6 => [0, 21324000, 25801333, 30278666, 34755999, 39233332, 43710665, 48187998, 52665331, 57142664, 61620000],
            7 => [0, 72456000, 93218666, 113981336, 134744006, 155506676, 176269346, 197032016, 217794686, 238557356, 259320000],
            8 => [0, 325400000, 375968888, 426537776, 477106664, 527675552, 578244440, 628813328, 679382216, 729951104, 780519992],
            9 => [0, 867320000, 1043222222, 1219124444, 1395026666, 1570928888, 1746831110, 192273332, 2098635554, 2274537776, 2450440000],
            10 => [0, 3015680000, 7506488888, 11997297776, 16488106664, 20978915552, 25469724440, 29960533328, 34451342216, 38942151104, 43432960000],
        ],
    ];
    
    /**
     *数据库增加功法
     * 录入数据用
     * */
    private function add()
    {
        //获取门派
        $arr = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23,
            24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44,
            45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55];
        $school = Db::name('school')->where('school_id', 'in', $arr)->select();
        foreach ($school as $key => $value) {
            foreach ($this->config['esoterica_type'] as $k => $v) {
                $n = 10;
                $f_id = 0;
                while ($n >= $value['level']) {
                    $i = 10;
                    while ($i >= 1) {
                        if ($n == 10 && $i == 10) {
                            $info = [
                                'name' => '《' . $value['name'] . $v . '》' . '顶级',
                                'price_id' => '',
                                'type' => $k,
                                'value' => $this->config['add_attribute'][$n][$k],
                                'f_id' => $f_id,
                                'realm_id' => "",
                                'school_id' => $value['school_id'],
                                'steps' => $i,
                                'level' => $n,
                                'img_url' => '/static/api/img/esoterica/' . $k . '01.png',
                                'sell' => $n * 1000,
                                'describe' => $value['name'] . '功法，' . '大成',
                            ];
                            $f_id = Db::name('esoterica')->insertGetId($info);
                        }
                        $info = [
                            'name' => '《' . $value['name'] . $v . '》' . $n . '星' . $i . '重',
                            'price_id' => guid(),
                            'type' => $k,
                            'value' => $this->config['add_attribute'][$n][$k],
                            'f_id' => $f_id,
                            'realm_id' => "",
                            'school_id' => $value['school_id'],
                            'steps' => $i,
                            'level' => $n,
                            'img_url' => '/static/api/img/esoterica/' . $k . '01.png',
                            'sell' => $n * 1000,
                            'describe' => $value['name'] . '功法，学习后' . $this->config['attribute_name'][$k] . '+' . $this->config['add_attribute'][$n][$k],
                        ];
                        $f_id = Db::name('esoterica')->insertGetId($info);
                        
                        if ($i == 1 && ($n == $value['level'])) {
                            $price = [
                                [
                                    'price_id' => $info['price_id'],
                                    'type' => 4,
                                    'value' => $this->config['stu_S'][$n],
                                ],
                                [
                                    'price_id' => $info['price_id'],
                                    'type' => 6,
                                    'value' => $this->config['stu_TL'][$n],
                                ],
                            ];
                        } else {
                            
                            $price = [
                                [
                                    'price_id' => $info['price_id'],
                                    'type' => 4,
                                    'value' => $this->config['price_SL'][$n][$i],
                                ],
                                [
                                    'price_id' => $info['price_id'],
                                    'type' => 1,
                                    'value' => $this->config['price_SL'][$n][$i],
                                ],
                                [
                                    'price_id' => $info['price_id'],
                                    'type' => 2,
                                    'value' => $this->config['price_quality'][$n][$i],
                                ],
                            ];
                        }
                        Db::name('price')->insertAll($price);
                        $i--;
                    }
                    $n--;
                }
            }
            
        }
        return "完成";
    }
    
    /**
     *数据库删除功法
     * 清空功法表  并删除关联价格
     * */
    private function del()
    {
        $data = Db::name('esoterica')->select();
        
        foreach ($data as $value) {
            Db::name('price')->where('price_id', $value['price_id'])->delete();
            Db::name('esoterica')->where('esoterica_id', $value['esoterica_id'])->delete();
        }
    }
    /**
     *图片路径增加域名
     * */
    public function getImgUrlAttr($value)
    {
        return Request::instance()->domain() . $value;
    }
    
    /**
     *学习功法
     *
     * @param int $user_id 玩家ID
     * @param array $data  功法id
     * @param array $data  功法信息
     *
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
            'content' => '天资卓越' . $info['name'] . '已练成',
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
     *                     * @param int $id 玩家ID
     *
     * @param object $data 功法信息
     *
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
                'content' => '天资卓越' . $data['name'] . '已练成',
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