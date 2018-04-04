<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-06-06
 * Time: 09:03
 */

namespace Common_operation\Controller;

use Common\Controller\CommonBusinessController;

class CommonController extends CommonBusinessController {

    protected $operation_info = null;
    protected $operation_id = null;
    protected $source_url = null;
    protected $plat = null;
    protected $reqnum = 20;
    protected $auth_config = null;
    // 构造方法
    public function __construct() {
        parent::__construct();
        header('Content-type: text/html; charset=utf-8');
        $this->auth_config = C('SUPER_ADMIN_ID');
        // 认证
        $res = $this->__auth();
        if(isset($res['error']) && trim($res['error'])){
            die("<span style='color:red'>{$res['error']}</span>");
        }
        $this->assign('user_info', $this->operation_info);

        // 同步提交错误统一处理
        $this->__initMessage();
    }

    private function __initMessage() {
        $uid = md5($this->operation_id);
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
        $access_token = I('get.access_token', '', 'trim');
        $info = session(C('SAVE_USER_KEY'));
        if ($info && !$access_token) {
            $this->operation_id = ternary($info['id'], '');
            $info['plat'] = $this->plat = strtolower(ternary($info['plat'], ''));
            $this->operation_info = $info;
            $this->source_url = ternary($info['source_url'], '');
            return true;
        }

        if (!$access_token) {
            return array('error'=>'缺少参数access_token');
        }

        $info = $this->_decryptToken($access_token);
        if (!$info || !isset($info['plat']) || !isset($info['operation_id'])) {
            return array('error'=>'权限认证失败!');
        }
        $plat = strtolower($info['plat']);
        switch ($plat) {

            // 管理后台
            case 'admin':
                $where = array(
                    'id' => $info['operation_id'],
                    'manager' => 'Y',
                );
                $admin_info = M('User')->where($where)->field('id,username,city_id')->find();
                if (!$admin_info) {
                    return array('error'=>'身份与登录的平台不符!');
                }
                $admin_info['rz']='N';
                if($this->auth_config[$admin_info['id']]) {
                    $admin_info['rz']='Y';
                }
                $admin_info['plat_name']='青团后台管理员';
                $info = array_merge($info, $admin_info);
                break;
            // 代理后台
            case 'manage':
                $where = array(
                    'id' => $info['operation_id'],
                    'manager' => 'P',
                );
                $tid = I('get.id', '', 'trim');
                if($tid){
                    $team=M('team')->where(array('id'=>$tid))->field('city_id')->find();
                    if (!$team) {
                        return array('error'=>'团单不存在!');
                    }
                }

                $manage_info = M('User')->where($where)->field('id,username,city_id')->find();
                if (!$manage_info) {
                    return array('error'=>'身份与登录的平台不符!');
                }
                if ($tid && $manage_info['city_id']!=$team['city_id']) {
                    return array('error'=>'不是本站团单!');
                }
                $manage_info['city_name'] = M('category')->where(array('id'=>$manage_info['city_id']))->getField('name');
                $manage_info['rz']='N';
                if($this->auth_config[$manage_info['id']]) {
                    $manage_info['rz']='Y';
                }
                $manage_info['plat_name']="{$manage_info['city_name']}站-代理";
                $info = array_merge($info, $manage_info);
                break;
            // 商家后台
            case 'merchant':
                $where = array(
                    'id' => $info['operation_id'],
                );
                $merchant_info = M('partner')->where($where)->field('id,username,city_id')->find();
                if (!$merchant_info) {
                   return array('error'=>'身份与登录的平台不符!');
                }
                $merchant_info['city_name'] = M('category')->where(array('id'=>$merchant_info['city_id']))->getField('name');
                $merchant_info['plat_name']="{$manage_info['city_name']}站-商家";
                $info = array_merge($info, $merchant_info);
                break;
            default:
                return array('error'=>'非法访问!');
                break;
        }
        $this->operation_id = ternary($info['id'], '');
        $this->operation_info = $info;
        $info['plat'] = $this->plat = strtolower(ternary($info['plat'], ''));
        $this->operation_info = $info;

        $this->source_url = ternary($info['source_url'], '');
        session(C('SAVE_USER_KEY'), $info);
        return true;
    }
    
    protected function _getUserId(){
        return $this->operation_id;
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
        die();
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
        $page = parent::pages($totalRows, $listRows, $map = array(), $rollPage = 0);
        $page->lastSuffix = false;
        $page->rollPage = 7;
        $page->setConfig(
                'theme', '<ul class=pagination><li><span style="float:left;">当前页' . $listRows
                . '条数据 总%TOTAL_ROW% %HEADER%</span> %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%</li></ul>'
        );
        return $page;
    }

    /**
     * token解密
     * @param $token token
     *
     * @return string
     */
    protected function _decryptToken($token) {
        $tokenKey = C('tokenKey');

        $decrypt_str = \Think\Crypt\Driver\Xxtea::decrypt(pack("H*", $token), $tokenKey);

        list($info, $rand) = explode('|', $decrypt_str, 2);

        if ($info && is_string($info)) {
            $info = json_decode($info, true);
        }

        return $info;
    }

    /**
     * 创建token
     * @param $uid
     *
     * @return string
     */
    protected function _createToken($str) {
        $tokenKey = C('tokenKey');
        $rand = \Org\Util\String::randString(6);
        $token = bin2hex(\Think\Crypt\Driver\Xxtea::encrypt($str . '|' . $rand, $tokenKey));
        return $token;
    }
    
    /**
     *  添加编辑团单 根据 来源平台不同  字段值不可变
     * @return boolean
     */
    protected function set_post_data($is_edit=false){
        if(!isset($this->operation_info['plat']) || !trim($this->operation_info['plat'])){
            return false;
        }
        $plat = strtolower(trim($this->operation_info['plat']));
        if(!$plat){
            return false;
        }
        
        switch($plat){
             // 管理后台
            case 'admin':
               
                break;
            // 代理后台
            case 'manage':
                if(isset($this->operation_info['city_id']) && trim($this->operation_info['city_id'])){
                    $_POST['city_id'] = $this->operation_info['city_id'];
                }
                if($is_edit){
                    if(isset($_POST['ucaii_price'])){
                        unset($_POST['ucaii_price']); //不能修改供货价
                    }
                    if(isset($_POST['team_price'])){
                        unset($_POST['team_price']); //不能修改团购价
                    }
                    if(isset($_POST['lottery_price'])){
                        unset($_POST['lottery_price']); //不能修改活动价
                    }
                }

                break;
            // 商家后台
            case 'merchant':
                if(isset($this->operation_info['city_id']) && trim($this->operation_info['city_id'])){
                    $_POST['city_id'] = $this->operation_info['city_id'];
                }
                if(isset($this->operation_info['id']) && trim($this->operation_info['id'])){
                    $_POST['partner_id'] = $this->operation_info['id'];
                }
                if($is_edit){
                    if(isset($_POST['ucaii_price'])){
                        unset($_POST['ucaii_price']); //不能修改供货价
                    }
                    if(isset($_POST['team_price'])){
                        unset($_POST['team_price']); //不能修改团购价
                    }
                    if(isset($_POST['lottery_price'])){
                        unset($_POST['lottery_price']); //不能修改活动价
                    }
                }
                break;
            default:
                return array('error'=>'非法访问!');
                break;
        }
        return true;
    }

}
