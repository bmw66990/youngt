<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-06-06
 * Time: 09:02
 */

namespace Admin\Controller;

class OrderController extends CommonController {

    /**
     * 订单列表
     */
    public function index() {
        $where = $this->_getSearchWhere();
        $this->_getOrderList($where);
        $this->display();
    }

    /**
     * 付款订单
     */
    public function payList() {
        $where = $this->_getSearchWhere();
        $where['state'] = 'pay';
        $this->_getOrderList($where);
        $this->display();
    }

    /**
     * 未付款订单
     */
    public function unpayList() {
        $where = $this->_getSearchWhere();
        $where['state'] = 'unpay';
        $where['rstate'] = 'normal';
        $this->_getOrderList($where);
        $this->display();
    }

    /**
     * 退款订单
     */
    public function refundList() {
        $where = $this->_getSearchWhere();
        $where['state'] = 'unpay';
        $where['rstate'] = 'berefund';
        $this->_getOrderList($where, 'refund_etime DESC,id DESC');
        $this->display();
    }

    /**
     * 获取订单列表
     *
     * @param $where
     */
    protected function _getOrderList($where, $sort = 'id DESC') {
        $orderModel = D('Order');
        $total = $orderModel->getTotal($where);
        $page = $this->pages($total, $this->reqnum, $where);
        $limit = $page->firstRow . ',' . $page->listRows;
        //$list       = $orderModel->getList($where, 'id DESC', $limit, 'id,city_id,user_id,team_id,origin,quantity,money,credit,state,rstate,service,tn,create_time,pay_time');
        $list = $orderModel->getList($where, $sort, $limit);

        // 数据整理
        if ($list) {
            foreach ($list as &$v) {
                $v['state_name'] = '';
                if (isset($v['rstate']) && trim($v['rstate']) == 'normal') {
                    $v['state_name'] = '未付款';
                    if (isset($v['state']) && trim($v['state']) == 'pay') {
                        $v['state_name'] = '已付款';
                    }
                } else if (isset($v['rstate']) && trim($v['rstate']) == 'berefund') {
                    $v['state_name'] = '已全额退款';
                    $coupon_count = M('coupon')->where(array('order_id' => $v['id']))->count();
                    if ($coupon_count && $coupon_count > 0) {
                        $v['state_name'] = '已部分退款';
                    }
                } else if (isset($v['rstate']) && trim($v['rstate']) == 'askrefund') {
                    $v['state_name'] = '申请退款';
                }
            }
            unset($v);
        }

        $team = D('Team')->getOrderTeam($list);
        $user = $this->_getOrderUser($list);
        $this->assign('list', $list);
        $this->assign('team', $team);
        $this->assign('userList', $user);
        $this->assign('pages', $page->show());
        $this->assign('service_type', $this->service_type);
        $this->_getCategoryList('city');
    }

    /**
     * 获取搜索条件
     */
    protected function _getSearchWhere() {
        $where = array(
            array('id', ''),
            array('pay_id', ''),
            array('mobile', ''),
            array('team_id', ''),
            array('city_id', ''),
            array('tn', '')
        );
        $map = $this->createSearchWhere($where);
        $searchValue = $this->getSearchParam($where);

        //TODO 考虑将此封装在createSearchWhere方法中
        $sctime = I('get.screate_time');
        if (!empty($sctime)) {
            $searchValue['screate_time'] = $sctime;
            $map['create_time'][] = array('EGT', strtotime($sctime));
        }
        $ectime = I('get.ecreate_time');
        if (!empty($ectime)) {
            $searchValue['ecreate_time'] = $ectime;
            $map['create_time'][] = array('ELT', strtotime($ectime));
        }

        $sptime = I('get.spay_time');
        if (!empty($sptime)) {
            $searchValue['spay_time'] = $sptime;
            $map['pay_time'][] = array('EGT', strtotime($sptime));
        }
        $eptime = I('get.epay_time');
        if (!empty($eptime)) {
            $searchValue['epay_time'] = $eptime;
            $map['pay_time'][] = array('ELT', strtotime($eptime));
        }

        $payType = I('get.pay_type');
        if (trim($payType)) {
            $order_service_arr = @explode('|', $payType);
            $map['service'] = array('IN', $order_service_arr);
            $searchValue['pay_type'] = $payType;
        }
        $user = I('get.username');
        if (trim($user)) {
            $where = array(
                'username|email' => array('like', '%' . trim($user) . '%')
            );
            $user_id = D('User')->where($where)->getField('id', true);
            if ($user_id) {
                $map['user_id'] = array('IN', $user_id);
            }
            $searchValue['username'] = urldecode($user);
        }
        $this->assign('searchValue', $searchValue);
        return $map;
    }

    /**
     * 获取订单用户信息
     */
    protected function _getOrderUser($order) {
        if (empty($order))
            return array();
        $user = array();
        foreach ($order as $row) {
            $user[] = $row['user_id'];
        }
        $map = array(
            'id' => array('IN', array_unique($user))
        );
        $user = M('User')->where($map)->getField('id,username,email', true);
        $this->_writeDBErrorLog($user, M('User'), 'admin');
        return $user;
    }

    /**
     * 订单详情
     */
    public function detail() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model = D('Order');
        $order = $model->isExistOrder($id);
        if (empty($order)) {
            $this->redirect_message(U("Order/index"), array('error' => base64_encode('订单不存在!')));
            exit();
        }
        $data = $model->getOrderDetail($id);

        //TODO 处理邮购属性选择
        if ($order['optional_model']) {
            $order['optional_model'] = json_decode($order['optional_model'], true);
        }

        if (isset($order['address']) && trim($order['address'])) {
            $order['address'] = json_decode($order['address'], true);
        }

        if (isset($order['express']) && trim($order['express']) == 'Y') {
            $express_res = $this->_getCategoryList('express');
            $this->assign('express_res', $express_res);
        }

        // dump($order['optional_model']);
        switch (strtolower($order['yuming'])) {
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
            case 'wap':
                $data['source'] = '手机WAP';
                break;
            default:
                $data['source'] = '未知';
        }


        // 获取OTA信息
        $ota = D('Ota');
        if ($ota->tmCheck($order['team_id'])) {
            $data['ota'] = $ota->where(array('order_id'=>$order['id']))->find();
        }

        $this->assign('data', $data);
        $this->assign('order', $order);
        $this->display();
    }

    /**
     * 现金
     */
    public function cash() {
        $this->_checkblank('id');
        $id = I('param.id', 0, 'intval');
        $order = D('Order')->isExistOrder($id);
        if (empty($order)) {
            $this->error('订单不存在');
        }
        if ($order['state'] == 'pay') {
            $this->error('该订单已支付');
        }
        if (!$order['pay_id']) {
            $randId = strtr($order['money'], array('.' => '-', ',' => '-'));
            $pay_id = "go-" . $order['id'] . "-" . $order['quantity'] . "-" . $randId;
            $rs = M('Order')->where('id=' . $id)->setField('pay_id', $pay_id);
            if (!$rs) {
                $this->error('操作失败');
            }
            $order['pay_id'] = $pay_id;
        }
        $res = D('Team')->updateOrderUser($order, $order['money'], 'CNY', 'cash', '现金', '');
        if ($res) {
            $this->addOperationLogs("操作：订单现金,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},订单id:{$id}");
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 删除订单
     */
    public function delOrder() {
        $this->_checkblank('id');
        $id = I('param.id', 0, 'intval');
        $order = D('Order')->isExistOrder($id);
        if (empty($order)) {
            $this->error('订单不存在');
        }
        if ($order['state'] == 'pay') {
            $this->error('订单已付款');
        }
        if ($order['state'] == 'unpay' && $order['rstate'] == 'berefund') {
            $this->error('退款订单无法删除');
        }
        if ((time() - $order['create_time']) < 7200) {
            $this->error('订单在2小时之内无法删除，请在2小时之后删除');
        }
        $coupon = M('Coupon')->where('order_id=' . $id)->count();
        if ($coupon && $coupon > 0) {
            $this->error('此订单包含券号，无法删除');
        }
        $res = M('Order')->delete($id);
        if ($res) {
            //TODO 考虑有代金券的话,是否有操作
            $this->addOperationLogs("操作：删除订单,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},订单id:{$id}");
            $this->success('订单删除成功');
        } else {
            $this->error('订单删除失败');
        }
    }

    /**
     * 订单第三方查询
     */
    public function orderQuery() {
        $this->_checkblank('id');
        $id = I('param.id', 0, 'intval');

        $dis = I('get.dis', 0, 'intval');

        if ($dis != 0) {
            $order = M('discountOrder')->where(array('id'=>$id))->find();
        } else {
            $order = D('Order')->isExistOrder($id);
        }

        if (empty($order)) {
            $this->redirect_message(U("Order/index"), array('error' => base64_encode('订单不存在!')));
            exit();
        }
        if (!$order['pay_id']) {
            $this->redirect_message(U("Order/index"), array('error' => base64_encode('不是第三方支付!')));
            exit();
        }

        $query = new \Common\Org\Pay();
        $data = array();
        $type = $service = '';
        switch (trim($order['service'])) {
            case 'alipay':
            case 'aliapp':
            case 'aliwap':
            case 'pcalipay':
            case 'wapalipay':
                $type = 'alipay';
                $service = '支付宝';
                $data = $query->orderQuery('alipay', $order['pay_id'], $order['trade_no']);
                if (!$data) {
                    $data = $query->orderQuery('alipay', $order['id'], $order['trade_no']);
                }
                break;
            case 'tenpay':
            case 'tenapp':
            case 'tenwap':
            case 'pctenpay':
            case 'waptenpay':
                $type = 'tenpay';
                $service = '财付通';
                $data = $query->orderQuery('tenpay', $order['pay_id'], $order['trade_no']);
                if (!$data) {
                    $data = $query->orderQuery('tenpay', $order['id'], $order['trade_no']);
                }
                break;
            case 'umspay':
                $type = 'umspay';
                $service = '全民付';
                $data = $query->orderQuery('umspay', $order['pay_id'], $order['trade_no']);
                if (!$data) {
                    $data = $query->orderQuery('umspay', $order['id'], $order['trade_no']);
                }
                break;
            case 'wechatpay':
            case 'wapwechatpay':
            case 'pcwxpaycode':
            case 'wxpay':
                $type = 'wechatpay';
                $service = '微信';
                $data = $query->orderQuery($order['service'], $order['pay_id'], $order['trade_no']);
                if (!$data) {
                    $data = $query->orderQuery($order['service'], $order['id'], $order['trade_no']);
                }
                break;
            case 'unionpay':
            case 'wapunionpay':
                $type = 'unionpay';
                $service = '银联';
                $data = $query->orderQuery($order['service'], $order['pay_id'], $order['trade_no']);
                if (!$data) {
                    $data = $query->orderQuery($order['service'], $order['id'], $order['trade_no']);
                }
                break;
            case 'lianlianpay':
                $type = 'lianlianpay';
                $service = '连连';
                $data = $query->orderQuery($order['service'], $order['pay_id'], $order['trade_no']);
                if (!$data) {
                    $data = $query->orderQuery($order['service'], $order['id'], $order['trade_no']);
                }
                break;
            case 'wepay':
                $type = 'wepay';
                $service = '京东支付';
                $data = $query->orderQuery($order['service'], $order['pay_id']);
                break;
            default :
                $data = $query->orderQuery('', $order['pay_id'], $order['trade_no']);
                if (!$data) {
                    $data = $query->orderQuery('', $order['id'], $order['trade_no']);
                }
                break;
        }
        $this->assign('data', $data);
        $this->assign('type', $type);
        $this->assign('service', $service);
        $this->display();
    }

    /**
     * 申请退款
     */
    public function applyRefund() {
        $this->_checkblank('id');
        $id = I('param.id', 0, 'intval');
        $model = D('Order');
        $order = $model->isExistOrder($id);
        if (!$order) {
            $this->redirect_message(U("Order/applyRefund"), array('error' => base64_encode('订单不存在!')));
            exit();
        }
        if ($order['state'] != 'pay') {
            $this->redirect_message(U("Order/applyRefund"), array('error' => base64_encode('该订单未支付，无法申请退款!')));
            exit();
        }
        if ($order['rstate'] == 'askrefund') {
            $this->redirect_message(U("Order/applyRefund"), array('error' => base64_encode('该订单已申请退款!')));
            exit();
        }
        // get 显示
        if (!IS_POST) {
            $this->assign('id', $id);
            $this->display();
            exit();
        }

        // 处理post请求
        $type = I('post.type');
        if (!in_array($type, array(1, 2))) {
            $this->redirect_message(U("Order/applyRefund"), array('error' => base64_encode('参数错误!')));
            exit();
        }
        if ($type == 1) {
            $info = '退至青团账户';
        } else if ($type == 2) {
            $info = '原路退回';
        }
        $refundData = array(
            'tn' => $info,
            'retime' => time(),
            'rereason' => '客服处理',
            'rstate' => 'askrefund'
        );
        if ($rs = M('Order')->where('id=' . $id)->save($refundData)) {
            $this->addOperationLogs("操作：订单申请退款,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},订单id:{$id}");
            $this->success('退款申请成功');
        } else {
            $this->_writeDBErrorLog($rs, M('Order'), 'admin');
            $this->error('退款申请失败');
        }
    }

    /**
     * 强制确认收货！
     */
    public function orderConfirmReceipt() {
        $order_id = I('get.order_id', '', 'trim');

        $order = D('Order');
        $res = $order->orderConfirmReceipt($order_id);
        if (isset($res['error']) && trim($res['error'])) {
            $this->ajaxReturn(array('status' => -1, 'info' => $res['error']));
        }
        $this->addOperationLogs("操作：邮购订单强制确认收货,订单id:{$order_id}，结果：成功！");
        $this->ajaxReturn(array('status' => 1, 'info' => '强制确认收货成功！'));
    }

    /**
     * 订单信息下载
     */
    public function orderDetailDown() {
        if (IS_POST) {
            $username = I('post.username', '', 'trim');
            $mobile = I('post.mobile', '', 'trim');
            $team_id = I('post.team_id', '', 'intval');
            $begin_time = I('post.begin_time', date('Y-m-d', strtotime('-1 Months')), 'trim');
            $end_time = I('post.end_time', date('Y-m-d'), 'trim');
            $team_type = I('post.team_type', '', 'trim');
            $is_consume = I('post.is_consume', '', 'trim');
            $is_state = I('post.is_state', '', 'trim');
            $is_delivery = I('post.delivery', '', 'trim');
            $city_id = I('post.city_id', '', 'intval');
            $pay_type = I('post.pay_type', '', 'trim');

            $where = array();
            //通过用户名或邮箱查询订单
            if ($username) {
                $where['u.username|u.email'] = $username;
            }
            //通过手机号查询订单
            if ($mobile) {
                $where['u.mobile|o.mobile'] = $mobile;
            }
            //通过项目编号查询订单
            if ($team_id) {
                $where['o.team_id'] = $team_id;
            }
            if (trim($begin_time) == '' || trim($end_time) == '') {
                $this->redirect_message(U("Order/orderDetailDown"), array('error' => base64_encode('请选择起止时间!')));
                exit();
            }
            if (strtotime($begin_time) > strtotime($end_time)) {
                $this->redirect_message(U("Order/orderDetailDown"), array('error' => base64_encode('开始时间不能大于结束时间!')));
                exit();
            }
            //通过时间查询订单
            if ($begin_time && $end_time) {
                $where['o.create_time'] = array('between', array(strtotime($begin_time), strtotime($end_time)));
            } else {
                $where['o.create_time'] = array('between', array(strtotime($begin_time), strtotime($end_time)));
            }

            //通过是否结算进行筛选
            if ($is_consume) {
                $where['o.is_pay'] = $is_consume;
            }

            //通过项目类型查询订单
            if ($team_type) {
                if ($team_type == 'lottery') {
                    $where['t.team_type'] = array('in', array('newuser', 'limited', 'timelimit'));
                } elseif ($team_type == 'goods') {
                    $where['_string'] = "t.team_type = '{$team_type}' OR t.delivery = 'express'";
                } else {
                    $where['t.team_type'] = 'normal';
                }
            }
            //通过订单状态查询订单
            if ($is_state) {
                if ($is_state == 'Y') {
                    $where['o.state'] = 'pay';
                } else {
                    $where['o.state'] = 'unpay';
                    $where['o.rstate'] = 'normal';
                }
            }
            //通过订单的发货状态查询订单
            if ($is_delivery && $team_type == 'goods') {
                if ($is_delivery == 'Y') {
                    $where['o.express_no'] = array('neq', '');
                } else {
                    $where['_string'] = 'o.express_no = "" OR o.express_no is null';
                }
            }
            //通过城市id查询订单
            if ($city_id) {
                $where['o.city_id'] = $city_id;
            }
            //通过支付渠道查询订单
            if ($pay_type) {
                $pay_type_where = array();
                if (in_array('alipay', $pay_type)) {
                    $pay_type_where[] = 'alipay';
                    $pay_type_where[] = 'aliapp';
                    $pay_type_where[] = 'aliwap';
                    $pay_type_where[] = 'pcalipay';
                    $pay_type_where[] = 'wapalipay';
                }
                if (in_array('tenpay', $pay_type)) {
                    $pay_type_where[] = 'tenpay';
                    $pay_type_where[] = 'tenapp';
                    $pay_type_where[] = 'tenwap';
                    $pay_type_where[] = 'pctenpay';
                    $pay_type_where[] = 'waptenpay';
                }
                if (in_array('umspay', $pay_type)) {
                    $pay_type_where[] = 'umspay';
                }
                if (in_array('wechatpay', $pay_type)) {
                    $pay_type_where[] = 'wapwechatpay';
                    $pay_type_where[] = 'pcwxpaycode';
                    $pay_type_where[] = 'wxpay';
                }
                if (in_array('unionpay', $pay_type)) {
                    $pay_type_where[] = 'unionpay';
                    $pay_type_where[] = 'wapunionpay';
                }
                if (is_array('lianlianpay', $pay_type)) {
                    $pay_type_where[] = 'lianlianpay';
                }
                if (in_array('wepay', $pay_type)) {
                    $pay_type_where[] = 'wepay';
                }
                if (in_array('credit', $pay_type)) {
                    $pay_type_where[] = 'credit';
                    $pay_type_where[] = 'cash';
                }
                $where['o.service'] = array('in', $pay_type_where);
            }
            $down_file_name = "Youngt_Order_{$begin_time}-{$end_time}";
            $this->_getOrderDown($where, $down_file_name);
        }
        $this->assign(array('end_time' => date('Y-m-d'), 'begin_time' => date('Y-m-d', strtotime('-1 Months'))));
        $this->assign('city', $this->_getCategoryList('city'));
        $this->display();
    }

    /**
     * 通过条件下载符合条件的订单信息
     *
     * @param $where
     */
    protected function _getOrderDown($where, $down_file_name) {
        $Model = M('order');
        $field = array(
            'o.id' => 'order_id',
            'o.team_id' => 'order_team_id',
            'o.partner_id' => 'order_partner_id',
            'p.username' => 'partner_username',
            'p.mobile' => 'partner_mobile',
            'u.mobile' => 'user_mobile',
            'o.mobile' => 'order_mobile',
            'p.bank_user' => 'partner_bank_user',
            'p.bank_no' => 'partner_bank_no',
            't.product' => 'team_product',
            'o.quantity' => 'order_quantity',
            'o.origin' => 'order_origin',
            'o.quantity*t.ucaii_price+o.fare' => 'team_ucaii_price',
            'o.fare' => 'order_fare',
            'u.username' => 'user_username',
            'o.optional_model' => 'order_optional_model',
            'o.address' => 'order_address',
            'o.express_no' => 'order_express_no',
            'c.name' => 'category_express_name',
            'o.remark' => 'order_remark',
            'o.adminremark' => 'order_adminremark',
            'o.yuming' => 'order_yuming'
        );
        $data = $Model->alias('o')->join('left join user u ON u.id = o.user_id')
                ->join('left join team t ON t.id = o.team_id')
                ->join('left join partner p ON p.id = o.partner_id')
                ->join('left join category c ON c.id = o.express_id')
                ->field($field)
                ->where($where)
                ->select();
        if (count($data) == 0) {
            $this->redirect_message(U("Order/orderDetailDown"), array('error' => base64_encode('您的筛选条件下没有数据，请检查您的筛选条件')));
            exit();
        }
        if (count($data) > 1000) {
            $this->redirect_message(U("Order/orderDetailDown"), array('error' => base64_encode('下载数据数量超过1000条，请选择更多下载条件!')));
            exit();
        }
        foreach ($data as &$val) {
            $address = json_decode($val['order_address'], true);
            if (!is_null($address)) {
                $address_str = "收货人：{$address['name']}，";
                $address_str.= "手机号码：{$address['mobile']}，";
                $address_str.= "邮编：{$address['zipcode']}，";
                $address_str.= "收货地址：{$address['province']}-{$address['area']}-{$address['street']}。";
                $val['order_address'] = $address_str;
            }
            $option_model = json_decode($val['order_optional_model'], true);
            if (!is_null($option_model)) {
                $model_str = '商品选项：';
                foreach ($option_model as $model_val) {
                    $model_str.= $model_val['name'] . ':' . $model_val['num'] . ',';
                }
                $val['order_optional_model'] = substr($model_str, 0, -1);
            }
            switch (strtolower($val['order_yuming'])) {
                case 'pc':
                    $val['order_yuming'] = '电脑PC';
                    break;
                case 'newandroid':
                case 'android':
                    $val['order_yuming'] = '安卓客户端';
                    break;
                case 'ios':
                case 'newios':
                    $val['order_yuming'] = 'iOS客户端';
                    break;
                case 'mobile.youngt.com':
                case 'm.youngt.com':
                case 'wap':
                    $val['order_yuming'] = '手机WAP';
                    break;
                default:
                    $val['order_yuming'] = '未知';
            }
        }
        $dawn_name = array(
            'order_id' => '订单号',
            'order_team_id' => '团单编号',
            'order_partner_id' => '商户编号',
            'order_mobile' => '订单手机号',
            'user_mobile' => '用户手机号',
            'partner_username' => '商户名称',
            'partner_mobile' => '商户电话',
            'partner_bank_user' => '商户开户名',
            'partner_bank_no' => '结算账号',
            'team_product' => '产品名称',
            'order_quantity' => '数量',
            'order_origin' => '实付',
            'team_ucaii_price' => '结算价',
            'order_fare' => '邮费',
            'order_address' => '收货信息',
            'category_express_name' => '快递名称',
            'express_no' => '快递单号',
            'order_optional_model' => '选项',
            'order_remark' => '备注',
            'order_adminremark' => '客服备注',
            'order_yuming' => '来源域名',
        );
        download_xls($data, $dawn_name, $down_file_name);
    }

    /**
     * 修改快递信息
     */
    public function updateExpressInfo() {
        $order_id = I('get.order_id', '', 'trim');
        $express_id = I('post.express_id', '', 'trim');
        $express_no = I('post.express_no', '', 'trim');

        if (!$order_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '订单id不能为空！'));
        }
        if (!$express_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请选择快递类型！'));
        }
        if (!$express_no) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请输入快递单号！'));
        }

        $where = array('id' => $order_id);
        $order_res = M('order')->where($where)->field('user_id,state,mail_order_pay_state,team_id,express_id,express_no')->find();
        if (!$order_res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '订单不存在！'));
        }
        if (!isset($order_res['state']) || trim($order_res['state']) != 'pay') {
            $this->ajaxReturn(array('code' => -1, 'error' => '该订单未支付,不能修改物流信息！'));
        }
        if (isset($order_res['mail_order_pay_state'])) {
            if (trim($order_res['mail_order_pay_state']) == '0') {
             //   $this->ajaxReturn(array('code' => -1, 'error' => '该订单商家未发货,不能修改物流信息！'));
            }
            if (trim($order_res['mail_order_pay_state']) == '2') {
                $this->ajaxReturn(array('code' => -1, 'error' => '该订单用户已经收货,不能修改物流信息！'));
            }
        }
        $team_info = M('team')->where(array('id' => $order_res['team_id']))->field('team_type,delivery ')->find();
        if (!$team_info ||  $team_info['delivery']!= 'express' ) {
                $this->ajaxReturn(array('code' => -1, 'error' => '该订单不是邮购订单,不能修改物流信息！'));
        }
        
        $data = array(
            'express_id' => $express_id,
            'express_no' => $express_no,
            'partner_deliver_time' => time(),
            'mail_order_pay_state' => 1,
        );
        $res = M('order')->where($where)->save($data);
        if ($res === false) {
            $this->ajaxReturn(array('code' => -1, 'error' => '发货失败！'));
        }
        $this->addOperationLogs("操作：邮购订单修改收货信息,订单id:{$order_id}，订单类型id：{$express_id}，订单号：{$express_no}结果：成功！");
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 追踪包裹
     */
    public function orderLogisticsView() {
        $order_id = I('get.order_id', '', 'trim');

        if (!$order_id) {
            $this->redirect_message(U("Order/payList"), array('error' => base64_encode('订单id不能为空!')));
        }

        $where = array(
            'id' => $order_id,
            'rstate' => 'normal', //
        );
        $order_res = M('order')->where($where)->field('user_id,state,mail_order_pay_state,team_id,express_id,express_no')->find();
        if (!$order_res) {
            $this->redirect_message(U("Order/payList"), array('error' => base64_encode('订单不存在!')));
        }

        if (!isset($order_res['state']) || trim($order_res['state']) != 'pay') {
            $this->redirect_message(U("Order/payList"), array('error' => base64_encode('该订单未支付，不能查看物流!')));
        }
        if (!isset($order_res['express_id']) || !trim($order_res['express_id'])) {
            $this->redirect_message(U("Order/payList"), array('error' => base64_encode('该订单没有寄件信息，不能查看物流!')));
        }
        if (!isset($order_res['express_no']) || !trim($order_res['express_no'])) {
            $this->redirect_message(U("Order/payList"), array('error' => base64_encode('该订单没有寄件信息，不能查看物流!')));
        }
        if (!isset($order_res['mail_order_pay_state']) || intval($order_res['mail_order_pay_state']) < 1) {
            $this->redirect_message(U("Order/payList"), array('error' => base64_encode('该订单未发货，不能查看物流!')));
        }

        $express_res = $this->_getCategoryList('express');
        $type = ternary($express_res[$order_res['express_id']]['ename'], '');
        $express_query = new \Common\Org\ExpressQuery();
        $data = array();
        $res = $express_query->express_query($type, $order_res['express_no']);
        if (isset($res['status']) && $res['status'] == 200 && isset($res['data'])) {
            $data = $res['data'];
        }

        $state = '未发货';
        if (intval($order_res['mail_order_pay_state']) == 1) {
            $state = '待收货';
        }
        if (intval($order_res['mail_order_pay_state']) == 2) {
            $state = '已收货';
        }

        $r_data = array(
            'state' => $state,
            'express_name' => ternary($express_res[$order_res['express_id']]['name'], ''),
            'express_no' => ternary($order_res['express_no'], ''),
            'list' => $data
        );
        $this->assign($r_data);
        $this->display();
    }

    /**
     * 售后
     */
    public function service() {
        $this->_checkblank('id');
        $id = I('param.id', 0, 'intval');
        $order = M('order')->find($id);

        $returninfo = M('returninfo')->where('order_id=' . $id)->find();
        if (!$returninfo) {
            $address = json_decode($order['address'], true);
            $returninfo['mobile'] = $order['mobile'];
            $returninfo['realname'] = $address['name'];
            $returninfo['money'] = $order['money'];
            $returninfo['duty'] = '买家';
            $returninfo['expressmoney'] = $order['fare'];
            $returninfo['team_id'] = $order['team_id'];
            $returninfo['team_title'] = M('team')->where('id=' . $order['team_id'])->getField('title');
            $returninfo['user_id'] = $order['user_id'];
            if ($order['partner_id']) {
                $partner = M('partner')->where('id=' . $order['partner_id'])->find();
            }
            $returninfo['partner_title'] = $partner['title'];
            $returninfo['order_id'] = $id;
            $returninfo['create_time'] = $returninfo['finish_time'] = time();
        }
        if (IS_POST) {
            unset($_POST['id']);
            $returninfo = array_merge($returninfo, $_POST);
            if (isset($returninfo['id'])) {
                // update
                $map = array(
                    'order_id' => $id,
                    'id' => $returninfo['id']
                );
                $res = M('returninfo')->where($map)->save($returninfo);
            } else {
                // add
                $res = M('returninfo')->add($returninfo);
            }
            if ($res) {
                if (isset($returninfo['id'])) {
                    $this->addOperationLogs("操作：编辑售后信息,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},订单id:{$id}");
                } else {
                    $this->addOperationLogs("操作：新增售后信息,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},订单id:{$id}");
                }
                $this->success('售后编辑成功');
            } else {
                $this->error('售后编辑失败');
            }
        } else {
            $this->assign('info', $returninfo);
            $this->display();
        }
    }

    /**
     *  快捷生成券号
     */
    public function quickCreateCoupon() {
        $order_id = I('get.order_id', '', 'trim');
        if (!$order_id) {
            $this->display();
            exit;
        }
        $filed = array(
            'id',
            'quantity',
            'team_id',
            'partner_id',
            'rstate',
            'origin',
            'state',
            'user_id',
            'buy_id',
            'mobile',
            'price',
            'express',
            'pay_time',
            'create_time',
        );
        $order_res = M('order')->where(array('id' => $order_id))->field($filed)->find();
        if (!$order_res) {
            $this->redirect_message(U("Order/quickCreateCoupon"), array('error' => base64_encode('该订单不存在!')));
        }
        if (!isset($order_res['rstate']) || trim($order_res['rstate']) != 'normal') {
            $this->redirect_message(U("Order/quickCreateCoupon"), array('error' => base64_encode('该订单不是正常状态的订单!')));
        }
        if (!isset($order_res['state']) || trim($order_res['state']) != 'pay') {
            $this->redirect_message(U("Order/quickCreateCoupon"), array('error' => base64_encode('该订单未付款不能生成券号!')));
        }
        if (!isset($order_res['express']) || trim($order_res['express']) == 'Y') {
            $this->redirect_message(U("Order/quickCreateCoupon"), array('error' => base64_encode('该订单是邮购类订单不能生成券号!')));
        }
        $coupon_res = M('coupon')->where(array('order_id' => $order_id))->select();
        $coupon_count = count($coupon_res);
        $field = array(
            'partner_id',
            'team_price',
            'team_type',
            'credit',
            'ucaii_price',
            'expire_time',
            'title',
            'notice',
            'product',
            'begin_time',
        );
        $team_res = M('team')->where(array('id' => $order_res['team_id']))->field($field)->find();
        if (!$team_res) {
            $this->redirect_message(U("Order/quickCreateCoupon"), array('error' => base64_encode('该订单购买的商品不存在!')));
        }
        if (!isset($team_res['team_type']) || trim($team_res['team_type']) == 'goods') {
            $this->redirect_message(U("Order/quickCreateCoupon"), array('error' => base64_encode('该订单是邮购类订单不能生成券号!')));
        }
        $info = '该订单券号存在，无需生成！';
        if (isset($order_res['quantity']) && intval($order_res['quantity']) > $coupon_count) {

            $res = D('Team')->addCoupon($order_res, $team_res);
            if (!$res) {
                $this->redirect_message(U("Order/quickCreateCoupon"), array('error' => base64_encode('该订单购买的商品不存在!')));
            }
            $coupon_res = M('coupon')->where(array('order_id' => $order_id))->index('id')->select();
            $info = '该订单券号生成成功！';
            $coupon_ids = implode(',', array_keys($coupon_res));
            $this->addOperationLogs("操作：快捷生成券号,订单id:{$order_id}，券号id：[{$coupon_ids}]结果：成功！");
        }
        $data = array(
            'info' => $info,
            'order_id' => $order_id,
            'coupon_list' => $coupon_res,
            'order_info' => $order_res,
            'team_info' => $team_res,
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 云购订单列表
     */
    public function cloudShopingOrder() {
        $team_id = I('get.team_id','','trim');

        $where = array(
            'team.team_type' => 'cloud_shopping',
        );
        if($team_id){
            $where['team.id'] = $team_id;
        }

        $team = M('team');
        $count = $team->where($where)->join('left join cloud_shoping_result as csr on csr.team_id=team.id')->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'team.id' => 'team_id',
            'team.title' => 'team_product',
            'team.now_periods_number' => 'team_now_periods_number',
            'team.team_price' => 'team_team_price',
            'team.permin_number' => 'team_permin_number',
            'team.delivery' => 'team_delivery',
            'csr.periods_number' => 'csr_periods_number',
            'csr.status' => 'csr_status',
        );
        $list = $team->field($field)->where($where)->order('team.id desc,csr.id desc')
                ->join('left join cloud_shoping_result as csr on csr.team_id=team.id')
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        if ($list) {
            foreach ($list as &$v) {
                if (isset($v['csr_periods_number']) && trim($v['csr_periods_number'])) {
                    $v['team_now_periods_number'] = $v['csr_periods_number'];
                }
                $v['status_text'] = '进行中';
                if (isset($v['csr_status']) && trim($v['csr_status'])) {
                    $v['status_text'] = '未领取';
                    if (trim($v['csr_status']) == '2') {
                        $v['status_text'] = '已领取';
                    }
                }
                $v['delivery_text'] = '邮寄';
                if(isset($v['team_delivery']) && trim($v['team_delivery'])=='coupon'){
                    $v['delivery_text'] = '青团券';
                }
            }
            unset($v);
        }

        $data = array(
            'team_id' => $team_id,
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );

        $this->assign($data);
        $this->display();
    }
    
    /**
     * 云购订单记录
     */
    public function cloudShopingRecord(){
        $team_id = I('get.id','','trim');
        $periods_number = I('get.periods_number','','trim');
                
        $where = array(
            'team.team_type'=>'cloud_shopping',
            'team.id'=>$team_id,
            'csr.periods_number'=>$periods_number,
        );
        $field = array(
            'team.id' => 'team_id',
            'team.title' => 'team_product',
            'team.now_periods_number' => 'team_now_periods_number',
            'team.now_number' => 'team_now_number',
            'team.max_number' => 'team_max_number',
            'team.team_price' => 'team_team_price',
            'team.delivery' => 'team_delivery',
            'csr.periods_number' => 'csr_periods_number',
            'csr.status' => 'csr_status',
            'csr.winning_user_id' => 'csr_winning_user_id',
            'csr.winning_cloud_code' => 'csr_winning_cloud_code',
            'csr.begin_time' => 'csr_begin_time',
        );
        $team = M('team');
        $order_res = $team->field($field)->where($where)
                ->join('left join cloud_shoping_result as csr on csr.team_id=team.id')
                ->find();
        
        // 状态
        $order_res['status_text']='进行中';
        if(isset($order_res['csr_status']) && trim($order_res['csr_status'])=='1'){
            $order_res['status_text']='已揭晓';
        }else if(isset($order_res['csr_status']) && trim($order_res['csr_status'])=='2'){
            $order_res['status_text']='已领取';
        }
        
        // 中奖用户名称
        $order_res['csr_winning_user_name']='';
        if(isset($order_res['csr_winning_user_id']) && trim($order_res['csr_winning_user_id'])){
             $order_res['csr_winning_user_name'] = M('user')->where(array('id'=>$order_res['csr_winning_user_id']))->getField('username');
        }
        
        // 当前期数
        if(isset($order_res['csr_periods_number']) && trim($order_res['csr_periods_number'])){
             $order_res['team_now_periods_number'] = trim($order_res['csr_periods_number']);
        }
        
        $where = array(
            'team_id'=>$team_id,
            'periods_number'=>$periods_number,
        );
        $cloud_shoping_code = M('cloud_shoping_code');
        $count = $cloud_shoping_code->where($where)->group('user_id')->field('user_id')->select();
        $count = count($count);
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'user_id' => 'user_id',
            'count(id)' => 'pay_count',
        );
        $list = $cloud_shoping_code->where($where)->group('user_id')->field($field)
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        if($list){
            $user_ids = array();
            foreach($list as &$v){
                $user_ids[$v['user_id']] = $v['user_id'];
            }
            unset($v);
            $user_info = array();
            if($user_ids){
                $user_info = M('user')->where(array('id'=>array('in',  array_keys($user_ids))))->field('id,username')->index('id')->select();
            }
            foreach($list as &$v){
               $v['user_username'] = ternary($user_info[$v['user_id']]['username'],'');
            }
            unset($v);
            
        }
        $data=array(
            'order'=>$order_res,  
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );
        
        $this->assign($data);
        $this->display();   
    }
    
    /**
     * 云购订单详情
     */
    public function cloudShopingDetail(){
        $team_id = I('get.id','','trim');
        $periods_number = I('get.periods_number','','trim');
                
        $where = array(
            'team.team_type'=>'cloud_shopping',
            'team.id'=>$team_id,
            'csr.periods_number'=>$periods_number,
        );
        $field = array(
            'team.id' => 'team_id',
            'team.title' => 'team_product',
            'team.now_periods_number' => 'team_now_periods_number',
            'team.now_number' => 'team_now_number',
            'team.max_number' => 'team_max_number',
            'team.permin_number' => 'team_permin_number',
            'team.team_price' => 'team_team_price',
            'team.delivery' => 'team_delivery',
            'csr.periods_number' => 'csr_periods_number',
            'csr.status' => 'csr_status',
            'csr.winning_user_id' => 'csr_winning_user_id',
            'csr.winning_cloud_code' => 'csr_winning_cloud_code',
            'csr.winning_order_id' => 'csr_winning_order_id',
            'csr.begin_time' => 'csr_begin_time',
        );
        $team = M('team');
        $order_res = $team->field($field)->where($where)
                ->join('left join cloud_shoping_result as csr on csr.team_id=team.id')
                ->find();
        
        // 状态
        $order_res['status_text']='进行中';
        if(isset($order_res['csr_status']) && trim($order_res['csr_status'])=='1'){
            $order_res['team_now_number'] = $order_res['team_max_number'];
            $order_res['status_text']='已揭晓';
        }else if(isset($order_res['csr_status']) && trim($order_res['csr_status'])=='2'){
            $order_res['team_now_number'] = $order_res['team_max_number'];
            $order_res['status_text']='已领取';
        }
        
        // 中奖用户名称
        $order_res['csr_winning_user_name']='';
        if(isset($order_res['csr_winning_user_id']) && trim($order_res['csr_winning_user_id'])){
             $order_res['csr_winning_user_name'] = M('user')->where(array('id'=>$order_res['csr_winning_user_id']))->getField('username');
        }
        
        // 当前期数
        if(isset($order_res['csr_periods_number']) && trim($order_res['csr_periods_number'])){
             $order_res['team_now_periods_number'] = trim($order_res['csr_periods_number']);
        }
        
        //获奖结果获取
        $order_res['address']=array();
        $order_res['express_id']='';
        $order_res['express_no']='';
        $order_res['mail_order_pay_state']='';
        $order_res['coupon']=array();
        $order_res['express_res']=array();
        if(isset($order_res['csr_winning_order_id']) && trim($order_res['csr_winning_order_id'])){
             
            if(isset($order_res['team_delivery']) && trim($order_res['team_delivery'])=='coupon'){
                $order_res['coupon'] = M('coupon')->where(array('order_id'=>$order_res['csr_winning_order_id']))->field('id,consume')->find();
            }elseif(isset($order_res['team_delivery']) && trim($order_res['team_delivery'])=='express'){
                $order_res['express_res'] = $this->_getCategoryList('express');
                $orderRes = M('order')->where(array('id'=>$order_res['csr_winning_order_id']))->field('address,express_id,express_no,mail_order_pay_state')->find();
                $address = array();
                if(isset($orderRes['address']) && trim($orderRes['address'])){
                    $address = @json_decode(trim($orderRes['address']),true);
                }
                if($address){
                    $order_res['address'] = $address;
                }
                if(isset($orderRes['express_id']) && trim($orderRes['express_id'])){
                    $order_res['express_id'] = $orderRes['express_id'];
                }
                if(isset($orderRes['express_no']) && trim($orderRes['express_no'])){
                    $order_res['express_no'] = $orderRes['express_no'];
                }
                if(isset($orderRes['mail_order_pay_state']) && trim($orderRes['mail_order_pay_state'])){
                    $order_res['mail_order_pay_state'] = $orderRes['mail_order_pay_state'];
                }
                               
            }
       
        }
        
        $data=array(
            'order'=>$order_res,  
        );
        
        $this->assign($data);
        $this->display();  
    }
    
    /**
        * 云购查看云购码详情
        */
    public function cloudShopingCodeDetail(){
        $uid = I('get.uid','','trim');
        $team_id= I('get.team_id','','trim');
        $periods_number = I('get.periods_number','','trim');
        
        $username = '';
        if($uid){
            $username = M('user')->where(array('id'=>$uid))->getField('username');
        }
        $team_product = '';
        if($team_id){
            $team_product = M('team')->where(array('id'=>$team_id))->getField('title');
        }
             
        $where = array(
            'user_id'=>$uid,
            'team_id'=>$team_id,
            'periods_number'=>$periods_number,
        );
        $cloud_shoping_code = M('cloud_shoping_code');
        $count = $cloud_shoping_code->where($where)->count();
        $page = $this->pages($count, $this->reqnum);

        $list = $cloud_shoping_code->where($where)
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        
         $data=array(
            'team_id' => $team_id,
            'periods_number' => $periods_number,
            'uid' => $uid,
            'username' => $username,
            'team_product' => $team_product,
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );
        
        $this->assign($data);
        $this->display();   
    }

    /**
     * 优惠买单订单
     */
    public function discountOrder() {
        $partner_id = I('get.partner_id','','trim');
        $where = array(
            'state' => 'pay'
        );
        if($partner_id){
            $where['partner_id'] = $partner_id;
        }

        $order = M('discountOrder');
        $count = $order->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $list = $order->where($where)->order('partner_id desc,create_time desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        if ($list) {
            foreach ($list as &$v) {
                $v['partner'] = M('partner')->where(array('id'=>$v['partner_id']))->getField('title');
                $v['product'] = '实时消费';
                $v['create_time'] = date('Y-m-d H:i',$v['create_time']);
                $v['state'] = $v['payed_id'] != 0 ? '已结算' : '未结算';
                $v['amoney'] = $v['origin'] - round($v['origin'] * $v['aratio'],2);
            }
            unset($v);
        }

        $data = array(
            'partner_id' => $partner_id,
            'count' => $count,
            'list' => $list,
            'page' => $page->show()
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 订单详情
     */
    public function discountOrderDetail() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model = M('discountOrder');
        $order = $model->where(array('id'=>$id))->find();
        $data['user'] = M('user')->field(array('username','email'))->where(array('id'=>$order['user_id']))->find();
        $data['product'] = '实时消费';
        if (empty($order)) {
            $this->redirect_message(U("Order/discountOrder"), array('error' => base64_encode('订单不存在!')));
            exit();
        }

        // dump($order['optional_model']);
        switch (strtolower($order['yuming'])) {
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
        $this->assign('data', $data);
        $this->assign('order', $order);
        $this->display();
    }
}
