<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/6/18
 * Time: 13:48
 */

namespace Wap\Controller;

/**
 * 用户操作控制器
 * Class UserController
 * @package Wap\Controller
 */
class UserController extends CommonController {

    protected $checkCity = false;

    /**
     * 检测用户是否登录
     * @var bool
     */
    protected $checkUser = true;
    private $order_type = array(
        'UNPAY_INDEX' => '未付款订单',
        'UNREVIEW' => '待评价订单',
        'REVIEWED' => '已评价订单',
        'other' => '我的订单'
    );

    /**
     * 构造方法
     */
    public function __construct() {
        parent:: __construct();
        $user = $this->_getUserInfo();
        $this->assign('user', $user);
    }

    /**
     * @var array
     */
    protected $scoreType = array(
        'binding' => '绑定第三方登陆',
        'comment' => '评价',
        'exchange' => '积分换券',
        'lottery' => '抽奖',
        'pay' => '购买商品',
        'register' => '注册会员',
        'daysign' => '每日签到',
        'refund' => '退款'
    );

    /**
     * 用户中心首页
     */
    public function index() {
        $this->assign('title', '个人中心');
        $this->display();
    }

    /**
     * 用户积分记录
     */
    public function score() {
        $user_id = $this->_getUserId();
        $time = strtotime("2014-08-20");
        $where = array(
            'user_id' => $user_id,
            'create_time' => array('gt', $time)
        );
        $list = M('credit')->where($where)->order('id DESC')->select();
        if ($list) {
            $action = $this->scoreType;
            $teamId = array();
            foreach($list as $row) {
                if(($row['action'] == 'comment' || $row['action'] == 'pay' || $row['action'] == 'refund') && $row['detail_id']) {
                    $teamId[$row['detail_id']] = $row['detail_id'];
                }
            }
            $team = array();
            if(!empty($teamId)) {
                $team = M('Team')->where(array('id'=>array('in',  array_keys($teamId))))->getField('id,product', TRUE);
                $this->_writeDBErrorLog($team, M('Team'), 'api');
            }
            foreach ($list as $key => $val) {
                if ($val['score'] > 0) {
                    if ($val['action'] == 'comment' || $val['action'] == 'pay' || $val['action'] == 'refund') {
                        $val['detail'] = $action[$val['action']] . '-' . ternary($team[$val['detail_id']], '');
                    } else if ($val['action'] == 'lottery') {
                        $val['detail'] = $action[$val['action']] . '获取';
                    } else {
                        $val['detail'] = $action[$val['action']];
                    }
                    $val['score'] = '+' . $val['score'];
                } else {
                    $val['detail'] = $action[$val['action']] . '消耗';
                }
                $list[$key] = $val;
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 用户成长值
     */
    public function growth() {
        $user_id = $this->_getUserId();
        $where = array(
            'buy' => array('user_id' => $user_id, 'action' => 'buy'),
            'refund' => array('user_id' => $user_id, 'action' => 'refund')
        );
        foreach ($where as $key => $val) {
            $money = M('flow')->where($val)->sum('money');
            if ($key == 'buy') {
                $buy_money_num = $money;
            } else {
                $refund_money_num = $money;
            }
        }
        $growth = ceil($buy_money_num - $refund_money_num);
        $this->assign('growth', $growth);
        $this->display();
    }

    /**
     * 退款查询
     */
    public function selectRefund() {
        $user_id = $this->_getUserId();
        $Model = D('Order');
        $where = array('user_id' => $user_id, 'rstate' => array('neq', 'normal'));
        $order = $Model->getList($where, 'retime desc');
        foreach ($order as &$val) {
            $team = M('team')->find($val['team_id']);
            $val['image'] = getImagePath($team['image']);
            $val['product'] = $team['product'];
        }
        $this->assign('orders', $order);
        $this->display();
    }

    /**
     * 用户账户设置首页
     */
    public function editUser() {
        $this->assign('title', '修改用户新信息');
        $this->display();
    }

    /**
     * 修改用户名
     */
    public function editName() {
        $this->assign('title', '修改用户名');
        $this->display();
    }

    /**
     * 修改绑定手机
     */
    public function editMobile() {
        $this->assign('title', '修改绑定手机');
        $this->display();
    }

    /**
     * 修改密码
     */
    public function editPwd() {
        $user = M('user')->find(C('MEMBER_AUTH_KEY'));
        if ($user['password']) {
            $this->assign('title', '修改密码');
        } else {
            $this->assign('title', '设置密码');
        }
        $this->display();
    }

    /**
     * 更新用户信息
     */
    public function updateUser() {
        $act = I('post.act');
        switch ($act) {
            case 'editPwd':
                $data = $this->_editPwd();
                break;
            case 'editName':
                $data = $this->_editName();
                break;
            case 'editMobile':
                $data = $this->_editMobile();
                break;
            default :
                $data = getPromptMessage('非法请求');
                break;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 验证用户信息并修改
     */
    protected function _editName() {
        $user_id = $this->_getUserId();
        $Model = D('User');
        $value = I('post.value');
        $where = array("id <> {$user_id}", 'username' => $value);
        $count = $Model->getTotal($where);
        if ($count) {
            return getPromptMessage("用户名{$value}已经存在");
        } else {
            $up_data = array('id' => $user_id, 'username' => $value);
            $res = $Model->save($up_data);
            if ($res) {
                return getPromptMessage("修改成功", 'success', 1);
            } else {
                return getPromptMessage($Model->getError());
            }
        }
    }

    /**
     * 验证密码并修改
     */
    protected function _editPwd() {
        $user_id = $this->_getUserId();
        $Model = D('User');
        $value = I('post.value');
        if (isset($_POST['oldPwd'])) {
            $oldPwd = encryptPwd(I('post.oldPwd'));
            $userInfo = $this->_getUserInfo();
            if ($oldPwd != $userInfo['password']) {
                $data = getPromptMessage('原始密码错误');
            }
        }
        if (isset($data)) {
            return $data;
        } else {
            if (strlen($value) < 6 || strlen($value) > 18) {
                return getPromptMessage('密码长度必须为6-18位');
            } else {
                $up_data = array('id' => $user_id, 'password' => encryptPwd($value));
                $res = $Model->save($up_data);
                if ($res) {
                    return getPromptMessage("修改成功", 'success', 1);
                } else {
                    return getPromptMessage($Model->getError());
                }
            }
        }
    }

    /**
     * 验证手机号码并修改
     */
    protected function _editMobile() {
        $user_id = $this->_getUserId();
        $value = I('post.value');
        $Model = D('User');
        if (!checkMobile($value)) {
            $this->ajaxReturn(getPromptMessage('手机号码格式不正确'));
        }
        if ($this->_checkCode() === false) {
            $this->ajaxReturn(getPromptMessage('验证码错误'));
        }
        $up_data = array('id' => $user_id, 'mobile' => $value);
        $res = $Model->save($up_data);
        if ($res) {
            return getPromptMessage("修改成功", 'success', 1);
        } else {
            return getPromptMessage($Model->getError());
        }
    }

    /**
     * 检测验证码
     */
    protected function _checkCode($action = 'changemobile') {
        $code = I('post.code', 0, 'intval');
        $mobile = I('post.mobile', '', 'trim');
        $where = array('code' => $code, 'mobile' => $mobile, 'action' => $action, 'date' => date('Y-m-d'));
        $count = D('Sms')->getTotal($where);
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 签到
     */
    public function daySign() {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if(!strpos($agent,"icroMessenger")) {
            echo '请求非法';exit;
        }
        $this->assign('title', '签到');
        $this->display();
    }

    /**
     * 签到处理
     */
    public function doDaySign() {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if(!strpos($agent,"icroMessenger")) {
            echo '请求非法';exit;
        }
        $user_id = $this->_getUserId();
        $Model = D('User');
        $daySign = M('daysign');
        $daytime = strtotime(date('Y-m-d'));
        $condition['user_id'] = $user_id;
        $condition['create_time'] = $daytime;
        $count = $daySign->where($condition)->count('id');
        if ($count) {
            $data = getPromptMessage('您今天已经签过到了，请明天再来吧!');
        } else {
            $res = $Model->daySign($user_id);
            if ($res) {
                $data = getPromptMessage('签到成功', 'success', 1);
            } else {
                $this->_writeDBErrorLog($res, $Model);
                $data = getPromptMessage('签到失败!');
            }
        }
        $this->ajaxReturn($data);
    }

    /**
     * 异步获取未付款数量
     */
    public function getUnReViewNum() {
        $user_id = $this->_getUserId();
        $Model = M('comment');
        $where = array('user_id' => $user_id, 'is_comment' => 'N', 'consume' => 'Y');
        $count = $Model->where($where)->count('id');
        $data = getPromptMessage($count, 'success', 1);
        $this->ajaxReturn($data);
    }

    /**
     * 获取订单列表
     */
    public function getOrderList() {

        $state = I('get.state', '', 'trim');

        $order = D('Order');
        $where = $this->setPage('id');
        $limit = $this->reqnum;
        $sort = $this->sort;
        $this->uid = $this->_getUserId();
        $_status = strtoupper(trim($state));

        $order_title = $this->order_type['other'];
        $order_tab_is_show = '1';
        if ($this->order_type[$_status]) {
            $order_tab_is_show = '0';
            $order_title = $this->order_type[$_status];
        }

        $data = array();
        $common_where = " AND is_display='Y' AND team_type<>'cloud_shopping'";
        $field = 'id,team_id,state,rstate,price,quantity,create_time,allowrefund,origin,mail_order_pay_state,allowrefund,pay_time,retime,is_display';
        switch ($_status) {
            case 'UNPAY':   //未付款
            case 'UNPAY_INDEX':
                $where['_string'] = "user_id={$this->uid} && state='unpay' && rstate='normal' {$common_where}";
                $data = $order->getList($where, $sort, $limit, $field);
                break;
            case 'UNUSE':   //未使用
                $where['_string'] = "user_id={$this->uid} && state='pay' && rstate='normal' {$common_where}";
                $data = $order->getIsUseOrders($this->uid, 'N', $where, $sort, $limit, $field);
                break;
            case 'USED':    //已使用
                $where['_string'] = "user_id={$this->uid} && state='pay' && rstate='normal' {$common_where}";
                $data = $order->getIsUseOrders($this->uid, 'Y', $where, $sort, $limit, $field);
                break;
            case 'UNREVIEW':    //未评价
                $where['_string'] = "user_id={$this->uid} && state='pay' && rstate='normal' {$common_where}";
                $data = $order->getIsReviewOrders($this->uid, 'N', $where, $sort, $limit, $field);
                break;
            case 'REVIEWED':    //已评价
                $where['_string'] = "user_id={$this->uid} && state='pay' && rstate='normal' {$common_where}";
                $data = $order->getIsReviewOrders($this->uid, 'Y', $where, $sort, $limit, $field);
                break;
            case 'REFUND':  //退款
                $where['_string'] = "user_id={$this->uid} {$common_where} && ((state='pay' && rstate='askrefund') OR (state='unpay' && rstate='berefund'))";
                $data = $order->getList($where, $sort, $limit, $field);
                break;
            default:
                $where['_string'] = "user_id={$this->uid} {$common_where}";
                $data = $order->getAllOrders($where, $sort, $limit, $field);
                break;
        }


        $teamModel = D('Team');
        $team = $teamModel->getOrderTeam($data);
        //数据组装
        foreach ($data as &$val) {
            $val['title'] = $team[$val['team_id']]['title'];
            $val['product'] = $team[$val['team_id']]['product'];
            $val['end_time'] = $team[$val['team_id']]['end_time'];
            $val['image'] = getImagePath($team[$val['team_id']]['image']);
            // 针对未使用的订单
            if ($state == '' || $state == 'UNUSE') {
                $val['refund_money'] = sprintf('%.2f', $val['unuse_num'] * $val['price']);
                unset($val['use_num'], $val['unuse_num']);
            }
            $val['status'] = ternary($val['status'], strtolower($state));
            // 是否邮购
            $val['is_goods'] = 'N';
            if (isset($team[$val['team_id']]['team_type']) && trim($team[$val['team_id']]['team_type']) == 'goods') {
                $val['is_goods'] = 'Y';
            }
        }
        unset($val);

        // lastData
        $end_last_data = end($data);
        $_data = array(
            'order_tab_is_show' => $order_tab_is_show,
            'order_title' => $order_title,
            'orders' => $data,
            'last_data' => array(
                'last_count' => count($data),
                'last_id' => ternary($end_last_data['id'], ''),
            )
        );
        $this->assign($_data);
        $this->display();
    }

    /**
     * 订单详情
     */
    function orderDetail() {
        $id = I('get.id', '', 'trim');
        $Order = M('order');
        $Team = M('team');
        $Coupon = M('coupon');

        $order = $Order->find($id);
        $comment = M('comment')->where(array('order_id' => $id))->find();
        $team = $Team->find($order['team_id']);
        $team['image'] = getImagePath($team['image']);
        $coupons = $Coupon->where('order_id=' . $order['id'])->select();
        $ccount = $Coupon->where('order_id=' . $order['id'] . ' and consume="N"')->count();
        $rmsg = "";
        $order['status'] =getUserOrderState($order);
         $order['view_logistics'] ='';
        if (isset($team['team_type']) && trim($team['team_type']) == 'goods') {
            $order['pay_detail'] = "总共购买{$order['quantity']}份";
            $order_optional_model = @json_decode(ternary($order['optional_model'], ''), true);
            if ($order_optional_model) {
                $oom_str = '';
                foreach ($order_optional_model as $oom) {
                    $oom_str .= "{$oom['name']} X {$oom['num']}份; ";
                }
                $order['pay_detail'] = "{$order['pay_detail']}; $oom_str";
            }

            if (isset($order['state']) && $order['state'] == 'pay') {
                switch (trim($order['rstate'])) {
                    case 'askrefund':
                        $rmsg = "<a href='" . U('User/doOrderDetail', array('id' => $order['id'], 'type' => 'refund_detail')) . "' class='btn btn-danger btn-lg btn-block'>退款审核中</a>";
                        break;
                    case 'norefund':
                        $rmsg = "<a  href='javascript:;' class='btn btn-danger btn-lg btn-block'>审核未通过</a>";
                        break;
                    default:
                        switch (trim($order['mail_order_pay_state'])) {
                            case 1:
                                $order['view_logistics']= "<a href='" . U('User/doOrderDetail', array('id' => $order['id'], 'type' => 'logistics_view')) . "'>查看物流</a>";
                                $rmsg= "<a href='" . U('User/doOrderDetail', array('id' => $order['id'], 'type' => 'confirm_receipt')) . "' tip_message='确认要确定收货吗？' class='btn btn-danger btn-lg btn-block btn_ajax_post'>确认收货</a>";
                                break;
                            case 2:
                                $order['view_logistics'] = "<a href='" . U('User/doOrderDetail', array('id' => $order['id'], 'type' => 'logistics_view')) . "' >查看物流</a>";

                                // 是否显示评价
                                if ($comment['is_comment'] == 'N') {
                                    if ($comment['consume'] == 'Y') {
                                        $rmsg .= "<a href='" . U('User/doOrderDetail', array('id' => $order['id'], 'type' => 'review')) . "' class='btn btn-danger btn-lg btn-block'>评价</a>";
                                    }
                                } else {
                                   // $rmsg .= "<a href='javascript:;' class='btn btn-danger btn-lg btn-block'>已评价</a>";
                                }
                                break;
                            default:
                                //$rmsg = "<a  href='javascript:;' class='btn btn-danger btn-lg btn-block'>商家未发货</a>";
                                break;
                        }
                        break;
                }
                // 是否显示退款
                if ($order['state'] == 'pay' && $order['rstate'] == 'normal') {
                    if ($order['allowrefund'] == 'Y') {
                        // 商家结算的不能申请退款
                        $rmsg .= "<a href='" . U('User/doOrderDetail', array('id' => $order['id'], 'type' => 'refund')) . "' class='btn btn-default btn-lg btn-block'>申请退款</a>";
                        
                    } else {
                        $rmsg .= "<a  class='btn btn-default btn-lg btn-block'>不支持退款</a>";
                    }
                }
            } else {
                if ($team['end_time'] > time()) {
                    if ($order['rstate'] == 'normal') {
                      $rmsg = "<a href='" . U('Team/buy', array('oid' => $order['id'])) . "' class='btn btn-danger btn-lg btn-block'>去付款</a>";
                    }
                     switch (trim($order['mail_order_pay_state'])) {
                            case 1:
                            case 2:
                                $order['view_logistics'] = "<a href='" . U('User/doOrderDetail', array('id' => $order['id'], 'type' => 'logistics_view')) . "' >查看物流</a>";
                                break;
                        }
                } else {
                    $rmsg = "<a href='javascript:;' class='btn btn-danger btn-lg btn-block'>已过期</a>";
                }
            }
            
        } else {
            if ($order['state'] == 'pay') {
                if ($order['rstate'] == 'askrefund') {
                    $rmsg = "<a href='" . U('User/doOrderDetail', array('id' => $order['id'], 'type' => 'refund_detail')) . "' class='btn btn-danger btn-lg btn-block'>退款审核中</a>";
                } else if ($order['rstate'] == 'norefund') {
                    $rmsg = "<a  href='javascript:;' class='btn btn-danger btn-lg btn-block'>审核未通过</a>";
                }
                if (!trim($rmsg)) {
                    if ($ccount <= 0) {
                        if ($comment['is_comment'] == 'N') {
                            if ($comment['consume'] == 'N') {
                                $rmsg = "<a href='javascript:;' class='btn btn-danger btn-lg btn-block'>该订单有未消费青团券不能评价</a>";
                            } else {
                                $rmsg = "<a href='" . U('User/doOrderDetail', array('id' => $order['id'], 'type' => 'review')) . "' class='btn btn-danger btn-lg btn-block'>评价</a>";
                            }
                        } else if ($comment['is_comment'] == 'Y') {
                            $rmsg = "<a href='javascript:;' class='btn btn-danger btn-lg btn-block'>已评价</a>";
                        } else {
                            $rmsg = "<a href='javascript:;' class='btn btn-danger btn-lg btn-block'>系统升级导致该订单不能评价</a>";
                        }
                    } else {
                        if ($order['allowrefund'] == 'Y') {
                            $rmsg = "<a href='" . U('User/doOrderDetail', array('id' => $order['id'], 'type' => 'refund')) . "' class='btn btn-danger btn-lg btn-block'>申请退款</a>";
                        } else {
                            $rmsg = "<a  class='btn btn-danger btn-lg btn-block'>不支持退款</a>";
                        }
                    }
                }
            } else {
                if ($team['end_time'] > time()) {
                    if ($order['rstate'] == 'berefund') {
                        $rmsg = "<a href='" . U('Team/buy', array('oid' => $order['id'])) . "' class='btn btn-danger btn-lg btn-block'>已退款重新购买</a>";
                    } else {
                        $rmsg = "<a href='" . U('Team/buy', array('oid' => $order['id'])) . "' class='btn btn-danger btn-lg btn-block'>去付款</a>";
                    }
                } else {
                    $rmsg = "<a href='javascript:;' class='btn btn-danger btn-lg btn-block'>已过期</a>";
                }
            }
        }


        $data = array(
            'team' => $team,
            'order' => $order,
            'coupons' => $coupons,
            'rmsg' => $rmsg,
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 处理订单详情点击
     */
    public function doOrderDetail() {
        $type = I('get.type', '', 'trim');
        $oid = I('get.id', '', 'trim');
        if (!$type || !$oid) {
            redirect(U('User/index'));
        }
        $uid = $this->_getUserId();
        $order = M('order');
        $coupon = M('coupon');
        $comment = M('comment');
        $user = M('user');
        $type = strtolower($type);
        switch ($type) {
            case 'review':
                // 评论页面显示
                $this->assign('oid', $oid);
                $this->display('User/review');
                exit;
                break;
            case 'do_review':
                // 评价动作
                $score = I('post.score', '', 'trim');
                $content = I('post.content', '', 'trim');
                if (!$score || !$content) {
                    $this->ajaxReturn(array('code' => '-1', 'error' => '亲！请打分或输入评价内容后再进行发表评论!'));
                }
                $where = array('is_comment' => 'N', 'user_id' => $uid, 'order_id' => $oid);
                $is_comment = $comment->where($where)->find();
                if (!$is_comment) {
                    $this->ajaxReturn(array('code' => '-1', 'error' => '亲！您的请求不合法'));
                }
                $now_time = time();
                $data = array(
                    'content' => $content,
                    'comment_num' => $score,
                    'is_comment' => 'Y',
                    'create_time' => $now_time,
                );
                $model = M();
                $model->startTrans();
                $res = $comment->where($where)->save($data);
                if (!$res) {
                    $model->rollback();
                    $this->ajaxReturn(array('code' => '-1', 'error' => '亲！您的评论提交失败'));
                }
                $orderRes = $order->where(array('id' => $oid))->field('origin,team_id')->find();
                $money = ceil(ternary($orderRes['origin'], '0'));
                $userRes = $user->where(array('id' => $uid))->field('score')->find();
                $all_score = ternary($userRes['score'], 0) + $money;

                // 更新用户积分
                $res = $user->where(array('id' => $uid))->save(array('score' => $all_score));
                if (!$res) {
                    $model->rollback();
                    $this->ajaxReturn(array('code' => '-1', 'error' => '亲！您的评论提交失败'));
                }
                $scoredata = array(
                    'create_time' => $now_time,
                    'user_id' => $uid,
                    'score' => $money,
                    'action' => 'comment',
                    'detail_id' => ternary($orderRes['team_id'], 0),
                    'sumscore' => $all_score
                );
                $res = M('credit')->add($scoredata);
                if (!$res) {
                    $model->rollback();
                    $this->ajaxReturn(array('code' => '-1', 'error' => '亲！您的评论提交失败'));
                }
                $model->commit();
                $this->ajaxReturn(array('code' => '0', 'url' => U('User/index')));
                break;
            case 'refund':
                // 申请退款
                $field = "id,origin,team_id,fare,express,price";
                $orderRes = $order->where(array('id' => $oid))->field($field)->find();
                if (!$orderRes || !isset($orderRes['team_id'])) {
                    redirect(U('User/index'));
                }
                $num = $coupon->where(array('order_id'=>$oid,'consume'=>array('neq','Y')))->count();
                $orderRes['refund_money'] = sprintf("%.2f",$num * $orderRes['price']);
                if(isset($orderRes['express']) && trim($orderRes['express'])=='Y'){
                    $orderRes['refund_money'] = sprintf("%.2f",$orderRes['origin']-$orderRes['fare']);
                }
                
                $field = "id,consume,expire_time";
                $couponRes = $coupon->where(array('order_id' => $oid))->field($field)->select();
                $data = array(
                    'order' => $orderRes,
                    'coupon' => $couponRes,
                );
                
                $this->assign($data);
                $this->display('User/refund');
                exit;
                break;
            case 'refund_detail':
                // 退款详情
                $field = "id,retime,tn";
                $orderRes = $order->where(array('id' => $oid))->field($field)->find();
                $this->assign('order', $orderRes);
                $this->display('User/refund_detail');
                exit;
                break;
            case 'do_refund':
                $rereason = I('post.rereason', array(), '');
                $other_rereason = I('post.oreason', array(), '');
                $refrom = I('post.refrom', array(), '');

                $order = D('Order');
                $res = $order->checkIsRefund($oid);
                if (isset($res['error'])) {
                    $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
                }

                if (in_array('other', $rereason)) {
                    array_pop($rereason);
                }
                $direction = '原路退';
                if ($refrom == 1) {
                    $direction = '退至青团余额';
                }
                if (!trim(implode('', $rereason) . $other_rereason)) {
                    $this->ajaxReturn(array('code' => '-1', 'error' => '亲！退款原因不能为空！'));
                }

                $reasonstr = implode(' | ', $rereason) . ' | ' . $other_rereason;

                $data = array(
                    'rereason' => $reasonstr,
                    'allowrefund' => 'Y',
                    'rstate' => 'askrefund',
                    'retime' => time(),
                    'tn' => $direction,
                );
                $res = $order->where(array('id' => $oid))->save($data);
                if (!$res) {
                    $this->ajaxReturn(array('code' => '-1', 'error' => '亲！退款申请失败！'));
                }
                $this->ajaxReturn(array('code' => '0', 'url' => U('User/orderDetail', array('id' => $oid))));
                // 退款动作
                break;
            case 'cancel_refund':
                // 取消退款
                $res = $order->where(array('id' => $oid))->save(array('rstate' => 'normal'));
                if (!$res) {
                    $this->ajaxReturn(array('code' => '-1', 'error' => '亲！您取消失败！'));
                }
                $this->ajaxReturn(array('code' => '0', 'url' => U('User/getOrderList', array('_t' => 'all'))));
                break;
            case 'confirm_receipt':
                $order = D('Order');
                $res = $order->orderConfirmReceipt($oid, $uid);
                if (isset($res['error']) && trim($res['error'])) {
                    $this->ajaxReturn(array('code' => '-1', 'error' => $res['error']));
                }
                $this->ajaxReturn(array('code' => 0));
                break;
            case 'logistics_view':
                $where = array(
                    'id' => $oid,
                    'rstate' => 'normal', //
                );
                $order_res = M('order')->where($where)->field('user_id,state,mail_order_pay_state,team_id,express_id,express_no')->find();
                if (!$order_res) {
                    redirect(U('User/index'));
                }
                if (!isset($order_res['user_id']) || intval($order_res['user_id']) !== intval($uid)) {
                    redirect(U('User/index'));
                }
                if (!isset($order_res['state']) || trim($order_res['state']) != 'pay') {
                    redirect(U('User/index'));
                }
                if (!isset($order_res['express_id']) || !trim($order_res['express_id'])) {
                    redirect(U('User/index'));
                }
                if (!isset($order_res['express_no']) || !trim($order_res['express_no'])) {
                    redirect(U('User/index'));
                }
                if (!isset($order_res['mail_order_pay_state']) || intval($order_res['mail_order_pay_state']) < 1) {
                    redirect(U('User/index'));
                }

                $express_res = $this->_getCategoryList('express');
                $type = ternary($express_res[$order_res['express_id']]['ename'], '');
                $express_query = new \Common\Org\ExpressQuery();
                $data = array();
                $res = $express_query->express_query($type, $order_res['express_no']);
                if (isset($res['status']) && $res['status'] == 200 && isset($res['data'])) {
                    $data = $res['data'];
                }

                $r_data = array(
                    'order' => $order_res,
                    'express_name' => ternary($express_res[$order_res['express_id']]['name'], ''),
                    'express_no' => ternary($order_res['express_no'], ''),
                    'list' => $data
                );
                $this->assign($r_data);
                $this->display('User/logistics_view');
                break;
            default:
                redirect(U('User/index'));
                break;
        }
    }

    /**
     * 地址列表
     */
    public function addressList() {

        $uid = $this->_getUserId();

        $where = array('user_id' => $uid);
        $address_list = M('address')->where($where)->select();
        // 整理数据
        if ($address_list) {
            foreach ($address_list as &$v) {
                unset($v['user_id']);
                $v['mobile_hide'] = substr($v['mobile'], 0, 4) . '****' . substr($v['mobile'], -4, 4);
            }
            unset($v);
        }

        $data = array(
            'address_list' => $address_list,
        );

        $this->assign($data);
        $this->display('User/addressList');
    }

    /**
     * 添加地址
     */
    public function addAddress() {

        if (IS_POST) {
            $uid = $this->_getUserId();
            $address_data = I('post.address', array(), '');
            $res = D('Address')->addUserAddress($uid, $address_data);
            if (isset($res['error']) && trim($res['error'])) {
                $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
            }
            $this->ajaxReturn(array('code' => 0, 'redirect_url' => U('User/addressList')));
        } else {
            $this->assign('post_url', 'addAddress');
            $this->assign('opt', '添加');
            $this->display('User/addEditAddress');
        }
    }

    /**
     * 编辑地址
     */
    public function editAddress() {
        if (IS_POST) {
            $address_data = I('post.address', array(), '');
            $address_id = I('post.address_id', array(), '');
            $uid = $this->_getUserId();
            $res = D('Address')->editUserAddress($uid, array_merge($address_data, array('address_id' => $address_id)));
            if (isset($res['error']) && trim($res['error'])) {
                $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
            }
            $this->ajaxReturn(array('code' => 0, 'redirect_url' => U('User/addressList')));
        } else {
            $address_id = I('get.address_id', '', 'trim');
            if (!$address_id) {
                redirect(U('Member/addressList', array('error' => base64_encode('修改的地址id不存在！'))));
            }
            $addres_res = M('address')->where(array('id' => $address_id))->find();
            $this->assign('post_url', 'editAddress');
            $this->assign('address', $addres_res);
            $this->assign('opt', '编辑');
            $this->display('User/addEditAddress');
        }
    }

    /**
     * 删除地址
     */
    public function deleteAddress() {
        $address_id = I('get.address_id', '', 'trim');
        $uid = $this->_getUserId();
        $res = D('Address')->deleteUserAddress($uid, $address_id);
        if (isset($res['error']) && trim($res['error'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
        }
        $this->ajaxReturn(array('code' => 0, 'redirect_url' => U('User/addressList')));
    }

}
