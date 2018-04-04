<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-07-24
 * Time: 08:53
 */

$data = M('System')->select();

$set = array();
foreach($data as $row) {
    $set[strtolower($row['keys'])] = $row['values'];
}

return array('POINTS' => $set);