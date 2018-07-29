<?php
return [
    'alipay' => [
        //APPID
        'appId' => '',
        //签名方式
        'signType' => 'RSA2',
        //异步回调地址
        'notify_url' => 'http://www.xzhen.ren/api/pay/ail_notify',
        //开发者私钥
        'rsaPrivateKey' => '',
        //支付宝公钥
        'alipayrsaPublicKey' => ''
    ],
    'wxpay' => [
        //APPID
        'appId' => 'wxb1f9a2f9810950dd',
        //商户号
        'mchid' => '1509867871',
        //私钥
        'key' => 'e38b273349af27cea2cc71d72f50c0c5',
        //异步回调地址
        'notify_url' => 'http://www.xzhen.ren/api/pay/wx_notify',
        //AppSecret 获取open_id需要
        'secret' => '67f1231a5438bec0e78b8a99b4b7dddc'
    ],
    'goods' => [
        'czjj18' => [
            'name' => '成长基金购买',
            'pay_total' => 18,
            'num' => 1,
            'type' => 1,
            'describe' => '购买成长基金，阶梯领取奖励',
        ],
        'yk12' => [
            'name' => '月卡购买',
            'pay_total' => 12,
            'num' => 1,
            'type' => 2,
            'describe' => '购买12元月卡',
        ],
        'zsk198' => [
            'name' => '终身卡购买',
            'pay_total' => 198,
            'num' => 1,
            'type' => 3,
            'describe' => '购买198元终身卡',
        ],
        'djls100' => [
            'name' => '购买100顶级灵石',
            'pay_total' => 1,
            'num' => 100,
            'type' => 4,
            'describe' => '1元购买100顶级灵石',
        ],
        'djls600' => [
            'name' => '购买600顶级灵石',
            'pay_total' => 6,
            'num' => 600,
            'type' => 4,
            'describe' => '6元购买600顶级灵石',
        ],
        'djls1800' => [
            'name' => '购买1800顶级灵石',
            'pay_total' => 18,
            'num' => 1800,
            'type' => 4,
            'describe' => '18元购买1800顶级灵石',
        ],
        'djls6800' => [
            'name' => '购买6800顶级灵石',
            'pay_total' => 68,
            'num' => 6800,
            'type' => 4,
            'describe' => '68元购买6800顶级灵石',
        ],
        'djls16800' => [
            'name' => '购买16800顶级灵石',
            'pay_total' => 168,
            'num' => 16800,
            'type' => 4,
            'describe' => '168元购买16800顶级灵石',
        ],
        'djls32800' => [
            'name' => '购买32800顶级灵石',
            'pay_total' => 328,
            'num' => 32800,
            'type' => 4,
            'describe' => '328元购买32800顶级灵石',
        ],
        'djls64800' => [
            'name' => '购买64800顶级灵石',
            'pay_total' => 648,
            'num' => 64800,
            'type' => 4,
            'describe' => '648元购买64800顶级灵石',
        ],
    ],
];