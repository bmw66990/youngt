<?php
return array(
    'TMPL_PARSE_STRING' => array(
        '__JS_PATH__'      => '/Public/Home/Js',
        '__CSS_PATH__'     => '/Public/Home/Css',
        '__IMAGE_PATH__'   => '/Public/Home/Image',
        '__PLUGINS_PATH__' => '/Public/plugins',
    ),
   
    'COOKIE_DOMAIN' =>'.'.APP_DOMAIN,
    //'配置项'=>'配置值'
    'SESSION_OPTIONS' => array(
        'domain' => '.'.APP_DOMAIN,
    ),
    'MEMBER_AUTH_KEY' => 'mid',

    //'配置项'=>'配置值'
    'URL_PATHINFO_DEPR' =>'/',

    //sql解析缓存
    'DB_SQL_BUILD_CACHE' => true,
    'DB_SQL_BUILD_LENGTH' => 100,
    
    //静态缓存配置
    'HTML_CACHE_ON'=>false,
    'HTML_FILE_SUFFIX'  =>    '.html',
    'HTML_CACHE_RULES'=> array(
        'index:index' => array('{:action}', 600),
        'team:detail' => array('team-{tid}', 600),
        'Help:'       => array('{$_SERVER.REQUEST_URI|md5}', 600),
    ),

    'INDEX_VIEW'        => array( 308,411,606,1),
    
    // pc特殊过滤的团单id
    'PC_BUY_OTHER_TEAM'=>array(
        '109290'=>'[一元夺宝第1期]：小米九号平衡车Ninebot',
    ),


    //CSS JS version
    'CSS_VER' => 1,
    'JS_VER'  => 1,
);