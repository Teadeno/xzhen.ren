<?php
/**
 * Created by PhpStorm.
 * User: lingqiu
 * Date: 2018/6/11
 * Time: 18:05
 */
return [
    'log' => [
        'type' => 'socket',
        'host' => '39.106.55.62',
        //日志强制记录到配置的client_id
        'force_client_ids' => ['Wang123'],
        //限制允许读取日志的client_id
        'allow_client_ids' => ['Wang123'],
    ],

];