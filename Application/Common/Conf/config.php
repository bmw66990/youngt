<?php

return array(

    'SHOW_PAGE_TRACE'   => false,              // 显示页面Trace信息
    'LOAD_EXT_CONFIG'   => 'setting',
    
    
     /* 第三方支付回调log输出 */
    'PAY_CALLBACK_LOG' =>true,
    
    // 是否使用公共
    'IS_OPEN_COMMON_OPERATION'=>true,
    'tokenKey'   => 'youngtyyy',  // token验证使用密钥  注意：一点确定不能随意修改，否则客户端无法使用
    

    /* URL设置 */
    'URL_MODEL'         => 2,                  //URL模式
    'URL_PATHINFO_DEPR' => '/',                //URL参数之间的分割符号
    'URL_HTML_SUFFIX'   => 'html',            //URL静态后缀
    'siteURL'          => '',
    'uploadURL'        => '',
    'uploadPath'         => './Uploads/',
    'URL_CASE_INSENSITIVE' =>true,
   
    /* 子域名配置 */
    'APP_SUB_DOMAIN_DEPLOY'  => 1,             // 开启子域名配置
    'APP_SUB_DOMAIN_RULES'   => array(
        'api'           => 'Api',                
        'ddhomeapi'           => 'DDHomeApi',  
        'mobile'           => 'Wap' ,            
        'm'           => 'Wap' ,               
        'manage'           => 'Manage',                          
        'admin'           => 'Admin',            
        'youngt'           => 'YoungtApi',         
        'merchant'           => 'Merchant',        
        'merchantapi'           => 'MerchantApi',    
        'coperation'           => 'Common_operation',
        'fanli'           => 'Fanli',
        'mf'           => 'FanliManage',
    ),
    
     // 正式库连接
   
   /*'DB_DEPLOY_TYPE'=>true,
   'DB_RW_SEPARATE'=>true,                                          
   'DB_TYPE'           => 'mysql',            // 数据库类型   从库 rdsn2qqf2a73ara.mysql.rds.aliyuncs.com  新从库rdsl8hb86f572c9s79cr.mysql.rds.aliyuncs.com
   'DB_HOST'           => 'youngt20131106.mysql.rds.aliyuncs.com,rdsn2qqf2a73ara.mysql.rds.aliyuncs.com',
   'DB_NAME'           => 'db8l8852n3f1734k,db8l8852n3f1734k',
   'DB_USER'           => 'db8l8852n3f1734k,db8l8852n3f1734k',
   'DB_PWD'            => 'supere_1986youngt_com_2016,supere_1986youngt_com_2016',
   'DB_PORT'           => '3306',
   'DB_PREFIX'         => '',*/
    



    /* 数据库设置 */

   'DB_TYPE'           => 'mysql',            // 数据库类型   从库 rdsn2qqf2a73ara.mysql.rds.aliyuncs.com  新从库rdsl8hb86f572c9s79cr.mysql.rds.aliyuncs.com
   'DB_HOST'           => '120.55.99.219',
   'DB_NAME'           => 'db8l8852n3f1734k',
   'DB_USER'           => 'root',
   'DB_PWD'            => 'qtw@youngt@123456',
   'DB_PORT'           => '3306',
   'DB_PREFIX'         => '',
    
    
//      'DB_DEPLOY_TYPE'=>true,
//   'DB_RW_SEPARATE'=>true,                                          
//   'DB_TYPE'           => 'mysql',            // 数据库类型   从库 rdsn2qqf2a73ara.mysql.rds.aliyuncs.com  新从库rdsl8hb86f572c9s79cr.mysql.rds.aliyuncs.com
//   'DB_HOST'           => '120.55.99.219,rdsn2qqf2a73ara.mysql.rds.aliyuncs.com',
//   'DB_NAME'           => 'db8l8852n3f1734k,db8l8852n3f1734k',
//   'DB_USER'           => 'root,db8l8852n3f1734k',
//   'DB_PWD'            => 'qtw@youngt@123456,supere_1986youngt_com',
//   'DB_PORT'           => '3306',
//   'DB_PREFIX'         => '',


    /*图片地址前缀*/
    'IMG_PREFIX'=>'http://pic.youngt.com',

    'COOKIE_PREFIX' => 'tp_',
    'COOKIE_EXPIRE' => 86400 * 7,
    'COOKIE_PATH' => '/',
    'COOKIE_DOMAIN' =>$_SERVER['HTTP_HOST'],
    //'配置项'=>'配置值'
    'SESSION_OPTIONS' => array(
        'domain' => $_SERVER['HTTP_HOST'],
    ),

    /* 令牌验证 */
    'TOKEN_ON'          => false,              // 是否开启令牌验证
    'TOKEN_NAME'        => '__hash__',         // 令牌验证的表单隐藏字段名称
    'TOKEN_TYPE'        => 'md5',              //令牌哈希验证规则 默认为MD5
    'TOKEN_RESET'       => false,              //令牌验证出错后是否重置令牌 默认为true

    'MODULE_ALLOW_LIST'    =>    array('Home','Admin','Manage','Api','DDHomeApi','Boss','Wap','Operate','MerchantApi','Merchant','YoungtApi','Common_operation','NeighborApi','FastchargeApi','FanliManage','Fanli'),
    'DEFAULT_MODULE'       =>    'Home',  // 默认模块
    'URL_CASE_INSENSITIVE' =>false,

    /*自定义配置*/
    'LOAD_EXT_CONFIG' => 'redisChannels,sendSmsMessage,pointsRule,ota',
    'ASYNC_SERVICE'   => false,

    /*日志配置*/
    'LOG'           => true,

    /*图片上传配置*/
    'IMAGE_DRIVER'      => 1,                   //1:GD   2:Imagick
    'THUMB'             => true,                //是否生成缩略图
    'THUMB_WIDTH'       => 100,                 //缩略图宽
    'THUMB_HEIGHT'      => 100,                  //缩略图高
     'TMPL_ACTION_SUCCESS'=>'Public:success',
     'TMPL_ACTION_ERROR'=>'Public:error',
    /*缓存配置*/
    'DATA_CACHE_TYPE'   => 'Redis',
    
    // redis 链接方式配置
    'REDIS_HOST'=>'120.55.99.219',
    'REDIS_PORT'=>'6379',
    
    // 正式环境阿里云 redis
//    'REDIS_HOST'=>'22136812c65311e4.m.cnhza.kvstore.aliyuncs.com',
//    'REDIS_AUTH_USER_PASSWOED'=>'22136812c65311e4:youngtKV1',
    // 'REDIS_AUTH'=>'22136812c65311e4:youngtKV1',
    // session 保存位置调整
//    'SESSION_TYPE' => 'Redis', //session保存类型
//    'SESSION_PREFIX' => 'sess_', //session前缀
//    'SESSION_EXPIRE' => 3600, //SESSION过期时间

    /*签到金额积分配置*/
    'DAYSIGN_SCORE'	=> 1,
    'DAYSIGN_MONEY' => 0,

    /*密码加密字串*/
    'PWD_ENCRYPT_STR'=>'@4!@#$%@',
    
    /*邮购产品用户选择的送货时间选项*/ 
    'MAIL_TEAM_DELIVERY_TIME'=>array(
        'work_rest_day'=>'工作日、休息日均可收货',
        'work_day'=>'只工作日收货',
        'rest_day'=>'只休息日收货',
    ),

    /*代金券配置*/
    'VOUCHERS' => array(
        'createState'    => true,    //创建优惠券开启
        'useState'       => true, //使用优惠券开启
        'createCitys'    => array(1, 1552, 411),
        'useCitys'       => array(1, 1552, 411),
        'createTeamType' => array('normal'), //创建团单类型
        'useTeamType'    => array('normal'), // 使用团单类型
        //生成代金券规则
        'createRules'    => array(
            array('min' => 1, 'max' => 99999, 'money' => 4,'begin_time'=>'2015-11-10 22:00:00','end_time'=>'2015-11-11 00:00:00','expire_time'=>'2015-11-14 00:00:00'),
            array('min' => 1, 'max' => 99999, 'money' => 11,'begin_time'=>'2015-11-11 23:00:00','end_time'=>'2015-11-12 00:00:00','expire_time'=>'2015-11-14 00:00:00'),
        ),
        //使用代金券金额
        'useRules'       => array(
            array('min' => 30, 'max' => 100, 'money' => 4),
            array('min' => 100, 'max' => 99999, 'money' => 11),
        )
    ),

    /*第三方电子凭证*/
    'THREE_VALID_STATE' => true,
    'THREE_VALID_PARTNER' => array(
        18732 => array(
            'partner_id' => 108,
            'vid' => 130,
            'query_vid'=>132,
        ),

    ),
    'SMSTYPE'=>'Jcsms',

);
?>
