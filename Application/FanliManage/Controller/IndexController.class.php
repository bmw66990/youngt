<?php
namespace FanliManage\Controller;



class IndexController extends PublicController{    

//获取session
//判断内页用户session是否为空，如果是空的跳到登录页面重新登录。
  
 public function index(){
      $this->yz_login();
       $username=session('username');
        $mid=session('uid');
       //改用户订单总数量
        $res=M('fanli_order')->where(array('mid'=>$mid))->count();
        $order_prices=M('fanli_order')->where(array('mid'=>$mid))->sum('prices');

        

        $this->assign('order_count',$res);
        $this->assign('order_prices',$order_prices);
        $this->assign('username', $username);
        $this->display('Index/index');
    }
    
 
    
}