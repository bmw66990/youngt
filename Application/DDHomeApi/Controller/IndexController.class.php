<?php

namespace DDHomeApi\Controller;

class IndexController extends CommonController {

    /**
     * @var bool 是否验证uid
     */
    protected $checkUser = false;
    protected $signCheck = false;
    protected $tokenCheck = false;

    function __construct() {
        C('signCheck', false);
        C('tokenCheck', false);
        parent:: __construct();
    }
    
    public function create_token(){
        $uid = I('get.uid');
        $token = $this->_createToken($uid);
        echo $token;
    }

    public function index() {

        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 10
0px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover,{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>青团网客户
端点点在家接口</b>！', 'utf-8');
        exit;
    }
}
