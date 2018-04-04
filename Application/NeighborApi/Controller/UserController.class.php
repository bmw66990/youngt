<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-04-20
 * Time: 18:00
 */
namespace NeighborApi\Controller;

/**
 * 商家登陆/检测更新接口
 * Class UserController
 * @package NeighborApi\Controller
 */
class UserController extends CommonController {
    
    protected $checkUser = false;

    /**
     * 商家登陆
     */
    public function login() {
        $this->_checkblank(array('name', 'pwd'));

        $name    = I('get.name');
        $pwd     = I('get.pwd');

        $partner = D('User')->checkPwd($name, $pwd);
        $this->_writeDBErrorLog($partner, D('Partner'), 'merchantapi');
        if(isset($partner['error'])) {
            $this->outPut('', -1, '', $partner['error']);
        }
        // 生成token
        $uid   = $partner['id'];
        $token = $this->_createToken($uid);

        // 整理返回数据
        $data = array(
            'token' => $token,
        );
        $this->outPut($data, 0, null);
    }

    /**
     * 检查更新
     */
    public function checkUpdate(){
        $config = '';
        $plat   = I('get.plat', '', 'strval');
        if ($plat == 'ios') {
            $config = C('AppUpdateIos');
        } else if ($plat == 'android') {
            $config = C('AppUpdateAndroid');
        } else {
            $this->outPut(null, 1001);
        }
        $verApp    = getFloatVersion(I('get.ver'));
        $verOnline = getFloatVersion($config['ver']);
        if ($verApp < $verOnline) {
            $data = $config;
            $this->outPut($data, 0);
        }
        $this->outPut(null, 5);
    }
}