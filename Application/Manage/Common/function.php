<?php

/**
 * Created by JetBrains PhpStorm.
 * User: zhoujz
 * Date: 15-3-9
 * Time: 下午3:01
 *
 */
if (!function_exists('getAccess')) {

    function getAccess($k) {
        $_ACCESS_LIST = $_SESSION['_ACCESS_LIST'];
        if (!empty($_SESSION[C('ADMIN_AUTH_KEY')])) {
            return true;
        }

        if (!is_null($_ACCESS_LIST['APP'][strtoupper($k)])) {
            return true;
        } else {
            return false;
        }
    }

}

/**
 * 权限检测
 * @param type $access_name
 * @return boolean
 */
if (!function_exists('auth_check_access')) {

    function auth_check_access($access_name = array()) {
        if (is_string($access_name)) {
            $access_name = @explode(',', $access_name);
        }
        $module_name = strtolower(MODULE_NAME);
        $auth = new \Common\Org\Auth($module_name);
        $auth_config = C('AUTH_CONFIG');
        $user = session(C('USER_AUTH_KEY'));
        $uid = ternary($user['id'], '');

        if (isset($user['fagent_id']) && trim($user['fagent_id']) == '0') {
            return array_pop(array_reverse($access_name));
        }

        if ($access_name) {
            foreach ($access_name as $v) {
                $name = strtolower($v);

                if (strpos($v, $module_name) === false) {
                    $name = "$module_name/$name";
                }
                if ($auth->auth_check_access($uid, $auth_config, $name)) {
                    return $v;
                }
            }
        }
        return false;
    }

}
