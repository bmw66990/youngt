<?php

$href = "http://api.youngt.com{$_SERVER['REQUEST_URI']}";
$file_name = dirname(__DIR__) . '/script/Http.class.php';
$res = '';
if (file_exists($file_name)) {
    require_once $file_name;
    if (class_exists('Http')) {
        $http = new \Http();
        $res = $http->post($href, $_POST);
    }
}
die($res);
