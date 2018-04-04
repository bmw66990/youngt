<?php


/**
 *
 * 测试oss代码
 *
 **/
// echo time();exit;
// 设置时区
date_default_timezone_set('UTC');

// 设置php执行不超时
set_time_limit(0);

// 设置套接字连接不超时
ini_set('default_socket_timeout', -1);

// 定义根目录
define('BASE_DIR', __DIR__ . '/');

// 引入OSS类库
require_once dirname(BASE_DIR) . '/PushAppMessage.class.php';

$pushAppMessage = new \Common\Org\PushAppMessage();

$data = array(
'title'=>'浓情端午，相约青团',
'content'=>'浓情端午，相约青团，畅想最低折扣，只为与亲同乐！',
'custom'=>array(), 
);
// 全站 全平台推
foreach(array('android','ios') as $v){
  $data['plat'] = $v;
  var_dump($pushAppMessage->pushMessageToAll($data));
}

exit;

$data = array(
'title'=>'[杨凌一元众筹]电影票',
'content'=>'中影时代国际影城杨凌旗舰店2D电影票',
'custom'=>array(
	'type'=>"team_detail",
		'data'=>array(
		  'id'=>'117746',
		)
	), 
'tags'=>array('1')
);

// 给城市推 tags 城市id
foreach(array('android','ios') as $v){
  $data['plat'] = $v;
  //var_dump($pushAppMessage->pushMessageToTags($data));
}
exit;
$data = array(
'title'=>'[杨凌一元众筹]电影票',
'content'=>'中影时代国际影城杨凌旗舰店2D电影票',
'custom'=>array(
	'type'=>"team_detail",
	'data'=>array(
		  'id'=>'117136',
	      )
    ), 

'account'=>array('1')
);

// 给某个用户推 account 用户id
foreach(array('android','ios') as $v){
  $data['plat'] = $v;
  //var_dump($pushAppMessage->pushMessageToAccess($data));
}
