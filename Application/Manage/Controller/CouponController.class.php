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
 * 券号控制器
 * Class CouponController
 * @package Manage\Controller
 */
class CouponController extends CommonController {
	/**
	 *	获取券号列表
	 */
	public function index(){
		//构造 where 条件
		$paramArray=array(
			array('id','','','c'),
            array('order_id','','','c'),
	        array('team_id','','','c'),
	        array('user_id','','','c'),
	        array('consume','','','c'),
        );
        $where=$this->createSearchWhere($paramArray);
        if(isset($where['c.consume']) && $where['c.consume'] == 'N'){
            $where['c.expire_time']=array('egt',time());
        }
        $displayWhere=$this->getSearchParam($paramArray);
        $where['t.city_id']=$this->_getCityId();
        $this->_getData($where);
        $this->assign('displayWhere',$displayWhere);
      	$this->display();

	}

    /**
     * 获取已过期青团券
     */
    public function expireCoupon(){
        //构造 where 条件
        $paramArray=array(
            array('id','','','c'),
            array('order_id','','','c'),
            array('team_id','','','c'),
            array('user_id','','','c'),
        );
        $where=$this->createSearchWhere($paramArray);
        $displayWhere=$this->getSearchParam($paramArray);
        $where['t.city_id']=$this->_getCityId();
        $where['c.expire_time']=array('lt',time());
        $where['c.consume'] = 'N';
        $this->_getData($where);
        $this->assign('displayWhere',$displayWhere);
        $this->display();
    }

    /**
     * 获取列表
     */
    protected function _getData($where){
        $Model=D('Coupon');
        $count = $Model->getCouponCount($where);
        if($count === false){
            //TODO 错误日志
            $this->_writeDBErrorLog($count, $Model);
        }
        $page  = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this ->assign('pages', $page->show());
        $field="c.id,c.team_id,c.user_id,c.expire_time,t.product,t.team_price,c.consume,c.consume_time,c.order_id";
        $data = $Model->getCoupons($where,$limit,'c.id DESC',$field);
        if($data===false){
            //TODO 错误日志
            $this->_writeDBErrorLog($data, $Model);
        }
        $this->assign('data',$data);
    }



}