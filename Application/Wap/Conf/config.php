<?php
return array(

    'TMPL_PARSE_STRING' => array(
        '__JS_PATH__'      => '/Public/Wap/js',
        '__CSS_PATH__'     => '/Public/Wap/css',
        '__IMAGE_PATH__'   => '/Public/Wap/image',
        '__PLUGINS_PATH__' => '/Public/plugins',
    ),

    //登录模板路径
    'LOGIN_TPL'=> 'Public/login',
    'CITY_TPL' => 'Public/city',
    'SAVE_USER_KEY' => 'user_id',
    'SAVE_CITY_KEY' => 'city_id',
    //CSS JS version
    'CSS_VER' => 1,
    'JS_VER'  => 1,

    // weixin pay url
    'WX_PAY_URL' => 'http://yangling.youngt.com/Wap/',
    //静态缓存配置
    'HTML_CACHE_ON'=>true,
    'HTML_FILE_SUFFIX'  =>    '.html',
    'HTML_CACHE_RULES'=> array(
        'Activities:index' => array('active-{activities_id}-{city_id}', 600),
    ),

);