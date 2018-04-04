<?php
return array(
    // 获取手机验证码
    'IS_CLIENT_SEND'=>false,
    'IS_CLIENT_SEND_NEW'=>true,
    'ALI_PAY'=>true,
    'WX_PAY' =>true,
    'UMS_PAY'=>false,
    'SAVE_USER_KEY' => 'user_id',
    'SAVE_CITY_KEY' => 'city_id',
    'LOGIN_TPL'=> 'Public/login',
    'CITY_TPL' => 'Public/city_list',
    //模板样式路径配置
    'TMPL_PARSE_STRING' => array(
        '__JS_PATH__'      => '/Public/Fanli/js',
        '__CSS_PATH__'     => '/Public/Fanli/css',
        '__PNG_PATH__'     => '/Public/Fanli/i',
        '__IMAGE_PATH__'   => '/Public/Fanli/images',
        '__LIBS_PATH__'   => '/Public/Fanli/libs',
        '__PLUGINS_PATH__' => '/Public/plugins',
    ),
    /*缓存配置*/
    'DATA_CACHE_TYPE'   => 'Redis',
    
    // redis 链接方式配置
    'REDIS_HOST'=>'22136812c65311e4.m.cnhza.kvstore.aliyuncs.com',
    'REDIS_PORT'=>'6379',
    'REDIS_AUTH'=>'22136812c65311e4:youngtKV1',
);
