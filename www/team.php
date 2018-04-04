<?php
//兼容旧版wap团单详情
$id = $_GET['id'];
$url = 'http://m.youngt.com/Team/detail/id/'.$id;
header("location: $url");
