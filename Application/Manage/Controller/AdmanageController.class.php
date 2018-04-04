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
 * 广告控制器
 * Class AdmanageController
 * @package Manage\Controller
 */
class AdmanageController extends CommonController {

    private  $admanageType = array(
        array('val'=>'pc','name'=>'电脑首页轮播图'),
        array('val'=>'app','name'=>'APP广告图片'),
        array('val'=>'timelimit','name'=>'APP秒杀图片'),
        array('val'=>'limited','name'=>'APP限量图片'),
        array('val'=>'special_selling','name'=>'APP特卖图片'),
        array('val'=>'toutiao','name'=>'青团头条'),
    );

	/**
	 *	获取广告列表
	 */
	public function index(){
        $where['cityid']=$this->_getCityId();
        $type = I('get.type','','strval');
        if($type){
            $where['type'] = $type;
            $this->assign('type',$type);
        }
		$Model=D('Admanage');
	    $count = $Model->getTotal($where);
    	if($count === false){
    		//TODO 错误日志
            $this->_writeDBErrorLog($count, $Model);
    	}
		$page  = $this->pages($count, $this->reqnum);
		$limit = $page->firstRow . ',' . $page->listRows;
  		$this ->assign('pages', $page->show());
  		$this ->assign('count', $count);
  		$data = $Model->getList($where,'sort_order DESC',$limit);
      	if($data===false){
      		//TODO 错误日志
            $this->_writeDBErrorLog($data, $Model);
      	}
        $this->assign('admanageType',$this->admanageType);
      	$this->assign('data',$data);
      	$this->display();
	}


    /**
     *  新建广告
     */
    public function add(){
        if(IS_POST) {
            $Model = D('Admanage');
            $_POST['cityid']=$this->_getCityId();
            $type = I('post.type','','strval');
            if(!$type){
                $this->error('请选择广告类型后再提交');
            }

            if($type == 'pc' || $type == 'app'){
                if(!$_POST['end_time']){
                    $this->error('广告的结束时间必须填写');
                }
            }else{
                $where = array('cityid'=>$this->_getCityId(),'type'=>$type);
                $count = $Model->getTotal($where);
                if($count > 0 && $type!='toutiao'){
                    $this->error('您已经存在该类型图片，直接编辑或删除后在进行添加');
                }
            }
            $rs    = $Model->insert();
            if($rs === false) {
                //TODO 记录错误日志
                $this->_writeDBErrorLog($rs, $Model);
                //$this->error('新增失败！');
                $error=$Model->getErrorInfo();
                $this->error($error['info']);
            } else {
                $this->success('新增成功！', U('Admanage/index'));
            }
        } else {
            $this->assign('admanageType',$this->admanageType);
            $this->display();
        }
    }

    /**
     * 广告更新
     */
    public function edit() {
        $Model = D('Admanage');
        if(IS_POST) {
            $rs = $Model->update();
            if($rs === false) {
                //TODO 记录日志
                $this->_writeDBErrorLog($rs, $Model);
                $this->error('更新失败！');
            } else {
                $this->success('更新成功！', U('Admanage/index'));
            }
        } else {
            $this->_checkblank('id');
            $id = I('get.id', 0, 'intval');
            $info = $Model->info($id);
            if(empty($info)){
                $this->error('该广告不存在！');
            }
            $image=C('IMG_PREFIX').$info['picarr'];
            $this->assign('admanageType',$this->admanageType);
            $this->assign('image',$image);
            $this->assign('team',$info);
            $this->display();
        }
    }

    /**
     * 删除操作
     */
    public function delete() {
        $this->_checkblank('id');
        $id      = I('get.id', 0, 'intval');
        $Model = D('Admanage');
        $rs      = $Model->delete($id);
        if(!$rs) {
            //记录错误日志
            $this->_writeDBErrorLog($rs, $Model);
            $this->error('删除失败');
        } else {
            $this->success('删除成功');
        }
    }


}