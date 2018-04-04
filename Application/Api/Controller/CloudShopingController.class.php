<?php

namespace Api\Controller;

/**
 * 云购相关接口
 */
class CloudShopingController extends CommonController {

    protected $checkUser = true;
   // protected $uid = 925861;

    /**
     * 我的众筹 列表
     */
    public function cloud_shopping_order() {
        $type = I('get.type', 1, 'intval'); // 1-全部 ； 2-正在进行中；3-已揭晓
        $last_id = I('get.last_id', 0, 'intval');
        $end_id = I('get.end_id', 0, 'intval');

        $where = array(
            'order.user_id' => $this->uid,
            'order.state' => 'pay',
            'order.rstate' => 'normal',
            'order.team_type' => 'cloud_shopping',
        );
        if ($type == 2) {
            $where['_string'] = "csr.status=0 or csr.status is null";
        }
        if ($type == 3) {
            $where['csr.status'] = array('gt', 0);
        }
        if ($last_id && $end_id) {
            $order_where = D('Team')->getMysqlSortWhere('order.pay_time', $last_id, 'order.id', $end_id, '-');
            if ($order_where) {
                if (isset($where['_string']) && trim($where['_string'])) {
                    $order_where = "({$where['_string']}) and $order_where";
                }
                $where['_string'] = $order_where;
            }
        }

        $order = D('Order');
        $order_list = $order->cloud_shopping_order($where, 'order.pay_time desc,order.id desc', $this->reqnum + 1);
        $this->setHasNext(false, $order_list, $this->reqnum);
        $this->outPut(array_values($order_list), 0);
    }

    /**
     * 领奖接口
     */
    public function receive_prize() {
        $order_id = I('post.order_id', '', 'trim');
        $address_id = I('post.address_id', '', 'trim');
        $d_time = I('post.d_time', '', 'trim');

        // 非法数据判断
        if (!$order_id) {
            $this->outPut(null, -1, null, '非法领取奖品！');
        }
        if (!$address_id) {
            $this->outPut(null, -1, null, '请选择地址！');
        }
        if (!$d_time) {
            $this->outPut(null, -1, null, '请选择送货时间！');
        }

        // 点击确认领奖
        $res = D('Order')->confirm_receive_prize($this->uid, $order_id, $address_id, $d_time);
        if (isset($res['error']) && trim($res['error'])) {
            $this->outPut(null, -1, null, trim($res['error']));
        }
        $this->outPut(array(), 0);
    }

    /**
     * 查看我的云购码
     */
    public function view_cloud_shopping_code() {
        $tid = I('get.tid', '', 'trim');
        $pn = I('get.pn', '', 'trim');
        $uid = $this->uid;

        if (!$tid) {
            $this->outPut(null, -1, null, '查看云购码的团单id不能为空！');
        }
        if (!$pn) {
            $this->outPut(null, -1, null, '查看云购码的团单期数不能为空！');
        }

        $where = array(
            'team_id' => $tid,
            'periods_number' => $pn,
            'user_id' => $uid
        );
        $cloud_shoping_code = M('cloud_shoping_code');
        $pay_code_res = $cloud_shoping_code->where($where)->field('cloud_code,create_time,microtime as create_microtime')->select();

        // 获取中奖云购码
        $where = array(
            'team_id' => $tid,
            'periods_number' => $pn,
            'winning_user_id' => $uid,
        );
        $cloud_shoping_result = M('cloud_shoping_result')->where($where)->field('winning_cloud_code')->find();
        $winning_cloud_code = '';
        if (isset($cloud_shoping_result['winning_cloud_code']) && trim($cloud_shoping_result['winning_cloud_code'])) {
            $winning_cloud_code = trim($cloud_shoping_result['winning_cloud_code']);
        }

        if ($pay_code_res) {
            foreach ($pay_code_res as &$v) {
                $v['is_winning'] = 0;
                if (trim($winning_cloud_code) && isset($v['cloud_code']) && trim($v['cloud_code']) == trim($winning_cloud_code)) {
                    $v['is_winning'] = 1;
                }
            }
            unset($v);
        }

        $this->outPut(array_values($pay_code_res), 0);
    }

    /**
     * 领奖页面相关信息获取
     */
    public function receive_prize_info() {
        // 获取送货时间
        $delivery_time = array();
        $mail_team_delivery_time = C('MAIL_TEAM_DELIVERY_TIME');
        if ($mail_team_delivery_time) {
            foreach ($mail_team_delivery_time as $k => $v) {
                $delivery_time[$k] = array('id' => $k, 'name' => $v);
            }
        }

        // 默认地址
        $default_address = array();
        if (isset($this->uid) && trim($this->uid)) {
            $where = array(
                'user_id' => $this->uid,
                'default' => 'Y',
            );
            $default_address = M('address')->where($where)->find();
            if (!$default_address) {
                // 如果没有地址取最新地址
                unset($where['default']);
                $default_address = M('address')->where($where)->order(array('create_time' => 'desc'))->find();
            }
            if ($default_address) {
                $default_address['mobile_hide'] = substr($default_address['mobile'], 0, 4) . '****' . substr($default_address['mobile'], -4, 4);
            }
        }


        // 返回数据
        $data = array(
            'delivery_time' => $delivery_time ? array_values($delivery_time) : array(),
            'default_address' => $default_address ? $default_address : (object) array(),
        );
        $this->outPut($data, 0);
    }

}
