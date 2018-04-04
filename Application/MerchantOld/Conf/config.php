<?php
// 设置session过期时间
$expire_time = 60*60*24;
@session_set_cookie_params($expire_time);
@ini_set("session.gc_maxlifetime", $expire_time);

return array(
    
    // session 过期时间
    'SESSION_EXPIRE'=>$expire_time,
    'SESSION_OPTIONS'=> array(
        'expire'=>$expire_time
    ),
  /**
     * 权限认证配置
     */
    'AUTH_CONFIG' => array(
        'OPEN_AUTH_RULE_REGISTER' => false,
        'SUPER_ADMIN_ID' => array(
        ),
        'COMMON_AUTH_LIST' => array(
            'merchant/public/login' => '登录',
            'merchant/public/checklogin' => '登录',
            'merchant/public/logout' => '退出',
            'merchant/public/help' => '查看帮助信息',
            'merchant/partner/editpwd' => '修改密码',
            'merchant/index/index' => '首页',
            'merchant/coupon/index' => '首页',
        ),
        'AUTH_ON' => true, //认证开关
        'AUTH_TYPE' => 2, // 认证方式，1为时时认证；2为登录认证。
        'AUTH_GROUP' => 'auth_group', //用户组数据表名
        'AUTH_GROUP_ACCESS' => 'auth_group_access', //用户组明细表
        'AUTH_RULE' => 'auth_rule', //权限规则表
        'AUTH_USER' => 'login_access'//用户信息表
    ),
    'USER_AUTH_KEY'=>'partnerMerchant',
    'USER_AUTH_GATEWAY' => 'Public/login',
   

    'TMPL_PARSE_STRING' => array(
        '__APP_NAME__' => 'Merchant',
        '__ASSET_PATH__' => '/Public/MerchantOld',
        '__JS_PATH__'    => '/Public/MerchantOld/js',
        '__CSS_PATH__'   => '/Public/MerchantOld/css',
        '__PLUGINS_PATH__'=>'/Public/plugins',
    ),
    'CSS_VER' => 1,
    'JS_VER' => 1,
);