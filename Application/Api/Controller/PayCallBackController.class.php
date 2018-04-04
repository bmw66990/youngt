<?php

namespace Api\Controller;

use Api\Controller\CommonController;

class PayCallBackController extends CommonController {

    // 关掉签名和身份认证
    protected $checkUser = false;
    protected $signCheck = false;
    protected $tokenCheck = false;

    /**
     * 中奖概率配置
     *  @var array
     */
    private $lottery_config = array(0 => 850, 1 => 120, 2 => 20, 3 => 9, 4 => 1);

    /**
     * 获取奖项对应积分
     * @var array
     */
    private $getScore = array(0 => 5, 1 => 20, 2 => 50, 3 => 100, 4 => 500);

    function __construct() {
        C('signCheck', false);
        C('tokenCheck', false);
        parent:: __construct();
    }

    /**
     * 支付回调
     * @param type $payAction
     */
    public function payCallbackHandle($payAction = '') {

          // 参数特殊处理
        if (isset($_POST['youngt_ts_version'])) {
            $_POST['version'] = $_POST['youngt_ts_version'];
            unset($_POST['youngt_ts_version']);
        }

        // 测试支付回调log输出
        if (C('PAY_CALLBACK_LOG')) {
            file_put_contents('./tmp/pay_callback.log', var_export(array(
                'pay_time' => date('Y-m-d H:i:s'),
                'payCallbackHandle' => '支付回调',
                'payAction' => $payAction,
                'putData' => var_export($GLOBALS['HTTP_RAW_POST_DATA'], true),
                'getData' => var_export($_GET, true),
                'postData' => var_export($_POST, true),
                            ), true), FILE_APPEND);
        }

        // 参数接收
        $team = D('Team');
        $res = $team->payCallbackHandle($payAction);

        // 结果处理
        $pay = new \Common\Org\Pay();
        $res = $pay->setpcWXReturnValue($res, $payAction);
        if (C('PAY_CALLBACK_LOG')) {
            file_put_contents('./tmp/pay_callback.log', var_export(array(
                'res' => var_export($res, true),
                            ), true), FILE_APPEND);
        }

        ob_clean();
        if (isset($res['error'])) {
            $this->_writeDBErrorLog($res, $team, 'api');
            die($res['error']);
        }
        die($res['message']);
    }

    /**
     * qq登录回调
     */
    public function qqWebLoginCallbackHandle() {
        $qlogin = new \Common\Org\QqLogin();
        $call_back_url = 'http://' . $_SERVER['HTTP_HOST'] . '/PayCallBack/qqWebLoginCallbackHandle';
        $callBackData = $qlogin->callBack($call_back_url);
        if (isset($callBackData['status']) && intval($callBackData['status']) == 1) {
            $user = D('User');
            $res = $user->qqLogin($callBackData['sns'], array('nickname' => ternary($callBackData['name'], ''), 'usericon' => ternary($callBackData['imageUrl'], '')));
            if (isset($res['id']) && trim($res['id'])) {
                $token = $this->_createToken($res['id']);
                $this->returnResult(array('code' => 0, 'data' => array('token' => $token)));
            }
        }
        $this->returnResult(array('code' => -1, 'msg' => '登录失败！'));
    }

    /**
     * qq绑定回调地址
     * @param type $res
     */
    public function qqBindWebLoginCallbackHandle() {
        $qlogin = new \Common\Org\QqLogin();
        $call_back_url = 'http://' . $_SERVER['HTTP_HOST'] . '/PayCallBack/qqBindWebLoginCallbackHandle';
        $callBackData = $qlogin->callBack($call_back_url);
        if (isset($callBackData['status']) && intval($callBackData['status']) == 1) {
            $data = array(
                'code' => 0, //
                'data' => array(
                    'sns' => ternary($callBackData['openId'], ''),
                )
            );
            $this->returnResult($data);
        }
        $this->returnResult(array('code' => -1, 'msg' => '绑定失败！'));
    }

    private function returnResult($res) {
        if (is_array($res)) {
            $res = json_encode($res);
        }
        $html = "<script type='text/javascript'>

	function getCallBackResult(){
		var res = '{$res}';
		try{
			window.stub.onSumResult(res);
		}catch(e){
		    return res;
		}
	}</script>";
        die($html);
    }

    /**
     * 积分抽奖
     */
    public function lottery(){
        $this->check();
        $user = M('user')->field('score,username')->find($this->uid);
        if($user['score'] < 10) {
            $this->outPut(null,-1,null,'您的积分不足10积分无法抽奖');
        }else {
            $model = M();
            $model->startTrans();
            $rid = getLottery($this->lottery_config);
            //扣除用户积分流水
            $delRes = $this->_addCredit(-10,$user['username'],$user['score']);
            //增加用户积分流水
            $addRes = $this->_addCredit($this->getScore[$rid],$user['username'],$user['score']);
            //修改用户积分
            $saveRes = M('user')->save(array('id'=>$this->uid,'score'=>$user['score']+(-10+$this->getScore[$rid])));
            if($delRes && $addRes && $saveRes){
                $model->commit();
                $this->outPut($rid,0,null,$user['score']+(-10+$this->getScore[$rid]));
            }else{
                $model->rollback();
                $this->outPut(null,-1,null,'服务器响应失败');
            }
        }
    }

    /**
     * 记录积分流水记录
     * @param $score
     * @param $name
     * @param $current_score
     *
     * @return mixed
     */
    protected function _addCredit($score,$name,$current_score){
        $data = array(
            'create_time' => time(),
            'user_id'     => $this->uid,
            'score'       => $score,
            'action'      => 'lottery',
            'rname'       => $name,
            'sumscore'    => $current_score - 10
        );
        return M('credit')->add($data);
    }

}
