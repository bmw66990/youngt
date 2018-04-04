<?php
/**
 * 权限检测
 * @param type $access_name
 * @return boolean
 */
function auth_check_access($access_name=array()){
    if(is_string($access_name)){
        $access_name = @explode(',', $access_name);
    }
     $module_name = strtolower(MODULE_NAME);
     $auth = new \Common\Org\Auth();
     $auth_config =  C('AUTH_CONFIG');
     $user = session(C('SAVE_USER_KEY'));
     $uid = ternary($user['id'], '');
    if($access_name){
        foreach($access_name as $v){
             $name = strtolower($v);
            
            if(strpos($v, $module_name) === false){
                 $name = "$module_name/$name";
            }
            if(true){
                return $v;
            }
        }
    }
    return false;
}
