<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class UserController extends CommonController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 商家详情首页
     */
    public function accessManager() {

        $username = I('get.username', '', 'trim');
        $where = array('is_super_admin' => 'N', 'uid' => $this->partner_id);
        if ($username) {
            $where['_string'] = "username like '{$username}'";
        }
        $login_access = M('login_access');
        $count = $login_access->where($where)->count();
        $Page = $this->pages($count, $this->reqnum);
        $list = $login_access->where($where)
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        $data = array(
            'count' => $count,
            'page' => $Page->show(),
            'list' => $list,
            'username' => $username,
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 添加
     */
    public function accessManagerAdd() {
        $operation_type = I('post.operation_type', '', 'trim');
        if (!$operation_type) {
            $data = array(
                'operation_type' => 'accessManagerAdd',
            );
            $this->assign($data);
            $this->display();
            exit;
        }
        $login_data = I('post.login_access', array(), '');
        if (!isset($login_data['username']) || !trim($login_data['username'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '登录账号不能为空！'));
        }
        if (!isset($login_data['password']) || !trim($login_data['password'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '密码不能为空！'));
        }
        if (!isset($login_data['rpassword']) || !trim($login_data['rpassword'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '确认不能为空！'));
        }
        if (strcmp(trim($login_data['password']), trim($login_data['rpassword'])) !== 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '两次密码输入不一致！'));
        }
        $length = strlen($login_data['password']);
        if ($length > $this->pwd_max_length || $length < $this->pwd_min_length) {
            $this->ajaxReturn(array('code' => -1, 'error' => "新密码必须大于{$this->pwd_min_length}位，小于{$this->pwd_max_length}位！"));
        }
        $partner = M('partner');
        $where = array('username' => $login_data['username']);
        $partnerCount = $partner->where($where)->count();
        if ($partnerCount || $partnerCount > 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '该登录账号已经注册，请更换登陆账号！'));
        }
        $login_access = M('login_access');
        $loginAccessCount = $login_access->where($where)->count();
        if ($loginAccessCount || $loginAccessCount > 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '该登录账号已经注册，请更换登陆账号！'));
        }
        $data = array(
            'username' => ternary($login_data['username'], ''),
            'password' => encryptPwd(ternary($login_data['password'], '')),
            'type' => 'partner',
            'is_super_admin' => 'N',
            'status' => 1,
            'uid' => $this->partner_id,
            'create_time' => time(),
        );
        $res = $login_access->add($data);
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '添加账号失败！'));
        }
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 编辑
     */
    public function accessManagerEdit() {
        $operation_type = I('post.operation_type', '', 'trim');
        $access_id = I('get.id', '', 'trim');
        $login_access = M('login_access');
        if (!$operation_type) {
            if (!$access_id) {
                $this->ajaxReturn(array('code' => -1, 'error' => 'id不能为空！'));
            }
            $login_access_res = $login_access->where(array('id' => $access_id))->find();
            $this->assign($login_access_res);
            $data = array(
                'operation_type' => 'accessManagerEdit',
            );
            $this->assign($data);
            $this->display('User/accessManagerAdd');
            exit;
        }
        $login_data = I('post.login_access', array(), '');
        if (!$access_id) {
            $access_id = ternary($login_data['id'], '');
        }
        if (!isset($login_data['username']) || !trim($login_data['username'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '登录账号不能为空！'));
        }
        if ((isset($login_data['password']) && trim($login_data['password'])) || (isset($login_data['rpassword']) && trim($login_data['rpassword']))) {
            if (strcmp(trim($login_data['password']), trim($login_data['rpassword'])) !== 0) {
                $this->ajaxReturn(array('code' => -1, 'error' => '两次密码输入不一致！'));
            }
            $length = strlen($login_data['password']);
            if ($length > $this->pwd_max_length || $length < $this->pwd_min_length) {
                $this->ajaxReturn(array('code' => -1, 'error' => "新密码必须大于{$this->pwd_min_length}位，小于{$this->pwd_max_length}位！"));
            }
        }

        $partner = M('partner');
        $where = array('username' => $login_data['username'], 'id' => array('neq', $access_id));
        $loginAccessCount = $login_access->where($where)->count();
        if ($loginAccessCount || $loginAccessCount > 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '该登录账号已经注册，请更换登陆账号！'));
        }
        $data = array(
            'username' => ternary($login_data['username'], ''),
        );
        if (isset($login_data['password']) && trim($login_data['password'])) {
            $data['password'] = encryptPwd(ternary($login_data['password'], ''));
        }
        $res = $login_access->where(array('id' => $access_id, 'uid' => $this->partner_id, 'is_super_admin' => 'N'))->save($data);
        if ($res === false) {
            $this->ajaxReturn(array('code' => -1, 'error' => '更新失败！'));
        }
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 删除
     */
    public function accessManagerDelete() {
        $access_id = I('get.id', '', 'trim');
        if (!$access_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '操作账号的id不能为空！'));
        }
        $login_access = M('login_access');
        $where = array('id' => $access_id, 'uid' => $this->partner_id, 'is_super_admin' => 'N');
        $loginAccessCount = $login_access->where($where)->count();
        if (!$loginAccessCount || $loginAccessCount <= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '你要删除的账号不存在！'));
        }

        $model = M();
        $model->startTrans();
        $res = $login_access->where($where)->delete();
        if (!$res) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '账号删除失败！'));
        }

        $auth_group_access = M('auth_group_access');
        $where = array('uid' => $access_id, 'module_name' => strtolower(MODULE_NAME));
        $auth_group_access_count = $auth_group_access->where($where)->count();
        if ($auth_group_access_count && $auth_group_access_count > 0) {
            $res = $auth_group_access->where($where)->delete();
            if (!$res) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '账号删除失败！'));
            }
        }
        $model->commit();
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 授权
     */
    public function accessManagerdoAuth() {
        $user_id = I('get.id', '', 'trim');
        $operation_type = I('post.operation_type', '', 'trim');
        if (!$operation_type) {
            if (!$user_id) {
                redirect(U("User/accessManager"));
            }
            $where = array('id' => $user_id, 'uid' => $this->partner_id, 'is_super_admin' => 'N');
            $userRes = M('login_access')->where($where)->field('id')->find();
            if (!$userRes) {
                redirect(U("User/accessManager"));
            }
            $this->assign($userRes);
            $module_name = strtolower(MODULE_NAME);
            $auth_group_list = $this->getAuthGroupList($module_name, $user_id);
            // 获取城市
            $data = array(
                'auth_group_list' => $auth_group_list,
                'operation_type' => 'accessManagerdoAuth',
                'module_name' => $module_name,
            );
            $this->assign($data);
            $this->display('User/doAuth');
            exit;
        }

        $user = I('post.user', array(), '');
        if (!$user_id) {
            $user_id = ternary($user['id'], '');
        }
        if (!$user_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '用户id不能为空！'));
        }
        if (!isset($user['module_name']) || trim($user['module_name']) == '') {
            $this->ajaxReturn(array('code' => -1, 'error' => '权限组名称不能为空！'));
        }
        if (!isset($user['group_id']) || !$user['group_id']) {
            $user['group_id'] = array();
        }
        if (is_string($user['group_id'])) {
            $user['group_id'] = @explode(',', $user['group_id']);
        }
        $auth_group_access = M('auth_group_access');
        $module_name = strtolower($user['module_name']);
        $data = array();
        foreach ($user['group_id'] as $v) {
            $data[] = array(
                'module_name' => $module_name,
                'uid' => $user_id,
                'group_id' => trim($v),
            );
        }
        // 权限修改开启事务
        $model = M();
        $model->startTrans();
        $where = array(
            'uid' => $user_id,
            'module_name' => $module_name
        );
        $res = $auth_group_access->where($where)->count();
        if ($res && $res > 0) {
            $res = $auth_group_access->where($where)->delete();
            if (!$res) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '授权失败！'));
            }
        }
        if ($data) {
            $res = $auth_group_access->addAll($data);
            if (!$res) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '授权失败！'));
            }
        }
        $model->commit();
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 禁用
     */
    public function accessManagerdoDisabled() {
        $access_id = I('get.id', '', 'trim');
        if (!$access_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '操作账号的id不能为空！'));
        }
        $login_access = M('login_access');
        $where = array('id' => $access_id, 'uid' => $this->partner_id, 'is_super_admin' => 'N');
        $loginAccessCount = $login_access->where($where)->count();
        if (!$loginAccessCount || $loginAccessCount <= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '你要禁用的账号不存在！'));
        }

        $res = $login_access->where($where)->save(array('status' => 0));
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '账号禁用失败！'));
        }

        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 恢复使用
     */
    public function accessManagerdoRecovery() {
        $access_id = I('get.id', '', 'trim');
        if (!$access_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '操作账号的id不能为空！'));
        }
        $login_access = M('login_access');
        $where = array('id' => $access_id, 'uid' => $this->partner_id, 'is_super_admin' => 'N');
        $loginAccessCount = $login_access->where($where)->count();
        if (!$loginAccessCount || $loginAccessCount <= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '你要恢复的账号不存在！'));
        }

        $res = $login_access->where($where)->save(array('status' => 1));
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '账号恢复失败！'));
        }

        $this->ajaxReturn(array('code' => 0));
    }

}
