<?php
return array(
    //'配置项'=>'配置值'
    'verifyKey'  => 'youngtxxx',        //验证使用密钥   注意：一点确定不能随意修改，否则客户端无法使用
    'dataKey'    => 'youngtdata',       //加密数据使用密钥   注意：一点确定不能随意修改，否则客户端无法使用
    'tokenKey'   => 'youngtyyy',        //token验证使用密钥  注意：一点确定不能随意修改，否则客户端无法使用
    'signCheck'  => true,               //是否验证签名
    'tokenCheck' => false,              //是否验证token

    //客户端更新配置
    'AppUpdateIos'=>array(  'ver'=>'5.0',
        'description'=>"1.性能优化\n 2.界面美化\n 3.新增更多支付方式\n" ,
        'url'=>"https://itunes.apple.com/cn/app/qing-tuan-wang/id921133719?mt=8"
    ),

    'AppUpdateAndroid'=>array(  'ver'=>'58',
        'description'=>"1.优化了微信联合登陆\n2.更新支付宝支付\n3.增加在线提现功能\n4.修改系统bug",
        'url'=>"http://ytfile.oss-cn-hangzhou.aliyuncs.com/youngt.apk"
    ),

 

);