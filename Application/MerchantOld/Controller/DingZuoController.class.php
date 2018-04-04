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
        $field = array('id', 'username', 'mobile', 'num', 'create_time', 'state', 'user_state', 'status', 'remarks');
        $list = $dzOrder->where($where)->field($field)
                ->order('id desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        $data = array(
            'count' => $count,
            'page' => $Page->show(),
            'dz_order' => $list,
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 处理订座订单
     */
    public function dealDZOrder() {
        $id = I('post.id',0,'intval');
        if(!trim($id)){
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
        $this->ajaxReturn(array('code' => 0));
    }

}
