<?php

namespace Api\Controller;

use Api\Controller\CommonController;

class OtaCallBackController extends CommonController {

    // 关掉签名和身份认证
    protected $checkUser = false;
    protected $signCheck = false;
    protected $tokenCheck = false;

    function __construct() {
        parent:: __construct();
    }

    // 游客入园回调
    public function callBack() {
        $merCode = I('post.MerchantCode', '', 'trim');
        $params  = I('post.Parameters', '', 'trim');
        $sign    = I('post.signature', '', 'trim');
        $data = D('Ota')->ecodeUsed($merCode,$params,$sign);
        echo json_encode($data);
    }
}
