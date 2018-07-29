<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用行为扩展定义文件
return [
    // 应用初始化
    'app_init' => [],
    // 应用开始
    'app_begin' => [],
    // 模块初始化
    'module_init' => [],
    // 操作开始执行
    'action_begin' => [],
    // 视图内容过滤
    'view_filter' => [],
    // 日志写入
    'log_write' => [],
    // 应用结束
    'app_end' => [],
    //修为值同步行为   在首页获取，影响到修炼速度的地方执行
    'sync_quality' => [
        'app\\api\\behavior\\Sync'
    ],
    //任务执行 同步行为
    'sync_mission' => [
        'app\\api\\behavior\\Sync'
    ],
    //王朝资源  同步行为
    'sync_dynasty' => [
        'app\\api\\behavior\\Sync'
    ],
    //日志记录
    'resource_log' => [
        'app\\api\\behavior\\Log',
    ],
    'user_log' => [
        'app\\api\\behavior\\Log',
    ],
    'market_log' => [
        'app\\api\\behavior\\Log',
    ],
    'attribute_log' => [
        'app\\api\\behavior\\Log',
    ],
    'dynasty_log' => [
        'app\\api\\behavior\\Log',
    ],
    'wall_map_log' => [
        'app\\api\\behavior\\Log',
    ],
    //丹药使用数量记录
    'user_elixir_log' => [
        'app\\api\\behavior\\Log',
    ],
    //物品购买记录
    'goods_buy_log' => [
        'app\\api\\behavior\\Log',
    ],
];
