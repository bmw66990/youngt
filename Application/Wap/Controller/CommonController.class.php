<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-06-18
 * Time: 09:33
 */
namespace Wap\Controller;

use Common\Controller\CommonBusinessController;

class CommonController extends CommonBusinessController {

    /**
     * 是否验证用户登录
     * @var boolf
     */
    protected $checkUser = false;

    /**
     * 是否验证选择城市
     * @var bool
     */
    protected $checkCity = true;

    /**
     * 构造方法
     */
    public function __construct() {
        parent:: __construct();
        
        // 过滤请求数据 防止xss攻击
        $_GET && safeFilter($_GET);
        
         //检测用户
        if($this->checkUser === true){
            $this->_checkUser();
        }

        //检测用户是否选择城市
        if($this->checkCity === true){
            $this->_checkCity();
        }
        $city = $this->_getCityInfo();
        $this->assign('cityInfo', $city);
        $this->_getTitle();
        
        // 错误信息统一收集
        $error = I('get.error', '', 'strval');
        if(trim($error)){
            $this->assign('error', base64_decode(str_replace(array('%2b',' '),'+',urldecode($error))));
        }
    }

    /**
     * 检测城市
     */
    protected function _checkCity(){
        if($this->_getCityId() === 0){
            redirect(U(C('CITY_TPL')));
        }
    }

    /**
     * 检测用户
     */
    protected function _checkUser(){
        if($this->_getUserId() === 0){
            redirect(U(C('LOGIN_TPL')));
        }
    }

    /**
     * 获取登录用户id
     */
    protected function _getUserId(){
        $user_id = cookie(C('SAVE_USER_KEY'));
        return ternary($user_id,0);
    }

    /**
     * 获取登录用户所在城市
     */
    protected function _getCityId(){
        $city_id = cookie(C('SAVE_CITY_KEY'));
        $_city_id = I('get.city_id','','trim');
        $_city_name_py = I('get.city_name_py','','trim');
        if($_city_id){
            $city_id = $_city_id;
            cookie(C('SAVE_CITY_KEY'), $city_id, 30 * 86400, '/');
        }else if($_city_name_py){
            $res = M('category')->where(array('zone'=>'city','ename'=>$_city_name_py,'display'=>'Y'))->field('id')->find();
            if(isset($res['id']) && trim($res['id'])){
                $city_id = $res['id'];
                cookie(C('SAVE_CITY_KEY'), $city_id, 30 * 86400, '/');
            }
        }
        return ternary($city_id,0);
    }

    /**
     * 当前城市详细信息
     * @return array
     */
    protected function _getCityInfo(){
        if($this->_getCityId() === 0){
            return array();
        }
        return D('Category')->info($this->_getCityId());
    }

    /**
     * 登录用户详细信息
     * @return array
     */
    protected function _getUserInfo(){
        if($this->_getUserId() === 0){
            return array();
        }
        return D('User')->info($this->_getUserId());
    }

    /**
     * 获取网页title
     * @param $str
     * @return string
     */
    protected function _getTitle($str) {
        $var = '青团网';
        if($str) {
            $var .= '-' . trim($str);
        }
        $this->assign('title', $var);
    }

    /**
     * 设置openid
     */
    protected function _setOpenId(){
        $openid = I('get.openid','','trim');
        if($openid){
            session('wx_share_openid',$openid);
        }
    }

    /**
     * 获取openid
     * @return mixed
     */
    protected function _getOpenId(){
        $openid = session('wx_share_openid');
        if($openid){
            session('wx_share_openid',null);
        }else{
            return '';
        }
    }
}