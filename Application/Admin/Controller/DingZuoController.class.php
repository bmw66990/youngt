<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/6/12
 * Time: 15:18
 */

namespace Admin\Controller;

/**
 * 订座控制器
 * Class DingZuoController
 * @package Admin\Controller
 */
class DingZuoController extends CommonController {

    /**
     * 订座管理
     */
    public function index(){
        $Model = D('Dingzuo');
        $paramArray = array(
            array('id'),
            array('city_id',0),
            array('partner_id'),
            array('title','','like'),
            array('class_id',0)
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $count = $Model->getTotal($where);
        $page = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $data = $Model->getList($where,'id desc',$limit);
        $this->assign('dingZuo_class',$this->_getCategoryList('class'));
        $this->assign('all_city',$this->_getCategoryList('city'));
        $this->assign('displayWhere',$displayWhere);
        $this->assign('data',$data);
        $this->display();
    }

    /**
     * 添加订座前置操作
     */
    public function _before_add(){
        //默认经纬度
        $point = '39.915,116.404';
        $partner_data = D('Partner')->getList(array('display'=>'Y'),'head desc, id desc','','id,username');
        $this->assign('partner_data',$partner_data);
        $this->assign('dingZuo_class',$this->_getCategoryList('class'));
        $this->assign('all_city',$this->_getCategoryList('city'));
        $this->assign('point',$point);
    }

    /**
     * 添加订座模板展示
     */
    public function add(){
        $this->display();
    }

    public function _before_doAdd(){
        $partner_id = I('post.partner_id',0,'intval');
        $point = I('post.longlat','','trim');
        if(!$partner_id){
             $this->redirect_message(U('DingZuo/add'),  array('error' => base64_encode('请选择商家！')));
        }
        if($point){
            $pointArr = explode(',',$point);
            $_POST['long'] = $pointArr[1];
            $_POST['lat']  = $pointArr[0];
            unset($_POST['longlat']);
        }else{
            $this->redirect_message(U('DingZuo/add'),  array('error' => base64_encode('请确定商家坐标！')));
        }
        $partner = M('partner')->getFieldById($partner_id,'station_id,zone_id');
        $_POST['station_id'] = $partner[$partner_id]['station_id'];
        $_POST['zone_id'] = $partner[$partner_id]['zone_id'];
        $_POST['week'] = implode(',',I('post.week',''));
    }

    /**
     * 添加订座处理
     */
    public function doAdd(){
        if(IS_POST){
            $Model = D('Dingzuo');
            $res = $Model->insert();
            if($res){
                $this->addOperationLogs("操作：新建订座,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},订座id:{$res}");
                $this->redirect_message(U('DingZuo/index'),  array('success' => base64_encode('新建成功！')));
            }else{
                  $this->redirect_message(U('DingZuo/add'),  array('error' => base64_encode($Model->getError())));
            }
        }else{
             $this->redirect_message(U('DingZuo/add'),  array('error' => base64_encode('非法请求!')));
        }
    }

    /**
     * 编辑订座前置操作
     */
    public function _before_edit(){
        $this->assign('dingZuo_class',$this->_getCategoryList('class'));
    }

    /**
     * 编辑订座模板
     */
    public function edit(){
        $id = I('get.id',0,'intval');
        if($id){
            $dz_info = D('Dingzuo')->info($id);
            $this->assign('dz_info',$dz_info);
            $this->assign('image',$dz_info['image'] ? getImagePath($dz_info['image']) : '');
            $this->assign('point',$dz_info['long'] && $dz_info['lat'] ? $dz_info['lat'].','.$dz_info['long'] : '39.915,116.404');
        }else{
             $this->redirect_message(U('DingZuo/index'),  array('error' => base64_encode('非法请求!')));
        }
        $this->display();
    }

    /**
     * 编辑订座前置操作
     */
    public function _before_doEdit(){
        $id = I('post.id',0,'intval');
        $point = I('post.longlat','','trim');
        if(!$id){
             $this->redirect_message(U('DingZuo/index'),  array('error' => base64_encode('非法请求!')));
        }
        if($point){
            $pointArr = explode(',',$point);
            $_POST['long'] = $pointArr[1];
            $_POST['lat']  = $pointArr[0];
            unset($_POST['longlat']);
        }else{
             $this->redirect_message(U('DingZuo/edit',array('id'=>$id)),  array('error' => base64_encode('请确定商家坐标!')));
        }
        $_POST['week'] = implode(',',I('post.week',''));
    }


    /**
     * 编辑订座逻辑处理
     */
    public function doEdit(){
        if(IS_POST){
            $Model = D('Dingzuo');
            $id = I('post.id',0,'intval');
            $res = $Model->update();
            if($res){
                $this->addOperationLogs("操作：修改订座,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},订座id:{$id}");
                 $this->redirect_message(U('DingZuo/index'),  array('success' => base64_encode('修改成功!')));
            }else{
                $this->redirect_message(U('DingZuo/edit',array('id'=>$id)),  array('error' => base64_encode($Model->getError())));
            }
        }else{
            $this->redirect_message(U('DingZuo/index'),  array('error' => base64_encode('非法请求!')));
        }
    }

    /**
     * 删除订座
     */
    public function delete(){
        $id = I('get.id',0,'intval');
        if($id){
            $Model = D('Dingzuo');
            $count = $Model->getTotal(array('id'=>$id));
            if($count){
                $res = $Model->delete($id);
                if($res){
                    $this->addOperationLogs("操作：删除订座,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},订座id:{$id}");
                    $param = array('success'=> base64_encode('删除成功'));
                }else{
                    $param = array('error'=> base64_encode($Model->getError()));
                }
            }else{
                $param = array('error'=> base64_encode('删除信息不存在！'));
            }
        }else{
            $param = array('error'=> base64_encode('非法操作！'));
        }
        $this->redirect_message(U('DingZuo/index'), $param);
    }

    public function order() {
        $city_id = I('get.city_id', 0, 'intval');
        $where = array();
        if ($city_id) {
            $where['p.city_id'] = $city_id;
            $this->assign('city_id', $city_id);
        }
        $Model = D('DzOrder');
        $count = $Model->getCount($where);
        $page = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $data = $Model->getDzOrderList($where, '', $limit);
        $this->_writeDBErrorLog($count, $Model);
        $city = $this->_getCategoryList('city');
        $this->assign('all_city', $city);
        $this->assign('data', $data);
        $this->display();
    }

}
