<?php

namespace Admin\Controller;

use Common\Controller\CommonBusinessController;

/**
 * Class CommonAction
 */
class CommonController extends CommonBusinessController {

    /**
     * 构造方法
     */
    public function __construct() {
        parent:: __construct();
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {            
            return false;        
        }
        //$this->_getWxShareData();
    }       

    /**
     * 获取用户分享所需数据
     */
    public function _getWxShareData($mid){
        $app_id = 'wx5aaa6db815374f64'; 
        //$app_id = 'wx71ef1edff818d209';            
        $redirect_uri = urlencode('http://fanli.ree9.com/Wechat/my_index');
        $auth_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$app_id}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_userinfo&state=".$mid."#wechat_redirect";        
        redirect($auth_url);
    }

    /**
     *  推广赚钱
     */
    public function _PutshareQrcode($mid){
        $app_id = 'wx5aaa6db815374f64'; 
        //$app_id = 'wx71ef1edff818d209';            
        $redirect_uri = urlencode('http://fanli.ree9.com/user/userIndex');
        $auth_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$app_id}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_userinfo&state=".$mid."#wechat_redirect";        
        redirect($auth_url);
    }

}
