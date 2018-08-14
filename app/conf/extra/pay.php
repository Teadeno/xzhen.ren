<?php
return [
    'alipay' => [
        //APPID
        'appId' => '2018071260618056',
        //签名方式
        'signType' => 'RSA2',
        //异步回调地址
        'notify_url' => 'https://api.xzhen.ren/api/notify/ail_notify',
        //开发者私钥
        'rsaPrivateKey' => 'MIIEowIBAAKCAQEAwXZC0NosDcWazvxeUYL8+PyqD03RVzhaOM2sdQQ9vzQbupRJMgrPkm+PzxSDCJ7chZH/Fg3OFVFUQaEO0RV6YHZ7SGrf9FadomMdDGKGlcxpH1pGSumDteE8lOXMZaPs4PrnPGEJsYCR0cdCQQ9r8io7GUU2Vq86GLv/EMrxGRPe6etpt3BVYN/KK/QYghxolMYX1QYDlhWpBEhDAVt/g+8ZtYcj198/hClDI0EghfjlNGZnS+ZE905XXzqaaiNB07ZsVjg9yOrJxPGVt1hoYdE+uaccbnM07a8CWvD+zxOXXCagk+VnLqT2lerGLrUFj+M5JYeBj2cX4PHfVpu14wIDAQABAoIBAD/Bl5d4rxgCzVf1d6fpQTFVIz5T6O+TlLgvn+wYb7j5lUHR6KVjRrD9rykRm3VV1Vr3mFdOSWJhISFX0Kg7Kc9sfIiLIC9H9D815rbUKYJokSRR/eRmPI/OIMb5THkaotAqH9aYLDehJW06+6yxRmPSYxOa6aN7r+vC2PZRy07114fbpXfl8vQFJ0bpf36bgEy3xynBKx3s9tpzuIzuOubQgrd+5SkA1C7yIIrLZi5jqdc6rDdDCyRr9tdhITZBTneFzOzJfFgaCnPLsAnM6spuDDkYUbMBfERLP7cTSMkl9aC2X4zC2mafnn8lgr2hHsShy4rS/GCC/yMQvxFfMeECgYEA/cIseg44JuvYbo1ITC56AgFVgNeoJLGv06/zhtHB//vXVHF517uYBty9art7zvmxGCGhktep7SOwvBn98jel+hNHhRtywk2R3YZ5pC7EtjB6SoycUvthoaTDwLRGkQ+3ld2pk7rMwXqEbtidkkRbpqCu90wl+6xzFojuRi3cw+kCgYEAwyu8+gCPE8PBOHCBqv61QoREYTThyJ6PKsvNSppuzks/LOJdUhXmxYpy8IDaMR7tUBvjuarKsTRGctwT8m18ufsH8Xar3detYWB2DIpkFm3TwfQL9nmY8dOarhP2N9XM0CFlOP+pNz2GtoyuQY53eP136Ws2n/H9d9M9G3IEh+sCgYBmWCoYECQVjol8DP9bumL67A7QKCpookGH3y8Y8QCNfcUbCalamQ53tg+vPG5yy8HhOa8wykp+miZttw304vzOH3h49tkSBLcP4WyuCy7LTEIGTy+9SCXYS9unnQ3Y873Xc88xBeYPIprHGhZzJ57PVX87vjqThrnkHNX8+AJSAQKBgE8jVxdQTrPG9nw6MnnxmfIEy+1Wkg7VLFxAE1gi0rdXj6BVkV2Nf+utvfmDZfXxAm0vQeGxPUspVT+RuYzdlGudPJVOd1YW9Di98mAXZYoqsmi82nTwzXGRSfE4KuhtCwWB5Rd9o1HA4vT2iloOuKcYsBy32oh0iUCjKO4OB4RNAoGBAJzgPz7dIPeWWxsJDFDW492SkihHN7aD1HrH4OwgabA+q+p6vmBZrQzZKFTZvM0vSNotEMw/2UP7YjTb7GLbvwZxKUYvjIn1IY9U+ip4taCX7ljrK60gdzCYkgAFFbYQxWsYl9NTFYc4REqBisb7mTJaCI0I6K2zKvxdPRoHfvXt',
        //支付宝公钥
        'alipayrsaPublicKey' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAoJ7I80M18hEpijpNl8KkB3wY8fsan8ukVAucLcjROwBvNYQs9iVDzWLXr7GhDQQaatLEka9swtZVeMf1L9RLosAcFCeKwotCNJqA8s8tmlve0/3AZAE59PS9A/zTxnRzwPHF/vldDzM0O81n5piZZP1VPk2WEI5jDVLC6HK9T3nOyu68Bu842/wWeaavXE5z5NeajeNWUGTmasqdxfXD4JPQkvFzFRQbSBhQ/Blei6DKttYxSynx9mVxfJcPuraG3aiOSuRo7FhLd/FbmMFHeARbPWc33x5ewCaOH3+Bm0kAnYcNVlz3FvCB+GFAkPerB2Y91M8aCp6WhlNSi7EFJQIDAQAB',
    ],
    'wxpay' => [
        //APPID
        'appId' => 'wxb1f9a2f9810950dd',
        //商户号
        'mchid' => '1509867871',
        //私钥
        'key' => 'e38b273349af27cea2cc71d72f50c0c5',
        //异步回调地址
        'notify_url' => 'https://api.xzhen.ren/api/notify/wx_notify',
        //AppSecret 获取open_id需要
        'secret' => '67f1231a5438bec0e78b8a99b4b7dddc',
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
            'pay_total' => 100,
            'num' => 100,
            'type' => 4,
            'describe' => '1元购买100顶级灵石',
        ],
        'djls600' => [
            'name' => '购买600顶级灵石',
            'pay_total' => 600,
            'num' => 600,
            'type' => 4,
            'describe' => '6元购买600顶级灵石',
        ],
        'djls1800' => [
            'name' => '购买1800顶级灵石',
            'pay_total' => 1800,
            'num' => 1800,
            'type' => 4,
            'describe' => '18元购买1800顶级灵石',
        ],
        'djls6800' => [
            'name' => '购买6800顶级灵石',
            'pay_total' => 6800,
            'num' => 6800,
            'type' => 4,
            'describe' => '68元购买6800顶级灵石',
        ],
        'djls16800' => [
            'name' => '购买16800顶级灵石',
            'pay_total' => 16800,
            'num' => 16800,
            'type' => 4,
            'describe' => '168元购买16800顶级灵石',
        ],
        'djls32800' => [
            'name' => '购买32800顶级灵石',
            'pay_total' => 32800,
            'num' => 32800,
            'type' => 4,
            'describe' => '328元购买32800顶级灵石',
        ],
        'djls64800' => [
            'name' => '购买64800顶级灵石',
            'pay_total' => 64800,
            'num' => 64800,
            'type' => 4,
            'describe' => '648元购买64800顶级灵石',
        ],
    ],
];