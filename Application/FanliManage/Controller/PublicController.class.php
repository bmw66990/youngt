<?php
/**
 * Created by PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 2015/4/7
 * Time: 17:28
 */

namespace FanliManage\Controller;
use Think\Controller;

/**
 * 公共控制器
 * Class PublicController
 * @package Manage\Controller
 */
class PublicController extends Controller {
    /**
     * 代理登陆
     */
    public function login(){
        $this->display();
    }

 
   public function yz_login(){

     if(!session('?username')){
        $this->redirect('Login/index');
       exit;
   }
}

    /**
     * 获取验证码
     */
    public function verify(){
        $Verify =     new \Think\Verify();
        $Verify->codeSet = '0123456789';
        $Verify->fontSize = 30;
        $Verify->length   = 4;
        $Verify->useNoise = false;
        $Verify->entry();
    }
    

    public function loginCheckVerify(){
        $code=I('post.code','0','intval');
        if(!$code){
            $this->ajaxReturn(array('code'=>-1,'error'=>'验证码不能为空！'));
        }
        $res=$this->_checkVerify($code);
        if($res){
            $this->ajaxReturn(array('code'=>0));
        }
        $this->ajaxReturn(array('code'=>-1,'error'=>'验证码错误！'));
        
    }

    /**
     * 检测验证码
     */
    protected function _checkVerify($code, $id = ''){
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }

    
}