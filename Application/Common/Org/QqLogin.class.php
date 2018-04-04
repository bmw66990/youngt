<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/28
 * Time: 10:24
 */

namespace Common\Org;

class QqLogin {

    /**
     * 配置
     */
    const QQ_APP_ID = '101096347';
    const QQ_CALLBACK = 'http://huxian.youngt.com/Public/qqCallBack';
    const QQ_APP_KEY = '3a22c8abcbd2643800c2d0dd5ccfcf91';
    const QQ_SCOPE = 'get_user_info';
    const GET_AUTH_CODE_URL = "https://graph.qq.com/oauth2.0/authorize";
    const GET_ACCESS_TOKEN_URL = "https://graph.qq.com/oauth2.0/token";
    const GET_OPENID_URL = "https://graph.qq.com/oauth2.0/me";
    const GET_USER_INFO_URL = "https://graph.qq.com/user/get_user_info";

    /**
     * qq联合登陆
     */
    public function login($callback = '') {
        if ($callback == '') {
            $callback = self::QQ_CALLBACK;
        }
        $state = md5(uniqid(rand(), true));
        session('QQ_Code', $state);
        $keysArr = array(
            "response_type" => "code",
            "client_id" => self::QQ_APP_ID,
            "redirect_uri" => $callback,
            "state" => $state,
            "scope" => self::QQ_SCOPE
        );
        $login_url = $this->combineURL(self::GET_AUTH_CODE_URL, $keysArr);
        header("Location:$login_url");
    }

    /**
     * @param $baseURL
     * @param $keysArr
     *
     * @return string
     */
    public function combineURL($baseURL, $keysArr) {
        $combined = $baseURL . "?";
        $valueArr = array();

        foreach ($keysArr as $key => $val) {
            $valueArr[] = "$key=$val";
        }

        $keyStr = implode("&", $valueArr);
        $combined .= ($keyStr);

        return $combined;
    }

    /**
     * 回调
     */
    public function callBack($callbackurl = '') {
        $state = session('QQ_Code');
        //--------验证state防止CSRF攻击
        if ($_GET['state'] != $state) {
            return getPromptMessage('code验证失败');
        }
        $token = $this->_getToken($callbackurl);
        if (isset($token['error'])) {
            return $token;
        }

        $open_id = $this->_get_openid($token);
        if (isset($open_id['error'])) {
            return $open_id;
        }
        $users = $this->_getUserInfo($token, $open_id);
        $data = array(
            'status' => 1,
            'sns' => "qzone:" . $open_id,
            'name' => $users['nickname'],
            'openId' => $open_id,
            'imageUrl' => $users['figureurl_qq_2'],
        );
        $qqData = array(
            'name' => $users['nickname'],
            'openId' => $open_id,
        );
        session('qqData', $qqData);
        return $data;
    }

    /**
     * get_contents
     * 服务器通过get请求获得内容
     *
     * @param string $url 请求的url,拼接后的
     *
     * @return string           请求返回的内容
     */
    public function get_contents($url) {
        if (ini_get("allow_url_fopen") == "1") {
            $response = file_get_contents($url);
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            curl_close($ch);
        }
        return $response;
    }

    /**
     * 获取token
     */
    protected function _getToken($callbackurl = '') {

        if (!trim($callbackurl)) {
            $callbackurl = self::QQ_CALLBACK;
        }
        //-------请求参数列表
        $keysArr = array(
            "grant_type" => "authorization_code",
            "client_id" => self::QQ_APP_ID,
            "redirect_uri" => $callbackurl,
            "client_secret" => self::QQ_APP_KEY,
            "code" => $_GET['code']
        );
        $token_url = $this->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = $this->get_contents($token_url);
        if (strpos($response, "callback") !== false) {

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
            $msg = json_decode($response);
            if (isset($msg->error)) {
                return getPromptMessage('token获取失败');
            }
        }
        $params = array();
        parse_str($response, $params);
        return $params["access_token"];
    }

    /**
     * 获取open_id
     */
    protected function _get_openid($token) {
        //-------请求参数列表
        $keysArr = array(
            "access_token" => $token
        );

        $graph_url = $this->combineURL(self::GET_OPENID_URL, $keysArr);
        $response = $this->get_contents($graph_url);

        //--------检测错误是否发生
        if (strpos($response, "callback") !== false) {

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }
        $user = json_decode($response);
        if (isset($user->error)) {
            return getPromptMessage('获取open_id失败');
        }
        return $user->openid;
    }

    /**
     * @param $token
     * @param $open_id
     *
     * @return mixed
     */
    protected function _getUserInfo($token, $open_id) {
        $keysArr = array(
            "access_token" => $token,
            "oauth_consumer_key" => self::QQ_APP_ID,
            "openid" => $open_id,
        );
        $get_user_url = $this->combineURL(self::GET_USER_INFO_URL, $keysArr);
        $response = $this->get_contents($get_user_url);
        $user = json_decode($response, true);
        return $user;
    }

}
