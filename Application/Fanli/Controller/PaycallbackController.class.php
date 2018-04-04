<?php
namespace Fanli\Controller;

use Think\Controller;

class PaycallbackController extends CommonController {

    public function notify() {
        $pay_type = I('get.pay_type', '', 'trim');

        var_dump($pay_type);
        die;
        // 测试支付回调log输出
        if (C('PAY_CALLBACK_LOG')) {
            file_put_contents('/tmp/wap_paycallback.log', var_export(array(
                'pay_time' => date('Y-m-d H:i:s'),
                'payCallbackHandle' => '支付回调',
                'payAction' => $pay_type,
                'putData' => var_export($GLOBALS['HTTP_RAW_POST_DATA'], true),
                'getData' => var_export($_GET, true),
                'postData' => var_export($_POST, true),
                            ), true), FILE_APPEND);
        }
        
        $res = $this->paySuccessdeal('wapwechatpay');

        if (!class_exists('PcWXpayNotify')) {
            include __DIR__ . '/wapWXpay/pcWXpayNotify.class.php';
        }
        $pcWXpayNotify = new \PcWXpayNotify();

        if (isset($res['error']) && trim($res['error'])) {
            $pcWXpayNotify->SetReturn_code("FAIL");
            $pcWXpayNotify->SetReturn_msg("支付回调失败！");
            $res ['error'] = $pcWXpayNotify->ToXml();
        }
        if (isset($res['message']) && trim($res['message'])) {
            $pcWXpayNotify->SetReturn_code("SUCCESS");
            $pcWXpayNotify->SetReturn_msg("OK");

            $res['message'] = $pcWXpayNotify->ToXml();
        }

        if (C('PAY_CALLBACK_LOG')) {
            file_put_contents('/tmp/wap_paycallback.log', var_export(array(
                'res' => var_export($res, true),
                            ), true), FILE_APPEND);
        }

        ob_clean();
        if (isset($res['error'])) {
            die($res['error']);
        }
        die($res['message']);
    }

    private function paySuccessdeal($pay_type='') {
        
        if(!$pay_type){
            return array('error'=>'fail');
        }
        
        $pay = new \Common\Org\Pay();

        $FanliModel = D('FanliOrder');
        switch ($pay_type) {
            case 'alipay':
                $res = $pay->getALiCallBackVerify();
                if (!$res) {
                     return array('error'=>'fail');
                }
                // 支付成功！
                if (isset($res['trade_status']) && $res['trade_status'] == "TRADE_SUCCESS") {
                    if (isset($res['refund_status'])) {
                        //  $this->thirdPartyRefundUpdateData($res);
                        return array('message'=>'success');
                    }
                    $pay_res = $FanliModel->paySuccess($res['out_trade_no'], $res['total_fee'], $pay_type, $res['trade_no']);
                    if ($pay_res) {
                         return array('message'=>'success');
                    }
                    return array('error'=>'fail');
                }

                if (isset($res['trade_status']) && $res['trade_status'] == "WAIT_BUYER_PAY") {
                    return array('message'=>'success');
                }
                return array('error'=>'fail');
                break;
            case 'wxpay':
                
                
                $data = $pay->getPCWXpayVerify();
                
                if (!$data || (isset($data['trade_state']) && trim($data['trade_state']) != '0')) {
                   return array('error'=>'fail');
                }
                $result = $FanliModel->paySuccess($data['out_trade_no'], $data['total_fee'], $pay_type, $data['transaction_id']);
                if ($result) {
                    return array('message'=>'success');
                }
               return array('error'=>'fail');
                break;
           case 'wapwechatpay':

                $res = $pay->getPCWXpayVerify();
                //$res['out_trade_no']  订单编号   $res['transaction_id']  vid  $pay_type 支付方式  $res['total_fee']订单总额
                if (!$res || (isset($res['trade_state']) && trim($res['trade_state']) != '0')) {
                    return array('error' => 'fail');
                }
                $result = $FanliModel->paySuccess($res['out_trade_no'], $res['total_fee'], $pay_type, $res['transaction_id']);
                if ($result) {
                    return array('message'=>'success');
                }
                return array('error'=>'fail');
                break;
            case 'wapalipay':
                $res = $pay->wapPayVerify('wapalipay');
                if (!$res) {
                    return array('error'=>'fail');
                }
                // 支付成功！
                if (isset($res['trade_status']) && $res['trade_status'] == "TRADE_SUCCESS") {
                    if (isset($res['refund_status'])) {
                        return array('message'=>'success');
                    }
                    $pay_res = $FanliModel->paySuccess($res['out_trade_no'], $res['total_fee'], $pay_type, $res['trade_no']);
                    if ($pay_res) {
                        return array('message'=>'success');
                    }
                    return array('error'=>'fail');
                }

                if (isset($res['trade_status']) && $res['trade_status'] == "WAIT_BUYER_PAY") {
                    return array('message'=>'success');
                }
                return array('error'=>'fail');
                break;
           
        }
        
        return array('error'=>'fail');
    }

}
