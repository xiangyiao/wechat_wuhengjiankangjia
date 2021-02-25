    <?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2021-02-25
 * Time: 13:23
 */
    return [
        //又拍云配置(测试)
        'upyun' => [
            'user' => [
                'server' => 'image-yuximi',
//            'username' => 'yuximi2app',     //仅有写入权限
//            'password' => 'QazWsxEdc123',
                'username' => 'pengdashu',
                'password' => 'pengdashu',
            ],
            'thumb_version' => [  //缩略图版本
                '10%' => '!type10',
                '20%' => '!type20',
                '30%' => '!type30',
                '40%' => '!type40',
                '50%' => '!type50',
                '60%' => '!type60',
                '70%' => '!type70',
                '80%' => '!type80',
                '90%' => '!type90',
                'middle' => '!middle',  //宽600px
                'big' => '!big', //宽800px
                'small' => '!small'    //宽100px
            ],
            'access_domain' => 'http://upyun.yuximi.com',  //文件访问域名
        ],
    ];