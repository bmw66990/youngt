<?php

return array(
    'TMPL_PARSE_STRING' => array(
        '__JS_PATH__' => '/Public/Admin/js',
        '__CSS_PATH__' => '/Public/Admin/css',
        '__IMAGE_PATH__' => '/Public/Admin/images',
        '__PLUGINS_PATH__' => '/Public/plugins',
    ),
    'USER_AUTH_GATEWAY' => 'Public/index',
    'SAVE_USER_KEY'=>'AdminInfo',
    'CSS_VER' => 1,
    'JS_VER' => 1,
    // session 保存位置调整
    'SESSION_TYPE' => 'Redis', //session保存类型
    'SESSION_PREFIX' => 'sess_', //session前缀
    'SESSION_EXPIRE' => 3600*24, //SESSION过期时间
    //缓存保存方式
    'DATA_CACHE_TYPE'   => 'Redis',
    /**
     * 网站地址配置
     */
    'YOUNGT_URL'=>'http://yangling.youngt.com',
     // session 保存位置调整
    'SESSION_TYPE' => 'Redis', //session保存类型
    'SESSION_PREFIX' => 'sess_', //session前缀
    'SESSION_EXPIRE' => 3600*24, //SESSION过期时间
    'IS_OPEN_COMMON_OPERATION'=>true,
    /**
     * 权限认证配置
     */
    'AUTH_CONFIG' => array(
        'OPEN_AUTH_RULE_REGISTER' => true,

        // 无权限  无登录 验证的uri
        'NO_AUTH_NO_LOGIN_URI'=>array(
            'admin/team/uploadimg'=>'团单图片上传',// 插件兼容性处理
        ),

        'SUPER_ADMIN_ID' => array(
            '1'=>'分销',
            //'899034'=>'王志兵',
           // '925861'=>'弥二涛',
           // '696942'=>'代平山',
            '300623'=>'刘廷锋',
        ),
        // 招商权限组配置
        'CB_AUTH_GROUP_ID'=>array(
            // 招商总监权限组id
            'MANAGER'=>11,
            // 招商普通职员权限组id
            'EMPLOYEE'=>9,
        ),
        'COMMON_AUTH_LIST' => array(
            'admin/index/index' => '首页',
            'admin/index/daysign' => '签到显示',
            'admin/index/ajaxdaysign' => '签到显示',
            'admin/index/ajaxtotal' => '异步获取',
            'admin/encyclopedias/qingtuanencyclopedias' => '青团百科',
            'admin/encyclopedias/encyclopediasdetail' => '百科详情',
        ),
        'AUTH_ON' => true, //认证开关
        'AUTH_TYPE' => 2, // 认证方式，1为时时认证；2为登录认证。
        'AUTH_GROUP' => 'auth_group', //用户组数据表名
        'AUTH_GROUP_ACCESS' => 'auth_group_access', //用户组明细表
        'AUTH_RULE' => 'auth_rule', //权限规则表
        'AUTH_USER' => 'user'//用户信息表
    )
);
