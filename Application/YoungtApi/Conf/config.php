<?php
return array(
    //'配置项'=>'配置值'
    'scjkey'  => 'Youngt20158958587',
    'verifyKey'  => 'youngtxxx',        //验证使用密钥   注意：一点确定不能随意修改，否则客户端无法使用
    'dataKey'    => 'youngtdata',       //加密数据使用密钥   注意：一点确定不能随意修改，否则客户端无法使用
    'tokenKey'   => 'youngtyyy',        //token验证使用密钥  注意：一点确定不能随意修改，否则客户端无法使用
    'signCheck'  => false,               //是否验证签名
    'tokenCheck' => false,              //是否验证token

    
    // 支付方式配置
    'TEN_PAY'=>false,
    'ALI_PAY'=>true,
    'WX_PAY' =>true,
    'UMS_PAY'=>true,
    'E_PAY'  =>false,
    'UNION_PAY'=>true,
    'LIANLIAN_PAY'=>true,
    
    // 获取手机验证码
    'IS_CLIENT_SEND'=>true,
    // app启动界面配置
    'APP_LOAD_IMAGE'=>array(
        'url'=>'load/default.png',
        'is_use'=>'Y',
    ),

    //客户端更新配置
    'AppUpdateIos'=>array(
        'ver'=>'1',
        'is_force'=>'N',
        'description'=>"1.性能优化\n 2.界面美化\n 3.新增更多支付方式\n" ,
        'url'=>"https://itunes.apple.com/cn/app/qing-tuan-wang/id921133719?mt=8"
    ),

    'AppUpdateAndroid'=>array(
        'ver'=>'4.0',
        'is_force'=>'N',
        'description'=>"1、界面优化，更方便浏览；\n2、增加商家分店显示；\n3、增加意见反馈功能；\n4、在列表中显示商家距离；",
        'url'=>"http://ytfile.oss-cn-hangzhou.aliyuncs.com/newyoungt.apk"
    ),
);