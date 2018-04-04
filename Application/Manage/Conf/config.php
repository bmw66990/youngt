<?php
return array(
    
      // session 保存位置调整
    'SESSION_TYPE' => 'Redis', //session保存类型
    'SESSION_PREFIX' => 'sess_', //session前缀
    'SESSION_EXPIRE' => 3600*24, //SESSION过期时间
    
	
     /**
     * 权限认证配置
     */
    'AUTH_CONFIG' => array(
        'OPEN_AUTH_RULE_REGISTER' => true,
        // 无权限  无登录 验证的uri
        'NO_AUTH_NO_LOGIN_URI'=>array(
            'manage/team/uploadimg'=>'团单图片上传',// 插件兼容性处理
        ),
        'SUPER_ADMIN_ID' => array(
         //   '696942'=>'代平山',
        ),
        'COMMON_AUTH_LIST' => array(
            'manage/public/login' => '登录',
            'manage/public/logout' => '注销',
            'manage/public/doLogin' => '登录',
            'manage/public/verify' => '验证码',
            'manage/index/index' => '首页',
            'manage/index/getregusernum' => '首页',
            'manage/index/getordernum' => '首页',
            'manage/index/getrefundnum' => '首页',
            'manage/index/getreceptionnum' => '首页',
            'manage/index/getreception' => '首页',
            'manage/index/getordersouce' => '首页',
            'manage/index/getordercount' => '首页',
            'manage/index/defalut' => '首页',
            'manage/encyclopedias/    qingtuanencyclopedias' => '青团百科首页',
            'manage/encyclopedias/encyclopediasdetail' => '百科详情',
        ),
        'AUTH_ON' => true, //认证开关
        'AUTH_TYPE' => 2, // 认证方式，1为时时认证；2为登录认证。
        'AUTH_GROUP' => 'auth_group', //用户组数据表名
        'AUTH_GROUP_ACCESS' => 'auth_group_access', //用户组明细表
        'AUTH_RULE' => 'auth_rule', //权限规则表
        'AUTH_USER' => 'user'//用户信息表
    ),
   
    'USER_AUTH_GATEWAY'  => 'Public/login',
    'USER_AUTH_KEY'=>'userManage',
    'CITY_AUTH_KEY'=>'cityInfo',

    'TMPL_PARSE_STRING' => array(
        '__ASSET_PATH__'   => '/Public/Manage',
        '__JS_PATH__'      => '/Public/Manage/js',
        '__CSS_PATH__'     => '/Public/Manage/css',
        '__PLUGINS_PATH__' => '/Public/Manage/plugins',
        '__IMAGE_PATH__'   => '/Public/Manage/Image'
    ),
    'CSS_VER' => 1,
    'JS_VER' => 1,
    
    // 申请结算开始时间
    'WITHDRAWALS_BEGIN_TIME'=>'2016-04-01 00:00:00',
    
    // 旧版代理后台地址
    'OLD_MANAGE_URL'=>'http://csapp.youngt.com/Manage',

    'LOAD_EXT_FILE' => 'auth',
    /*订单手续费*/
    'ORDER_FREE' => array(
        'ALIPAY'    => 1,
        'TENPAY'    => 1,
        'WECHATPAY' => 1,
        'UMSPAY'    => 1
    ),

);