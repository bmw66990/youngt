<?php
/**
 * Created by PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 2015-03-26
 * Time: 16:55
 */
namespace Manage\Controller;
use Manage\Controller\CommonController;
/**
 * 积分控制器
 * Class CouponController
 * @package Manage\Controller
 */
class CreditController extends CommonController {
	/**
	 *	获取积分列表
	 */
	public function index(){
		//构造 where 条件
		$paramArray=array(
			array('user_id','','','c'),
	        array('action','','','c'),
        );
        $where=$this->createSearchWhere($paramArray);
        $displayWhere=$this->getSearchParam($paramArray);
        $where['u.city_id']=$this->_getCityId();
		$Model=D('Credit');
	    $count = $Model->getCreditCount($where);
    	if($count === false){
    		//TODO 错误日志
            $this->_writeDBErrorLog($count, $Model);
    	}
		$page  = $this->pages($count, $this->reqnum);
		$limit = $page->firstRow . ',' . $page->listRows;
  		$this ->assign('pages', $page->show());
  		$field="c.id,c.action,c.user_id,c.score,u.username,u.email,u.mobile";
  		$data = $Model->getCredits($where,$limit,'c.id desc',$field);
      	if($data===false){
      		//TODO 错误日志
            $this->_writeDBErrorLog($data, $Model);
      	}
        // 筛选条件使用
        $option_action = array(
            'buy' => '购买商品',
            'login' => '每日登录',
            'pay' => '支付返积',
            'exchange' => '兑换商品',
            'register' => '注册用户',
            'invite' => '邀请好友',
            'refund' => '项目退款',
        );
        $this->assign('displayWhere',$displayWhere);
        $this->assign('option',$option_action);
      	$this->assign('data',$data);
      	$this->display();
	}


}