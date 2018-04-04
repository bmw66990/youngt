<?php
namespace Admin\Controller;



class CollPageController extends PublicController{    
    public function index(){
    	$this->yz_login();
    	 	//获取当前用户的ID
   		$mid = session('uid');
   		$this->assign('id',$mid);
         $this->display();
    }


}
