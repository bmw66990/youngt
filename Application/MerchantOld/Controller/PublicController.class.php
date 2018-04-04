<?php

/**
 * Created by PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 2015/4/7
 * Time: 17:28
 */

namespace Merchant\Controller;

use Think\Controller;

/**
 * 公共控制器
 * Class PublicController
 * @package Manage\Controller
 */
class PublicController extends Controller {

    //代理登陆
    public function login() {
        $this->display();
    }

    /*
     * 处理登陆数据
     */

    public function checkLogin() {

        $username = I('post.username', '', 'strval');
        $password = I('post.password', '', 'strval');
        $agree = I('post.agree', '0', 'trim');
        
        $login_cookie_key=C('USER_AUTH_KEY').'_cookie';
        $login_cookie_value = ternary(cookie($login_cookie_key), '');
        if($agree && $login_cookie_value){
            $username_password = @explode('##', $login_cookie_value);
            $_password = @array_pop($username_password);
            $_username = @array_pop($username_password);
            if($_username){
                $username = $_username;
            }
            $_password = @base64_decode($_password);
            if($_password){
                $_password = substr($_password,0,  strlen($_password)-32);
                $_password = @base64_decode($_password);
            }
            if($_password){
                $password = $_password;
            }
        }
        if (!trim($username)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '用户名不能为空！'));
        }
        if (!trim($password)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '密码不能为空！'));
        }

        $partner = M('partner');
        $login_access = M('login_access');
        $where = array('username' => $username, 'type' => 'partner');
        $loginAccessRes = $login_access->where($where)->find();
        if (!$loginAccessRes) {
            $partnerRes = $partner->where(array('username' => $username))->field(array('id', 'password', 'username', 'title', 'is_branch', 'db_id'))->find();
            if (!$partnerRes) {
                $this->ajaxReturn(array('code' => -1, 'error' => '用户不存在！'));
            }
            $loginAccessRes = array(
                'username' => ternary($partnerRes['username'], ''),
                'password' => ternary($partnerRes['password'], ''),
                'type' => 'partner',
                'status' => 1,
                'is_super_admin' => 'Y',
                'uid' => ternary($partnerRes['id'], ''),
                'create_time' => time(),
            );
            $uid = $login_access->add($loginAccessRes);
            if (!$uid) {
                $this->ajaxReturn(array('code' => -1, 'error' => '登录失败！'));
            }
            $loginAccessRes['id'] = $uid;
        }

        if (isset($loginAccessRes['status']) && !trim($loginAccessRes['status'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '该账号已禁用！'));
        }

        if (!isset($loginAccessRes['password']) || strcmp(trim($loginAccessRes['password']), trim(encryptPwd($password))) !== 0) {
            $loginAccessRes['password'] = $partner->where(array('username' => $username))->getField('password');
            if (!isset($loginAccessRes['password']) || strcmp(trim($loginAccessRes['password']), trim(encryptPwd($password))) !== 0) {
                $this->ajaxReturn(array('code' => -1, 'error' => '密码错误！'));
            }
            $login_access->where(array('id' => $loginAccessRes['id']))->save(array('password' => $loginAccessRes['password']));
        }

        // 更新登录信息
        $now_time =  time();
        $data = array(
            'login_time' => $now_time,
            'login_ip' => get_client_ip(),
        );
        $res = $login_access->where(array('id' => $loginAccessRes['id']))->save($data);
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '登录失败！'));
        }
        $partnerRes = $partner->where(array('id' => $loginAccessRes['uid']))->field(array('id', 'password', 'username', 'title', 'is_branch', 'db_id','city_id'))->find();
        if (!$partnerRes) {
            $this->ajaxReturn(array('code' => -1, 'error' => '对应的商户不存在！'));
        }
        $partnerRes['login_access_is_super_admin'] = ternary($loginAccessRes['is_super_admin'], '');
        $partnerRes['login_access_id'] = ternary($loginAccessRes['id'], '');

        // 是否支持订座
        $partnerRes['is_dingzuo'] = 0;
        $isDingzuo = M('dingzuo')->where(array('partner_id' => $partnerRes['id']))->count();
        if ($isDingzuo >= 0) {
            $partnerRes['is_dingzuo'] = 1;
        }
        
        // 是否有活动
        $where = array(
            'is_voluntary_in'=>'Y',
            'end_time' => array('gt', time()),
            'type'=>'activities',
            'cityid'=>array('in',array(957,  ternary($partnerRes['city_id'], '')))
        );
        $is_exist_activities_res =D('Admanage')->isExistActivities($where);
        $is_exist_activities='N';
        if($is_exist_activities_res){
            $is_exist_activities='Y';
        }
        $partnerRes['is_exist_activities'] = $is_exist_activities;

        // 保存session
        $expire_time = 60*60*24*7;
        session_set_cookie_params($expire_time);
        session(array('name'=>C('USER_AUTH_KEY'),'expire'=>$expire_time));
        session(C('USER_AUTH_KEY'), $partnerRes);
        if($agree){
            $password_cookie_value= base64_encode(base64_encode($password).strtoupper(md5($_SERVER['HTTP_HOST'])));
            $login_cookie_value = "{$username}##{$password_cookie_value}";
            cookie($login_cookie_key, $login_cookie_value, $expire_time, '/');
        }
       
        $this->ajaxReturn(array('code' => 0, 'message' => '登陆成功！'));
    }

    /**
     * 注销
     */
    public function logout() {
        // 清除cookie
        $login_cookie_key=C('USER_AUTH_KEY').'_cookie';
        cookie($login_cookie_key,null);
        
        // 清除session
        session_destroy();
        $this->redirect('Public/login');
    }
    
    /**
     * 查看帮助信息
     */
    public function help() {
        $sid = I('get.sid', '', 'strval');
        $this->assign('sid', $sid);
        $this->display('Help/index');
    }

}
