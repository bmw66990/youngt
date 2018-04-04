<?php
//兼容旧版wap特殊处理；
$name = $_GET['n'] ? $_GET['n'] : 'yangling';
$url = 'http://m.youngt.com/Public/changeCity/name/'.$name;
header("location: $url");
