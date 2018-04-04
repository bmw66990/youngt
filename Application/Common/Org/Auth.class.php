<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-05-06
 * Time: 17:06
 */

namespace Common\Org;

class Auth extends \Think\Auth {

    protected $module_name = 'admin';
    protected $_pk = 'id';
    protected $getOperationName = array(
        'index' => '首页',
        'team' => '团单',
        'order' => '订单',
        'coupon' => '青团券',
        'user' => '用户',
        'partner' => '商户',
        'customerservice' => '客服',
        'manage' => '管理',
        'financial' => '财务',
        'dingzuo' => '订座',
        'encyclopedias' => '青团百科',
    );

    // 构造方法
    public function __construct($module_name = 'admin') {
        parent::__construct();
        $this->module_name = strtolower($module_name);
    }

    /**
     * 注册rule规则
     * @param class_name string  类的名称
     * @return str           返回错误或者正确信息
     */
    public function register($class_name) {
        $data = get_class_methods($class_name);

        //把一些父类的方法过滤掉    
        $arr = array('_checkblank', 'getProvince', 'getCities', 'getStationList', '_initialize', '__set', 'upload', 'isLogin', '__construct', '__construct', 'show', 'fetch', 'buildHtml', 'theme', 'assign', ' __set', 'get', '__get', '__isset', '__call', 'error', 'success', 'ajaxReturn', 'redirect', '__destruct');

        foreach ($arr as $k => $v) {
            if (in_array($v, $data)) {
                $key = array_search($v, $data);
                unset($data[$key]);
            }
        }
        $auth_rule = M('auth_rule');
        $module_name = strtolower(MODULE_NAME);
        $controller_name = strtolower(CONTROLLER_NAME);
        foreach ($data as $v) {
            $name = "$module_name/$controller_name/" . strtolower($v);
            $nameRes = $auth_rule->where(array('name' => $name))->find();
            if (!$nameRes) {
                $_data = array(
                    'name' => $name,
                    'module_name' => $module_name,
                );
                $auth_rule->add($_data);
            } else {
                $func = new \ReflectionMethod($class_name, $v);
                $tmp = $func->getDocComment();
             
                if ($tmp) {
                    $tmp = str_replace(array('*', '/', "\r\n", ' '), '', $tmp);
                }
                if ($tmp && (!isset($nameRes['title']) || !trim($nameRes['title']))) {
                    $auth_rule->where(array('name' => $name))->save(array('title'=>$tmp));
                }
            }
        }
    }

    private function __register_group() {
        $module_name = strtolower(MODULE_NAME);
        $auth_rule = M('auth_rule');
        $auth_group = M('auth_group');
        foreach ($this->getOperationName as $k => $v) {
            $name = "$module_name/$k/%";
            $auth_rule_ids = $auth_rule->where("name like '$name'")->select();
            $ids = array();
            foreach ($auth_rule_ids as $_v) {
                $ids[] = ternary($_v['id'], '');
            }
            $ids = array_filter($ids);
            $_data = array(
                'title' => $v,
                'status' => '1',
                'rules' => implode(',', $ids),
                'module_name' => $module_name,
                'remark' => $v,
            );
            $auth_group->add($_data);
        }
    }

    /**
     * 权限校验
     * @param type $uid
     * @param type $auth_config
     * @param type $name
     * @return boolean
     */
    public function auth_check_access($uid = '', $auth_config = array(), $name = '') {
        if (!trim($uid)) {
            return false;
        }
        if (trim($uid) && isset($auth_config['SUPER_ADMIN_ID'][$uid])) {
            return true;
        }
        if (!trim($name)) {
            $module_name = strtolower(MODULE_NAME);
            $controller_name = strtolower(CONTROLLER_NAME);
            $action_name = strtolower(ACTION_NAME);
            $name = "$module_name/$controller_name/$action_name";
        }
        if (isset($auth_config['COMMON_AUTH_LIST'][$name])) {
            return true;
        }
        return $this->check($name, $uid);
    }

    /**
     * 获取用户组列表
     * @param int $uid
     * @return array
     */
    public function getGroups($uid) {
        $module = $this->module_name;
        static $groups = array();
        if (isset($groups[$module . $uid])) {
            return $groups[$module . $uid];
        }
        $where_string = "a.uid='$uid' and g.status='1'";
        if ($module) {
            $where_string = "$where_string AND g.module_name='$module' AND a.module_name='$module'";
        }
        $user_groups = M()
                        ->table($this->_config['AUTH_GROUP_ACCESS'] . ' a')
                        ->where($where_string)
                        ->join($this->_config['AUTH_GROUP'] . " g on a.group_id=g.id")
                        ->field('a.uid,a.group_id,g.title,g.rules')->select();
        $groups[$module . $uid] = $user_groups ? : array();
        return $groups[$module . $uid];
    }

    /**
     * 设置模块
     * @param $module
     */
    public function setModule($module) {
        $this->module_name = $module;
    }

    /**
     * 设置用户表名和主键
     * @param $pk
     * @param $table
     */
    public function setUser($pk) {
        $this->_pk = $pk;
    }

    /**
     * 获取用户信息
     * @param $uid
     * @return mixed
     */
    protected function getUserInfo($uid) {
        static $userinfo = array();
        $field = $this->module_name . $uid;
        if (!isset($userinfo[$field])) {
            $userinfo[$field] = M()->where(array($this->_pk => $uid))->table($this->_config['AUTH_USER'])->find();
        }
        return $userinfo[$field];
    }

    /**
     * 获取用户权限列表
     * @param int $uid
     * @param int $type
     * @return array
     */
    public function getAuthList($uid, $type) {
        static $_authList = array(); //保存用户验证通过的权限列表
        $t = $this->module_name . implode(',', (array) $type);
        if (isset($_authList[$uid . $t])) {
            return $_authList[$uid . $t];
        }
        if ($this->_config['AUTH_TYPE'] == 2 && isset($_SESSION['_AUTH_LIST_' . $uid . $t])) {
            return $_SESSION['_AUTH_LIST_' . $uid . $t];
        }

        //读取用户所属用户组
        $groups = $this->getGroups($uid);
        $ids = array(); //保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid . $t] = array();
            return array();
        }

        $map = array(
            'id' => array('in', $ids),
            'type' => $type,
            'status' => 1,
        );
        //读取用户组所有权限规则
        $rules = M()->table($this->_config['AUTH_RULE'])->where($map)->field('condition,name')->select();

        //循环规则，判断结果。
        $authList = array();   //
        foreach ($rules as $rule) {
            if (!empty($rule['condition'])) { //根据condition进行验证
                $user = $this->getUserInfo($uid); //获取用户信息,一维数组
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                //dump($command);//debug
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = strtolower($rule['name']);
                }
            } else {
                //只要存在就记录
                $authList[] = strtolower($rule['name']);
            }
        }
        $_authList[$uid . $t] = $authList;
        if ($this->_config['AUTH_TYPE'] == 2) {
            //规则列表结果保存到session
            $_SESSION['_AUTH_LIST_' . $uid . $t] = $authList;
        }
        return array_unique($authList);
    }

}
