<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用入口文件
// 检测PHP环境
if (version_compare(PHP_VERSION, '5.3.0', '<'))
    die('require PHP > 5.3.0 !');

date_default_timezone_set('Asia/Hong_Kong');

// 旧版商户端兼容
$url_path = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
if (strpos(strtolower($url_path), '/boss') !== false) {
    $new_boss_url = 'http://' . $_SERVER['HTTP_HOST'] . '/Merchant/Public/login.html';
    header("location: $new_boss_url");
    // header("location: http://www.youngt.com/Boss/");
    die();
}

// 跳转到手机 pc首页和详情静态化处理
if (strpos(strtolower($url_path), 'm.youngt.com') === false && (strpos(strtolower($url_path), 'team-') !== false || !trim(str_replace('/', '', $_SERVER['REQUEST_URI'])))) {
    $file_name = __DIR__ . '/script/Http.class.php';
    if (file_exists($file_name)) {
        require_once $file_name;
        if (class_exists('Http')) {
            $http = new \Http();
            if ($http->is_mobile()) {
                $request_uri = strtolower($_SERVER['REQUEST_URI']);
                $host_url = 'http://m.youngt.com';
                if (strpos($request_uri, 'team-') !== false) {
                    list($_, $team_id) = explode('-', $request_uri);
                    $team_id = intval($team_id);
                    $host_url = "http://m.youngt.com/Team/detail/id/{$team_id}.html";
                }
               header("location: $host_url");
               die();
            }
        }
    }
}

// 老代理兼容
if (strpos(strtolower($url_path), '/daili') !== false) {
    header("location: http://admin.youngt.com/daili");
    die();
}

if (strpos(strtolower($url_path), 'mobile.youngt.com') !== false && strpos(strtolower($url_path), '/city/change/id/') !== false) {
    list($_, $c_id) = explode("/city/change/id/", strtolower($url_path));
    $c_id = intval($c_id);
    $host_url = 'http://' . $_SERVER['HTTP_HOST'] . "/Public/changeCity/id/{$c_id}";
    header("location: {$host_url}");
    die();
}

if (strpos(strtolower($url_path), '/order/notify/') !== false) {
    $href = "http://x.youngt.com{$_SERVER['REQUEST_URI']}";
    $file_name = __DIR__ . '/script/Http.class.php';
    $res = '';
    if (file_exists($file_name)) {
        require_once $file_name;
        if (class_exists('Http')) {
            $http = new \Http();
            $res = $http->post($href, $_POST);
        }
    }
    die($res);
}

// 对参数特殊处理
if (isset($_POST['version'])) {
    $_POST['youngt_ts_version'] = $_POST['version'];
    unset($_POST['version']);
}

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG', true);

// 定义应用目录
define('APP_PATH', '../Application/');

// 定义应用主域
define('APP_DOMAIN', 'ree9.com');

//静态文件缓存目录
define('HTML_PATH', './Html/');

// 引入ThinkPHP入口文件
require '../framework/core.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单
