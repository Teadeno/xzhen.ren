<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/20
 * Time: 11:59
 */

namespace app\base\controller;


class Type
{
    //奖励类型
    static public $award = [
        1 => 'lingshi',                  //灵石
        2 => 'quality',                 //修为
        3 => 'prestige',               //声望
        4 => 'school_contribution',  //门贡
        6 => 'top_lingshi',  //门贡
        100 => 'goods'
    ];
    //奖励实物类型
    static public $award_goods = [
        1 => 'elixir',                //丹药
        2 => 'equipment',             //装备
        3 => 'esoterica',             //功法
        4 => 'resource',                //资源
        5 => 'dynasty',               //王朝资源
    ];
    //背包物品类型  //藏宝阁  鸿运商场一样
    static public $knapsack_type = [
        1 => 'elixir',                //丹药
        2 => 'equipment',             //装备
        3 => 'esoterica',             //功法
        4 => 'resource',                //资源
        5 => 'dynasty',               //王朝资源
    ];

    //用户资源变化类型
    static public $resource_log = [
        1 => 'lingshi',               //灵石
        2 => 'quality',               //修为
        3 => 'prestige',              //声望
        4 => 'school_contribution',  //门贡
        5 => 'skill',                 //铁门令
        6 => 'wall_map_num',         //挂图数8
        7 => 'wall_map_wheel',       //挂图单次轮数9
        8 => 'cloned'                 //分身符
    ];
    //资源表类型
    static public $resource = [
        1 => 'lingshi',               //灵石
        2 => 'quality',               //修为
        3 => 'prestige',              //声望
        4 => 'school_contribution',  //门贡
        5 => 'skill',                 //古神精血
        6 => 'skill',                 //古神精元
        7 => 'wall_map_num',         //挂图数8
        8 => 'wall_map_wheel',       //挂图单次轮数9
        9 => 'cloned',                 //分身符
        10 => 'top_lingshi',
    ];
    //王朝资源表类型
    static public $dynasty = [
        1 => 'population',      //人口资源
        2 => 'wood',             //木材
        3 => 'mineral',         //灵矿
        4 => 'food',            //食物
        5 => 'grass'            //灵草
    ];
    //王朝建筑类型
    static public $building = [
        1 => 'population',      //人口资源
        2 => 'wood',             //木材
        3 => 'mineral',         //灵矿
        4 => 'food',            //食物
        5 => 'grass'            //灵草
    ];
    //消耗类型
    static public $price = [
        1 => 'lingshi',      //灵石
        2 => 'quality',             //修为
        3 => 'prestige',         //声望
        4 => 'school_contribution',            //门贡
        5 => 'skill',            //神通点
        6 => 'rmb',            //RMB
    ];

    //装备增幅属性类型
    static public $equipment = [
        1 => 'practice_speed',   //修炼速度
        2 => 'vita',             //生命
        3 => 'defense',          // 防御
        4 => 'attack',          // 攻击
        5 => 'dodge',           // 闪避
        6 => 'speed',           //速度
        7 => 'critical_strike', //暴击
        8 => 'resistance',      //韧性
        9 => 'hit'              //命中
    ];
    //功法增幅属性类型
    static public $esoterica = [
        1 => 'practice_speed',   //修炼速度
        2 => 'vita',             //生命
        3 => 'defense',          // 防御
        4 => 'attack',          // 攻击
        5 => 'dodge',           // 闪避
        6 => 'speed',           //速度
        7 => 'critical_strike', //暴击
        8 => 'resistance',      //韧性
        9 => 'hit'              //命中
    ];
    //丹药增幅属性类型
    static public $elixir = [
        1 => 'practice_speed',   //修炼速度
        2 => 'vita',             //生命
        3 => 'defense',          // 防御
        4 => 'attack',          // 攻击
        5 => 'dodge',           // 闪避
        6 => 'speed',           //速度
        7 => 'critical_strike', //暴击
        8 => 'resistance',      //韧性
        9 => 'hit',              //命中
        10 => 'ratio'            //渡劫成功率
    ];
    //阵法增幅属性类型
    static public $tactical = [
        1 => 'attack',            //攻击
        2 => 'dodge',             //闪避
        3 => 'critical_strike', //暴击
        4 => 'defense',          // 防御
    ];
    //阵法消耗类型
    static public $consumption = [
        1 => 'population',      //人口资源
        2 => 'food',             //木材
        3 => 'mineral',         //灵矿
        4 => 'wood',            //食物
        5 => 'grass'            //灵草
    ];
    //门派职位
    static public $school = [
        0 => '杂役弟子',
        1 => '外门弟子',
        2 => '内门弟子',
        3 => '亲传弟子',
        4 => '执法',
        5 => '护法',
        6 => '长老',
        7 => '供奉',
        8 => '供奉',
        9 => '掌门',
    ];

    //挂图奖励记录
    static public $wall_map_type = [
        1 => 'elixir',                //丹药
        2 => 'equipment',             //装备
        3 => 'esoterica',             //功法
        4 => 'resource',                //资源
        5 => 'dynasty',               //王朝资源
        6 => 'user_resource',               //王朝资源
    ];

    //挂图奖励记录
    static public $school_position = [
        1 => 'elixir',                //丹药
        2 => 'resource',             //资源

    ];

    //物品购买记录购买类型
    static public $goods_buy = [
        1 => 'elixir',                //丹药
        2 => 'equipment',             //装备
        3 => 'esoterica',             //功法
        4 => 'resource',                //资源
        5 => 'dynasty',               //王朝资源
    ];
}