<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/23
 * Time: 19:28
 */

namespace Common\Org;

/**
 * Class sendVoice
 * @package Common\Org
 */
class sendVoices {
    /**
     * @var client 对象
     */
    private $clients = '';
    /**
     * 网关地址
     */
    private $gwUrl = 'http://sdk4report.eucp.b2m.cn:8080/sdk/SDKService';
    /**
     * 序列号,请通过亿美销售人员获取
     */
    private $serialNumber = '6SDK-EMY-6688-KGXTS';

    /**
     * 密码,请通过亿美销售人员获取
     */
    private $password = '741044';

    /**
     * 登录后所持有的SESSION KEY，即可通过login方法时创建
     */
    private $sessionKey = '741044';

    /**
     * 连接超时时间，单位为秒
     */
    private $connectTimeOut = 2;
    /**
     * 远程信息读取超时时间，单位为秒
     */
    private $readTimeOut = 10;
    /**
     * @var bool 可选，代理服务器地址，默认为 false ,则不使用代理服务器
     */
    private $host = false;
    /**
     * @var bool 可选，代理服务器端口，默认为 false
     */
    private $port = false;
    /**
     * @var bool 可选，代理服务器用户名，默认为 false
     */
    private $username = false;
    /**
     * @var bool 可选，代理服务器密码，默认为 false
     */
    private $passwords = false;

    /**
     * @param $phone    手机号
     * @param $code     验证码
     */
    public function sendVoice($phone, $code) {
        $phone = array($phone);
        require_once(__DIR__ . '/lib/Soaplib/Client.php');
        require_once(__DIR__ . '/lib/Soaplib/nusoap.php');
        $clients = new \Client($this->gwUrl, $this->serialNumber, $this->password, $this->sessionKey, $this->host, $this->port, $this->username, $this->passwords, $this->connectTimeOut, $this->readTimeOut);
        $clients->setOutgoingEncoding("utf-8");
        $statusCode = $clients->sendVoice($phone, $code);
        if ($statusCode != null && $statusCode == 0) {
            return array('status' => 1, 'data' => '发送成功');
        } else {
            return array('status' => -1, 'data' => '发送失败');
        }
    }

}