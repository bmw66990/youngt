<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 优惠买单
 * Class IndexController
 * @package Manage\Controller
 */
class DiscountOrderController extends CommonController {
    public function index() {
        $field = array('user_id','origin','(origin - round(origin * aratio, 2)) as amoney','create_time');
        $payed_id = I('get.payed_id', '', 'trim');
        $where['state'] = 'pay';
        $where['payed_id']    = intval($payed_id);
        $where['partner_id']  = $this->partner_id;
        $where['create_time'] = array('elt', time());
        $list = M('discountOrder')->field($field)->where($where)->order('create_time DESC')->select();
        foreach($list as $k => $v) {
            $v['product'] = '实时消费';
            $v['username'] = M('user')->where(array('id'=>$v['user_id']))->getField('username');
            $list[$k] = $v;
        }
        $count = M('discountOrder')->where($where)->count();
        $Page = $this->pages($count, $this->reqnum);
        $data = array(
            'count' => $count,
            'page' => $Page->show(),
            'list' => $list,
        );
        $this->assign($data);
        $this->display();
    }

    // 整理下载数据
    public function download() {

        // 接收参数
        $payed_id = I('get.payed_id', '', 'trim');

        // 查询条件
        $where['state'] = 'pay';
        $where['payed_id']    = intval($payed_id);
        $where['partner_id']  = $this->partner_id;

        $field = array('user_id','origin','(origin - round(origin * aratio, 2)) as amoney','create_time');

        $list = M('discountOrder')->field($field)->where($where)->select();

        if ($list) {
            foreach($list as $k => $v) {
                $v['product'] = '实时消费';
                $v['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                $v['username'] = M('user')->where(array('id'=>$v['user_id']))->getField('username');
                $v['username'] = substr($v['username'],0,4).'****'.substr($v['username'],-4,4);
                unset($v['user_id']);
                $list[$k] = $v;
            }
        }

        // 头信息
        $head = array(
            'product'  => '项目名称',
            'username' => '消费者用户名',
            'origin'   => '成交价',
            'amoney'   => '结算价',
            'create_time' => '消费时间'
        );

        // 文件名称
        $fileName = 'discount_' . $this->partner_id . date('YmdHis');
        partner_down_xls($list, $head, $fileName);
    }
    
}