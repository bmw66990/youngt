<?php

namespace Admin\Controller;

use Common\Controller\CommonBusinessController;

class PublicController extends CommonBusinessController {

    public function __construct() {
        parent::__construct();

        $error = I('get.error', '', 'strval');
        if (trim($error)) {
            $this->assign('error', base64_decode(str_replace(array('%2b', ' '), '+', urldecode($error))));
        }
    }

    public function index() {
        $this->display('Public/login');
    }

    public function login() {
        $username = I('post.username', '', 'strval');
        $password = I('post.password', '', 'strval');

        if (!trim($username)) {
            redirect(U('Public/index', array('error' => base64_encode('用户名不能为空！'))));
        }
        if (!trim($password)) {
            redirect(U('Public/index', array('error' => base64_encode('密码不能为空！'))));
        }
        $where = array(
            'mobile|username|email' => trim($username),
            'manager' => 'Y',
        );
        $user = M('user');
        $res = $user->where($where)->find();
        if (!$res) {
            redirect(U('Public/index', array('error' => base64_encode('用户不存在！'))));
        }
        if (!isset($res['password']) || strcmp($res['password'], encryptPwd(trim($password))) !== 0) {
            redirect(U('Public/index', array('error' => base64_encode('密码错误！'))));
        }

        session(C('SAVE_USER_KEY'), $res);
        // 操作日志
        $this->addOperationLogs("操作：管理员登录,管理员id:{$res['id']},管理员名称:{$res['username']}", $res['id'], $res['username']);
        // 成功跳转
        redirect(U('Index/index'));
    }

    /**
     * 注销
     */
    public function logout() {
        $user = session(C('SAVE_USER_KEY'));
        if ($user && isset($user['id'])) {
            $this->addOperationLogs("操作：管理员退出登录,管理员id:{$user['id']},管理员名称:{$user['username']}", $user['id'], $user['username']);
        }
        session_destroy();
        $this->redirect('Public/index');
    }
    
//    public function get_token(){
//        $access_token = array(
//            'plat'=>'admin',
//            'operation_id'=>'925861',
//        );
//        $token_str = @json_encode($access_token);
//        $token = $this->_createToken($token_str);
//        
//        exit($token);
//    }
//    
//    public function get_operation_info_by_token(){
//        $access_token = I('get.access_token','','trim');
//        $info = $this->_decryptToken($access_token);
//        var_dump($info);
//    }
    
 

}


