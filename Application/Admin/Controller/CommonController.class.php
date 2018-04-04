<?php

/**
 * Created by Sublime.
 * User: ma
 * Date: 2016-04-20
 * Time: 09:03
 */

namespace Admin\Controller;

use Common\Controller\CommonBusinessController;

class CommonController extends CommonBusinessController {

    protected $user = null;
    protected $uid = null;
    protected $reqnum = 20;
    private $auth = null;
    protected $auth_config = null;
    protected $service_type = array(
        'alipay|aliwap|aliapp|pcalipay|wapalipay' => '支付宝',
        'tenpay|tenwap|tenapp|pctenpay|waptenpay' => '财付通',
        'wechatpay|wxpay|wapwechatpay|pcwxpaycode' => '微信支付',
        'chinabank|umspay|wapumspay' => '全民付',
        'unionpay|wapunionpay' => '银联',
        'lianlianpay' => '连连',
        'credit' => '余额支付',
    );
    
    // 构造方法
    public function __construct() {
        parent::__construct();
        header('Content-type: text/html; charset=utf-8');
       
        $this->auth = new \Common\Org\Auth();
        $this->auth_config = C('AUTH_CONFIG');
        
        // 登录权限认证
        $this->__auth();
       
        // 同步提交错误统一处理
        $this->__initMessage();
        
        // 权限点自动注册
        $this->_register($this);

        $this->_isDaySign();
        
    }

    private function __initMessage() {
        $uid = md5($this->_getUserId());
        $error = cookie('error_' . $uid);
        $success = cookie('success_' . $uid);
        if (trim($error)) {
            $this->assign('error', base64_decode(str_replace(array('%2b', ' '), '+', urldecode($error))));
            cookie('error_' . $uid, null, -1);
        }
        if (trim($success)) {
            $this->assign('success', base64_decode(str_replace(array('%2b', ' '), '+', urldecode($success))));
            cookie('success_' . $uid, null, -1);
        }
    }

    private function __auth() {
        
        
        $module_name = strtolower(MODULE_NAME);
        $controller_name = strtolower(CONTROLLER_NAME);
        $action_name = strtolower(ACTION_NAME);
        $uri = "$module_name/$controller_name/$action_name";
        // 无权限 无登录r认证的url
        if(isset($this->auth_config['NO_AUTH_NO_LOGIN_URI'][$uri])){
            return true;
        }
        
        $this->user = session(C('SAVE_USER_KEY'));        
        if (is_null($this->user)) {
            //跳转到认证网关
            redirect(U(C('USER_AUTH_GATEWAY')));
        }
        $this->assign('user_info', $this->user);
            
        // 权限判断
        $res = $this->auth->auth_check_access($this->user['id'], $this->auth_config);
        if (!$res) {
            if (IS_AJAX) {
                $this->ajaxReturn(array('error' => '无权限该操作', 'info' => '无权限该操作', 'code' => -1, 'status' => -1));
            }
            $this->redirect_message(U('Index/index'), array('error' => base64_encode('无权限访问！')));
        }
    }

    protected function _register($class_name) {
        if (isset($this->auth_config['OPEN_AUTH_RULE_REGISTER']) && $this->auth_config['OPEN_AUTH_RULE_REGISTER']) {
            $this->auth->register($class_name);
        }
    }

    /**
     * @return bool
     */
    public function isLogin() {
        return is_null($this->user);
    }

    /**
     * 获取登录用户id
     */
    protected function _getUserId() {
        if (empty($this->uid)) {
            $user = session(C('SAVE_USER_KEY'));
            $this->uid = ternary($user['id'], '');
        }
        return $this->uid;
    }

    protected function redirect_message($url, $data = array()) {
        $uid = md5($this->_getUserId());
        $ex_time = 3600 * 24;
        if (isset($data['error']) && trim($data['error'])) {
            cookie('error_' . $uid, $data['error'], $ex_time);
        }
        if (isset($data['success']) && trim($data['success'])) {
            cookie('success_' . $uid, $data['success'], $ex_time);
        }
        redirect($url);
    }

    /**
     * 检测用户是否签到
     */
    protected function _isDaySign() {
        $where = array('uname' => $this->user['realname'], 'day' => date('Y-m-d'), 'time>0');
        $count = M('qd')->where($where)->count();
        $this->assign('isDaySign', $count ? 1 : 0);
    }

    /**
     * 分页设置
     * @param $totalRows
     * @param $listRows
     * @param array $map
     * @param int $rollPage
     * @return string
     */
    protected function pages($totalRows, $listRows, $map = array(), $rollPage = 0) {
        $page             = parent::pages($totalRows, $listRows, $map = array(), $rollPage = 0);
		
        $page->lastSuffix = false;
        $page->rollPage   = 7;
        $page->setConfig(
            'theme', '<ul class=pagination><li><span style="float:left;">当前页' . $listRows
            . '条数据 总%TOTAL_ROW% %HEADER%</span> %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%</li></ul> %FORM_PAGE%'
        );
        return $page;
    }
    
    /*
     * 获取招商人员信息
     * is_db = true  返回是否为招商总监或管理员 是返回true
     */
    protected function getCanvBusUser($is_cb=true){
        
        if($is_cb && isset($this->auth_config['SUPER_ADMIN_ID'][$this->uid])){
            return true;
        }
        
        $cb_auth_group_config = ternary($this->auth_config['CB_AUTH_GROUP_ID'], array());
        if(!isset($cb_auth_group_config['MANAGER']) || !isset($cb_auth_group_config['EMPLOYEE'])){
            return false;
        }
    
        $auth_groip_where = array('module_name'=>'admin','group_id'=>array('in',array_values($cb_auth_group_config)));
        $uids_res = M('auth_group_access') ->where($auth_groip_where)->field('group_id,uid')->select();
        $uids = array();
        if($uids_res){
            foreach($uids_res as $v){
                $uids[] = $v['uid'];
            }
        }
        if(!$uids){
            return false;
        }
        $user_res = M('user')->where(array('id'=>array('in',$uids)))->field('id,username,realname')->select();
        $user_info = array();
        foreach($user_res as $v){
            $user_info[$v['id']] = $v;
        }
        
        $cb_user_info = array();
        foreach($uids_res as $v){
            if(isset($v['group_id']) && $v['group_id'] == $cb_auth_group_config['MANAGER']){
              $cb_user_info['manager'][$v['uid']] = ternary($user_info[$v['uid']], array()); 
            }
            if(isset($v['group_id']) && $v['group_id'] == $cb_auth_group_config['EMPLOYEE']){
              $cb_user_info['employee'][$v['uid']] = ternary($user_info[$v['uid']], array()); 
            }
        }
        
        if($is_cb){
            if(isset( $cb_user_info['manager'][$this->uid])){
                return true;
            }
            return false;
        }
        
        return $cb_user_info;
    }
    
    /**
     * 获取操作企业号微信对象
     * @staticvar null $tp_wechat
     * @return \Common\Org\TPWechat
     */
    protected function getTPwechat(){
        static $tp_wechat = null;
        if(!$tp_wechat){
            $tp_wechat = new \Common\Org\TPWechat();
        }
        return $tp_wechat;
    }
    
    /**
     * 给某个组人员企业号发送消息
     * @param type $content
     * @param type $workbench_group_id
     */
    protected function sendMessage($content='',$workbench_group_id=false){
        
        if(!trim($content) || !$workbench_group_id){
            return false;
        }
        $user_ids = array();
        $user_ids_res = M('workbench_group_user_relation')->where(array('workbench_group_id'=>$workbench_group_id))->field('user_id')->select();
        foreach($user_ids_res as $v){
            $user_ids[$v['user_id']] = $v['user_id'];
        }
        if(!$user_ids){
            return false;
        }
        $where = array(
            'id'=>array('in', array_keys($user_ids)),
            'manager'=>'Y',
        );
        $field = array(
            'realname'=>'name',
            'mobile'=>'mobile',
        );
        $user_info = M('user')->where($where)->field($field)->select();
        
        return $this->getTPwechat()->sendMessageToText($content,$user_info);
    }

    /**
     * 字节转换
     * @param $size
     * @param string $unit
     * @param int $decimals
     *
     * @return string
     */
    protected function _byteFormat($size,$unit='B',$decimals = 2){
        $units = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8);

        $value = 0;
        if ($size > 0) {
            if (!array_key_exists($unit, $units)) {
                $pow = floor(log($size)/log(1024));
                $unit = array_search($pow, $units);
            }
            $value = ($size/pow(1024,floor($units[$unit])));
        }

        if (!is_numeric($decimals) || $decimals < 0) {
            $decimals = 2;
        }
        return sprintf('%.' . $decimals . 'f '.$unit, $value);
    }


}
