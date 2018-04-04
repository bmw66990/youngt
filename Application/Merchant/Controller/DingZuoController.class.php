<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class DingZuoController extends CommonController {

    public function index() {

        // 查询条件
        $where = array(
            'partner_id' => $this->partner_id,
        );

        // 查询结账纪录
        $dzOrder = M('dz_order');
        $count = $dzOrder->where($where)->count();
        $Page = $this->pages($count, $this->reqnum);
        $field = array('id' , 'username', 'mobile', 'num', 'create_time', 'state', 'user_state', 'status', 'remarks');
        $list = $dzOrder->where($where)->field($field)
                ->order('id desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        
        // 订座信息
        $partner_dingzuo_info = M('dingzuo')->where(array('partner_id'=>$this->partner_id))->field('id,begin_time,end_time,is_hall_status,is_box_status')->find();
        
        $data = array(
            'count' => $count,
            'page' => $Page->show(),
            'list' => $list,
            'partner_dingzuo_info' => $partner_dingzuo_info,
        );

        $this->assign($data);
        $this->display('Reservation/index');
    }

    /**
     * 处理订座订单
     */
    public function dealDZOrder() {
        $id = I('post.id','','trim');
        if(!$id){
            $id = I('get.id','','trim');
        }
        if(!$id){
            $this->ajaxReturn(array('code' => -1, 'error' => '订单id不能为空！'));
        }
        
        // 修改数据
        $data = array(
            'user_state'=>'Y',
            'status'=>'Y'
        );
        
        $dzOrder = M('dz_order');
        $res = $dzOrder->where(array('id'=>$id))->save($data);
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '处理失败！'));
        }
        $this->ajaxReturn(array('code' => 0,'success'=>'处理成功！'));
    }
    
    /**
     *   商户修改订座相关信息
     */
    public function update_info(){
         $dingzuo_id = I('post.dingzuo_id','','trim');
         $is_hall_status = I('post.is_hall_status','2','trim');
         $is_box_status = I('post.is_box_status','2','trim');
         $begin_time = I('post.begin_time','','trim');
         $end_time = I('post.end_time','','trim');
         
         if(!$dingzuo_id){
             $this->ajaxReturn(array('code' => -1, 'error' => '订座信息id不能为空！'));
         }
         
         if(!$begin_time){
             $this->ajaxReturn(array('code' => -1, 'error' => '订座开始时间不能为空！'));
         }
         
         if(!$end_time){
             $this->ajaxReturn(array('code' => -1, 'error' => '订座结束时间不能为空！'));
         }
         
         if(!$is_hall_status){
             $is_hall_status = 2;
         }
         
         if(!$is_box_status){
             $is_box_status = 2;
         }
         
         $where = array(
             'id'=>$dingzuo_id,
             'partner_id'=>$this->partner_id,
         );
         $partner_dingzuo_count = M('dingzuo')->where($where)->count();
         if(!$partner_dingzuo_count || $partner_dingzuo_count<=0){
             $this->ajaxReturn(array('code' => -1, 'error' => '订座信息不存在！'));
         }
         
         $update_date = array(
             'is_hall_status'=>$is_hall_status,
             'is_box_status'=>$is_box_status,
             'begin_time'=>$begin_time,
             'end_time'=>$end_time,
         );
         
         $res = M('dingzuo')->where($where)->save($update_date);
         if($res===false){
             $this->ajaxReturn(array('code' => -1, 'error' => '订座信息修改失败！'));
         }
         
         $this->ajaxReturn(array('code' => 0, 'success' => '订座信息修改成功！'));
         
    }

}
