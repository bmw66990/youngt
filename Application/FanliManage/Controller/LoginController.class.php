<?php
namespace FanliManage\Controller;



class LoginController extends PublicController{    
    public function index(){
         $this->display();
    }

 public function login(){

      
        $fanli_partner=M('fanli_partner');
        $code=I('post.code','','trim');
        $username = I('post.username','0','trim');
         $check = I('post.checkbox');

     //   $password = I('post.password','0','trim');
        $res=$fanli_partner->where(array('username'=>$username))->find();
       
        if(!empty($res)){
            $rew=$this->_checkVerify($code);
            if($rew){
            session('username',$res['username']);
            session('uid',$res['id']);

           if($check==1){
                 cookie('uname',$res['username'],time()+3600); // 指定cookie保存时间
            }else{
                cookie(null); // 清空当前设定前缀的所有cookie值
            }
             $data['status']=1;

            //$this->redirect('Index/index');

            }else{
                    $data['status']=0;
                    $data['info']="验证码错误！！！";
            }
        }
        else{
        // $this->error('操作失败','/Login/index',5);
                    $data['status']=0;
                    $data['info']="账号不存在！！！";
        }

        $this->ajaxReturn($data);
     
    }



     public function logout(){
        session(null);
        $this->redirect('Login/index');
    }
}
