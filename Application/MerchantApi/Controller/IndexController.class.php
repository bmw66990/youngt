<?php

namespace MerchantApi\Controller;

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

    public function index() {

        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 10
0px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover,{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>青团网商户
端接口</b>！', 'utf-8');
        exit;
    }
    /**
     * 检查更新
     */
    public function checkUpdate() {
        $plat = I('get.plat', '', 'trim');
        $app_ver = I('get.ver', '', 'trim');
        $config = C('AppUpdateAndroid');
        if ($plat == 'ios') {
            $config = C('AppUpdateIos');
        }
        $service_ver =  ternary($config['ver'], '');
        $is_upgrade = 'N';
        if($app_ver && strcmp($service_ver ,$app_ver )>0){
            $is_upgrade = 'Y';
        }
        if($plat=='ios'){
            $data=array();
        }else{
            $data = array(
                'version' => $service_ver,
                'is_force' => ternary($config['is_force'], ''),
                'is_upgrade'=>$is_upgrade,
                'description' => ternary($config['description'], ''),
                'download_url' => ternary($config['url'], ''),
            );
        }

        $this->outPut($data, 0);
    }

}
