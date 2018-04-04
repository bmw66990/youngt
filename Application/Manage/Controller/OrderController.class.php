<?php

/**
 * Created by PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 2015-03-26
 * Time: 15:55
 */

namespace Manage\Controller;

/**
 * 订单控制器
 * Class OrderController
 * @package Manage\Controller
 */
class OrderController extends CommonController {

    /**
     * 获取订单列表
     */
    public function index() {
        $select = true;
        $paramArray = array(
            array('id', '', '', 'o'),
            array('team_id', '', '', 'o'),
            array('mobile', '', '', 'o'),
            array('state', '', '', 'o'),
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $username = I('get.username', '', 'strval');
        if ($username) {
            $user_id = M('user')->where(array('username' => $username))->getField('id');
            $select = $user_id ? true : false;
            $where['o.user_id'] = $user_id;
            $displayWhere['username'] = $username;
        }
        $where['o.city_id'] = $this->_getCityId();
        //当查询未付款订单时需要配合 rstate 状态值必须为 normal 装状态
        if ($where['state'] == 'unpay') {
            $where['rstate'] = 'normal';  //退款状态
        }
        // 筛选下单时间在 {$start_time} 至 {$end_time} 之间的订单
        $start_time = I('get.start_time');
        $end_time = I('get.end_time');
        if ($start_time && $end_time) {
            $where['o.create_time'] = array('between', array(strtotime($start_time), strtotime($end_time)));
            $displayWhere['start_time'] = $start_time;
            $displayWhere['end_time'] = $end_time;
        }
        // 筛选付款时间在 {$start_paytime} 至 {$end_paytime} 之间的订单
        $start_paytime = I('get.start_paytime');
        $end_paytime = I('get.end_paytime');
        if ($start_paytime && $end_paytime) {
            $where['o.pay_time'] = array('between', array(strtotime($start_paytime), strtotime($end_paytime)));
            $displayWhere['start_paytime'] = $start_paytime;
            $displayWhere['end_paytime'] = $end_paytime;
        }
        $Model = D('Order');
        $where_count = $this->_createCountWhere($where);
        $count = $Model->getTotal($where_count);
        if ($count === false) {
            //TODO 错误日志
            $this->_writeDBErrorLog($count, $Model);
        }
        $page = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $field = 'o.id,o.team_id,t.product,o.user_id,u.username,u.mobile,o.quantity,o.origin,o.money,o.credit,o.yuming,o.state,o.create_time';
        if ($select) {
            $data = $Model->getOrders($where, $limit, 'id DESC', $field);
            if ($data === false) {
                //TODO 错误日志
                $this->_writeDBErrorLog($data, $Model);
            }
        }
        $this->assign('displayWhere', $displayWhere);
        $this->assign('count', $count);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 获取退款订单
     */
    public function refund() {
        $select = true;
        $paramArray = array(
            array('id', '', '', 'o'),
            array('team_id', '', '', 'o'),
            array('mobile', '', '', 'o'),
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $where['o.city_id'] = $this->_getCityId();
        $username = I('get.username', '', 'strval');
        if ($username) {
            $user_id = M('user')->where(array('username' => $username))->getField('id');
            $select = $user_id ? true : false;
            $where['o.user_id'] = $user_id;
            $displayWhere['username'] = $username;
        }
        //退款状态
        $where['rstate'] = array(array('eq', 'askrefund'), array('eq', 'berefund'), 'OR');  //退款状态
        $Model = D('Order');
        $where_count = $this->_createCountWhere($where);
        $count = $Model->getTotal($where_count);
        if ($count === false) {
            //TODO 错误日志
            $this->_writeDBErrorLog($count, $Model);
        }
        $page = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $field = 'o.id,o.team_id,t.product,o.user_id,u.username,u.mobile,o.quantity,o.origin,o.money,o.credit,o.yuming,o.rstate';
        if ($select) {
            $data = $Model->getOrders($where, $limit, 'id DESC', $field);
            if ($data === false) {
                //TODO 错误日志
                $this->_writeDBErrorLog($data, $Model);
            }
        }
        $this->assign('displayWhere', $displayWhere);
        $this->assign('data', $data);
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 	通过订单编号获取订单详情
     * 	@param id  : 订单编号
     */
    public function getOrderDetail() {
        $id = intval(I('get.id'));
        if (!empty($id)) {
            $Model = D('Order');
            $pk = $Model->getPk();
            $count = $Model->where($pk . '=' . $id)->count();
            if ($count) {
                $data = $Model->getOrderDetail($id);
                if ($data === false) {
                    //TODO 记录错误日志；
                    $this->_writeDBErrorLog($data, $Model);
                }
                //TODO 处理邮购属性选择
                if ($data['optional_model']) {
                    $data['optional_model'] = json_decode($data['optional_model'], true);
                }
                if ($data['address']) {
                    $data['address'] = json_decode($data['address'], true);
                }

                // dump($order['optional_model']);

                switch (strtolower($data['yuming'])) {
                    case 'pc':
                        $data['source'] = '电脑PC';
                        break;
                    case 'newandroid':
                    case 'android':
                        $data['source'] = '安卓客户端';
                        break;
                    case 'ios':
                    case 'newios':
                        $data['source'] = 'iOS客户端';
                        break;
                    case 'mobile.youngt.com':
                    case 'm.youngt.com':
                        $data['source'] = '手机WAP';
                        break;
                    default:
                        $data['source'] = '未知';
                }

                // 获取OTA信息
                $ota = D('Ota');
                if ($ota->tmCheck($data['team_id'])) {
                    $data['ota'] = $ota->where(array('order_id'=>$data['id']))->find();
                }

                $this->assign('data', $data);
            } else {
                $this->assign('error', '订单不存在或已被删除');
            }
        } else {
            $this->assign('error', '访问不合法');
        }
        if (isset($data) && $data['express'] == 'Y') {
            $this->display('expressOrderDetail');
        } else {
            $this->display();
        }
    }

    // 释放订单
    public function cancelOrder($id,$tid) {
        $ota = D('Ota');
        $parkcode = $ota->tmCheck($tid);
        if (!$parkcode) {
            $this->error('非法操作！');
        }
        if ($ota->orderRelease($id, $parkcode)) {
            $ota->where(array('order_id'=>$id))->delete();
            M('order')->where(array('id'=>$id))->delete();
        }
        $this->success('操作成功！');
    }

}
