<?php

return array(
    
     // session 保存位置调整
    'SESSION_TYPE' => 'Redis', //session保存类型
    'SESSION_PREFIX' => 'sess_', //session前缀
    'SESSION_EXPIRE' => 3600*24, //SESSION过期时间
    // 无权限  无登录 验证的uri
    'NO_AUTH_NO_LOGIN_URI'=>array(
        'common_operation/team/uploadimg'=>'团单图片上传',// 插件兼容性处理
    ),
    'TMPL_PARSE_STRING' => array(
        '__JS_PATH__' => '/Public/Common_operation/js',
        '__CSS_PATH__' => '/Public/Common_operation/css',
        '__IMAGE_PATH__' => '/Public/Common_operation/images',
        '__PLUGINS_PATH__' => '/Public/plugins',
    ),
    'SUPER_ADMIN_ID' => array(
        '1'=>'分销',
        '300623'=>'刘廷锋',
        '428790'=>'王娇',
        '374780'=>'宋少如',
        '630'=>'兰水兵',
        '445747'=>'范巧玲'
    ),
    'SAVE_USER_KEY'=>'CommonOperation',
    'CSS_VER' => 1,
    'JS_VER' => 1,
);
