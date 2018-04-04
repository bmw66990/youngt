<?php
namespace Admin\Controller;



class LoginController extends CommonController{    

 public function index(){
        $username = I('post.username','0','trim');
        $password = I('post.password','0','trim');
        $res=M('fanli_partner')->where(array('username'=>$usernaDme,'password'=>$password))->find();
        if(!empty($res)){
            session('username',$res['username']);
            session('uid',$res['id']);
            $this->redirect('Index/index');
        }else{
            $this->error('账号或者密码有误');
        }

        $this->display();
    }

    
}