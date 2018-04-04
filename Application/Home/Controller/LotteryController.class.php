<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/6/23
 * Time: 18:08
 */

namespace Home\Controller;


class LotteryController extends CommonController {

    /**
     * @var bool 是否验证city
     */
    protected $cityCheck = false;

    /**
     * @var bool 检测来源是否为手机
     */
    protected $fromCheck = false;

    protected $cartType = array(
        1 => '国产系列',
        2 => '韩系',
        3 => '日系',
        4 => '欧系',
        5 => '美系',
        6 => '德系',
    );
    /**
     * 青团抽奖处理
     */
    public function index() {
        $this->display();
    }

    /**
     * 抽奖发送指令返回msg值判断是否中奖
     */
    public function ajaxGetBonus() {
        $money = rand(1, 3);
        $msg   = '39';
        if ($money == 1) {
            $msg = '40';
        } elseif ($money == 2) {
            $msg = '37';
        } elseif ($money == 3) {
            $msg = '42';
        }
        $data = array(
            'money' => $money,
            'msg'   => $msg,
        );
        session('lottery_money', $money);
        $this->ajaxReturn($data);
    }

    /**
     * 领取奖金页面
     */
    public function getMoney() {
        $money = session('lottery_money');
        if (!$money) {
            redirect(U('Index/index'));
        }
        $this->assign('money', $money);
        $this->display();
    }

    /**
     * 异步获取奖金
     */
    public function ajaxGetMoney() {
        $account  = I('post.account', '', 'trim');
        $password = I('post.password', '', 'trim');
        $Model    = D('User');
        if (empty($account)) {
            $data = getPromptMessage('请输入帐号！');
        } else if (empty($password)) {
            $data = getPromptMessage('请输入密码！');
        }
        if (isset($data) === false) {
            $map['username|email|mobile'] = $account;
            $map['password']              = encryptPwd($password);
            $have                         = $Model->where($map)->find();
            if ($have) {
                $start_time   = strtotime(date('Y-m-d'));
                $select_where = array(
                    'user_id'     => $have['id'],
                    'action'      => 'hongbao',
                    'create_time' => array('between', array($start_time, time())),
                );
                $count        = M('flow')->where($select_where)->count('id');
                if ($count > 0) {
                    $data = getPromptMessage('您已领取过奖金不能重复领取');
                } else {
                    $money = intval(session('lottery_money'));
                    if ($money > 3 || $money == 0) {
                        $money = 1;
                    }
                    $income['user_id']     = $have['id'];
                    $income['money']       = $money;
                    $income['direction']   = 'income';
                    $income['action']      = 'hongbao';
                    $income['detail']      = $account . '抽奖中：' . $money . '元';
                    $income['create_time'] = time();
                    $flow_id               = M('flow')->add($income);
                    if ($flow_id) {
                        $Model->where('id=' . $have['id'])->setInc('money', $money);
                    }
                    session('lottery_money', null);
                    $data = getPromptMessage($money . '元已充入您的青团余额！', 'success', 1);
                }
            } else {
                $data = getPromptMessage('账户名或密码错误！');
            }
        }
        $this->ajaxReturn($data);
    }

    /**
     * 汽车团购报名
     */
    public function cart(){
        $state = cookie('lottery_cart');
        if($state){
            $this->assign('error','您已经参加过此活动了');
        }
        $this->display();
    }

    /**
     * 数据提交
     */
    public function doCart(){
        if(IS_AJAX){
            $username = I('post.username','','trim');
            $mobile   = I('post.mobile','','trim');
            $bz       = I('post.bz','','trim');
            if(!$username){
                $data = getPromptMessage('请输入姓名');
            }
            if(isset($data) === false){
                if(checkMobile($mobile) === false){
                    $data = getPromptMessage('手机号码格式不正确');
                }
            }
            if(isset($data) === false){
                $addData = array(
                    'username'   => $username,
                    'mobile'     => $mobile,
                    'bz'         =>$bz,
                    'begin_time' => date('Y-m-d H;i'),
                    'money'      => I('post.money'),
                    'type'       => $this->cartType[I('post.type')],
                );
                $res = M('lottery_cart')->add($addData);
                if($res){
                    cookie('lottery_cart',json_encode($addData),7*24*3600);
                    $data = getPromptMessage('','success',1);
                }else{
                    $data = getPromptMessage('提交失败');
                }
            }
        }else{
            $data = getPromptMessage('非法请求');
        }
        $this->ajaxReturn($data);
    }
}