<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-06-08
 * Time: 09:51
 */

namespace Admin\Controller;

class CouponController extends CommonController {

    /**
     * 未消费
     */
    public function index() {
        $where = $this->_getSearchWhere();
        $where['consume'] = 'N';
        $this->_getCouponList($where);
        $this->display();
    }

    /**
     * 已使用
     */
    public function used() {
        $where = $this->_getSearchWhere();
        $where['consume'] = 'Y';
        $this->_getCouponList($where);
        $this->display();
    }

    /**
     * 已过期
     */
    public function expired() {
        $where = $this->_getSearchWhere();
        $where['expire_time'] = array('LT', time());
        $where['consume'] = 'N';
        $this->_getCouponList($where);
        $this->display();
    }

    /**
     * 已退款
     */
    public function refund() {
        $where = $this->_getSearchWhere();

        foreach($where as $key=>$row) {
            $where['c.' . $key] = $row;
            unset($where[$key]);
        }

        $where['c.operation_type'] = 'refund';
        $model = M('CouponDelete');
        $total = $model->alias('c')->join('`order` o on c.order_id=o.id')->where($where)->count();
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = $model->alias('c')->field('c.*,o.refund_etime')->where($where)->join('`order` o on c.order_id=o.id')->order('o.refund_etime DESC')->limit($limit)->select();
        $team = D('Team')->getOrderTeam($list);
        $user = $this->_getCouponUser($list);
        $this->assign('team', $team);
        $this->assign('list', $list);
        $this->assign('userList', $user);
        $this->assign('pages', $page->show());
        $this->display();
    }

    /**
     * 获取退款时间
     * @param $list
     * @return array|mixed
     */
    protected function _getRefundTime($list) {
        $order_id = array();
        foreach($list as $row) {
            $order_id[] = $row['order_id'];
        }
        if(empty($order_id)) {
            return array();
        }
        $map = array(
            'id' => array('IN', array_unique($order_id))
        );
        $refund_time = M('Order')->where($map)->getField('id,refund_etime');
        return $refund_time;
    }

    /**
     * 获取券号列表
     * @param $where
     */
    protected function _getCouponList($where) {
        $model = D('Coupon');
        $total = $model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = $model->getList($where, 'create_time DESC', $limit);
        $team = D('Team')->getOrderTeam($list);
        $user = $this->_getCouponUser($list);
        $mobile = $this->_getOrderMobile($list);
        $this->assign('mobileList', $mobile);
        $this->assign('team', $team);
        $this->assign('list', $list);
        $this->assign('userList', $user);
        $this->assign('pages', $page->show());
    }

    /**
     * 获取搜索条件
     */
    protected function _getSearchWhere() {
        $where = array(
            array('order_id', ''),
            array('team_id', '')
        );
        $map = $this->createSearchWhere($where);
        $searchValue = $this->getSearchParam($where);
        $userId = I('get.user_id');
        $id = I('get.id', '', 'trim');
        if($id) {
            $map['id'] = array('like', $id . '%');
            $searchValue['id'] = $id;
        }
        if (!empty($userId)) {
            $condition = array(
                'username|mobile' => array('like', '%' . $userId . '%')
            );
            $uid = M('User')->where($condition)->getField('id', true);
            if ($uid) {
                $map['user_id'] = array('IN', $uid);
            }
            $searchValue['user_id'] = urldecode($userId);
        }
        $this->assign('searchValue', $searchValue);
        if(isset($uid) && !$uid) {
            $this->display();
            exit();
        }
        return $map;
    }

    /**
     * 获取订单用户信息
     * @param $list
     * @return array|mixed
     */
    protected function _getCouponUser($list) {
        if (empty($list))
            return array();
        $user = array();
        foreach ($list as $row) {
            $user[] = $row['user_id'];
        }
        $map = array(
            'id' => array('IN', array_unique($user))
        );
        $user = M('User')->where($map)->getField('id,username,mobile', true);
        $this->_writeDBErrorLog($user, M('User'), 'admin');
        return $user;
    }

    /**
     * 获取券号对应订单手机号
     * @param $list
     * @return mixed
     */
    protected function _getOrderMobile($list) {
        if (empty($list))
            return array();
        $order = array();
        foreach ($list as $row) {
            $order[] = $row['order_id'];
        }
        $map = array(
            'id' => array('IN', array_unique($order))
        );
        $user = M('Order')->where($map)->getField('id,mobile', true);
        $this->_writeDBErrorLog($user, M('Order'), 'admin');
        return $user;
    }

    /**
     * 重发券号
     */
    public function sendAgain() {
        if (IS_AJAX) {
            $this->_checkblank('id');
            $id = I('get.id', '', 'trim');
            $coupon = D('Coupon')->info($id);
            if (empty($coupon)) {
                $this->_writeDBErrorLog($coupon, D('Coupon'), 'admin');
                $this->error('券号不存在');
            }
            if ($coupon['consume'] == 'Y') {
                $this->error('该券号已使用');
            }
            $model = D('Order');
            $order = $model->isExistOrder($coupon['order_id']);
            if (empty($order)) {
                $this->_writeDBErrorLog($order, $model, 'admin');
                $this->error('订单不存在');
            }
            if (!checkMobile($order['mobile'])) {
                $this->error('手机号码格式不正确');
            }
            if ($coupon['sms'] > C('SMS_DAY_COUNT')) {
                $this->error('短信发送次数已超过' . C('SMS_DAY_COUNT') . '次，无法发送！');
            }

            $leftTime = $coupon['sms_time'] + C('SMS_TIME_OUT') - time();
            if ($leftTime > 0) {
                $this->error("请在{$leftTime}秒后，再次尝试短信发送优惠券");
            }

            if ($order['state'] == 'pay') {
                if ($order['rstate'] == 'askrefund') {
                    $this->error('订单已申请退款，无法发送短信');
                }
            } else {
                if ($order['rstate'] == 'normal') {
                    $this->error('订单未支付，请支付');
                } else if ($order['rstate'] == 'berefund') {
                    $this->error('订单已退款，无法发送短信');
                }
            }
            $teamModel = D('Team');
            $team = $teamModel->info($order['team_id'], 'id,product,expire_time');
            $sendSms = new \Common\Org\sendSms();
            $partnerMobile = M('Partner')->where('id=' . $order['partner_id'])->getField('phone');
            $etime = date("Y-m-d", $coupon["expire_time"]);
            $content = "《{$team['product']}》{$coupon['quantity']}份，券号{$id}，有效期{$etime}，商家电话{$partnerMobile}";
            $res = $sendSms->sendMsg($order['mobile'], $content);
            if ($res['status'] == 1) {
                //更新发送信息
                $data = array(
                    'sms' => $coupon['sms'] + 1,
                    'sms_time' => time()
                );
                $map = array(
                    'id' => $id
                );
                M('Coupon')->where($map)->save($data);
                $this->addOperationLogs("操作：重发券号,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},券号id:{$id}");
                $this->success('短信发送成功');
            } else {
                $this->error('短信发送失败');
            }
        } else {
            $this->error('非法请求');
        }
    }

    /**
     * 券号撤销
     */
    public function undoCoupon() {
        $this->_checkblank(array('id', 'oid'));
        $id = I('param.id', '', 'trim');
        $oid = I('param.oid', 0, 'intval');

        $map = array(
            'order_id' => $oid,
            'id' => array('IN', $id)
        );
        $coupon = M('Coupon')->index('id')->where($map)->select();
        if (empty($coupon)) {
            $this->_writeDBErrorLog($coupon, D('Coupon'), 'admin');
            $this->error('券号不存在');
        }
        $str = '';
        foreach ($coupon as $row) {
            if ($row['consume'] == 'N') {
                $str .= $row['id'] . '券号未消费，无法撤销<br/>';
            }
        }
        if ($str) {
            $this->error($str);
        }
        $res = D('Coupon')->undoCoupon($coupon, $oid);
        if(isset($res['error'])) {
            $this->error($res['error']);
            exit();
        }
        if ($res) {
            $this->addOperationLogs("操作：券号撤销,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},券号id:{$id}");
            $this->success('撤销成功');
        } else {
            $this->error('撤销失败');
        }
    }

    /**
     * 删除券号
     */
    public function delCoupon() {
        $this->_checkblank(array('id', 'oid'));
        $id = I('param.id', '', 'trim');
        $oid = I('param.oid', 0, 'intval');

        $map = array(
            'order_id' => $oid,
            'id' => array('IN', $id)
        );
        $coupon = M('Coupon')->where($map)->select();
        if (empty($coupon)) {
            $this->_writeDBErrorLog($coupon, D('Coupon'), 'admin');
            $this->error('券号不存在');
        }

        $str = '';
        foreach ($coupon as $row) {
            if ($row['consume'] == 'Y') {
                $str .= $row['id'] . '券号已消费，无法删除<br/>';
            }
        }
        if ($str) {
            $this->error($str);
        }
        $idList = explode(',', $id);
        $res = D('Coupon')->delCoupon($idList, $coupon, $oid);
        if ($res === true) {
            $this->addOperationLogs("操作：删除券号,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},券号id:{$id}");
            $this->success('删除成功');
        } else {
            $this->error($res['error']);
        }
    }

    /**
     * 未使用券号下载
     */
    public function unUseCouponDown(){
        $team_id = I('get.team_id',0,'trim');
        if(!$team_id){
            $this->redirect_message(U('Coupon/index'),  array('error' => base64_encode('请输入项目号再下载！')));
        }
        $where = array('c.team_id'=>array('in',$team_id),'c.consume'=>'N');
        $Model = M('coupon');
        $filed = 'c.id,c.team_id,c.order_id,c.user_id,o.mobile,t.product';
        $data  = $Model->alias('c')
                       ->join('left join `order` o ON c.order_id = o.id')
                       ->join('left join team t ON c.team_id = t.id')
                       ->field($filed)
                       ->where($where)
                       ->order('c.order_id desc')
                       ->select();
        if($data){
            $this->_createDownData($data);
        }else{
            $this->redirect_message(U('Coupon/index'),  array('error' => base64_encode('未找到可下载的数据，请核对您的搜索条件！')));
        }
    }

    /**
     * 未消费信息下载组装
     * @param $data
     */
    protected function _createDownData($data){
        foreach($data as &$val){
            $val['id'] = "'".$val['id'];
        }
        $dataName = array(
            'id'       => '青团券号',
            'order_id' => '订单编号',
            'team_id'  => '团单编号',
            'user_id'  => '用户编号',
            'mobile'   => '用户手机号',
            'product'  => '团单标题'
        );
        download_xls($data, $dataName, date('Y-m-d').'-'.'未消费券号');
    }
}
