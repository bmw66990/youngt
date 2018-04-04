<?php

/**
 * Created by PhpStorm.
 * User: zhoujz
 * Date: 15-3-13
 * Time: 上午11:42
 */

namespace Common\Org;

require_once __dir__ . '/lib/PushAppMessage/XingeApp.php';

/**
 * Class PushAppMessage
 *
 * @package Common\Org
 */
class PushAppMessage {

    const ACCESS_ID = '2100089093';
    const SECRET_KEY = '249840f23d03daa89298a7989c23208d';
    
    const IOS_ACCESS_ID = '2200154216';
    const IOS_SECRET_KEY = 'c2ee778b203fc7af21c6590fcc4de81e';

    static $xingeApp = null;
    static $message_style = null;
    static $message_click_action = null;
    static $ios_env = null;
    

    /**
     * 构造函数
     */
    public function __construct() {
        self::$xingeApp = new \XingeApp(self::ACCESS_ID, self::SECRET_KEY);
        self::$message_click_action = new \ClickAction();
        self::$message_click_action->setActionType(\ClickAction::TYPE_ACTIVITY);
        self::$message_click_action->setActivity('test');
        self::$message_style = new \Style(0, 1, 1);
        //  正式生产环境： \XingeApp::IOSENV_PROD，开发测试环境：\XingeApp::IOSENV_DEV
        self::$ios_env = \XingeApp::IOSENV_DEV;
    }
    
    /**
     * 获取推送客户端
     * @param type $plat
     * @return type
     */
    private function __getXingeAppInstance($plat='android'){
        if(strtolower(trim($plat))=='ios'){
            self::$xingeApp = new \XingeApp(self::IOS_ACCESS_ID, self::IOS_SECRET_KEY);
            return self::$xingeApp;
        }
       self::$xingeApp = new \XingeApp(self::ACCESS_ID, self::SECRET_KEY);
       return self::$xingeApp;
    }

    /**
     * 推送给指定账号
     * @param type $data
     * array(
     *  title=>'标题', 不可空
     *  content=>'内容', 不可空
     *  account=>array('uid'), 不可空
     *  custom=>array(), 其他参数 可空
     *  plat=>'平台', android ios 默认不传为android
     *  send_time=>'发送的时间', 格式Y-m-d H:i:s  默认为当前时间
     * )
     * @return  code==0 成功，其他失败； data array 记录推送结果；msg 错误信息
     */
    public function pushMessageToAccess($data = array()) {

        if (!isset($data['title']) || !isset($data['content']) || !isset($data['account'])) {
            return array('code' => -1, 'msg' => '参数错误');
        }
        if (!isset($data['custom']) || !is_array($data['custom'])) {
            $data['custom'] = array();
        }
        if (!isset($data['plat']) || !trim($data['plat'])) {
            $data['plat'] = 'android';
        }
        if (!isset($data['send_time']) || !trim($data['send_time'])) {
            $data['send_time'] = date('Y-m-d H:i:s');
        }
        $messge_type = \Message::TYPE_MESSAGE;
        if (isset($data['message_type']) && trim($data['message_type']) == '1') {
            $messge_type = \Message::TYPE_NOTIFICATION;
        }

        //处理账号
        if (!is_array($data['account']) && is_string($data['account'])) {
            $data['account'] = array($data['account']);
        }
        foreach ($data['account'] as &$v) {
            if (strpos($v, 'xgAlias_') === false) {
                $v = "xgAlias_$v";
            }
        }

        $plat = strtolower($data['plat']);
        if ($plat == 'android') {
            $message = new \Message();
            $message->setTitle($data['title']);
            $message->setContent($data['content']);
            $message->setCustom($data['custom']);
            $message->setSendTime($data['send_time']);
            $message->setType($messge_type);
            // 设置点击
            $message->setAction(self::$message_click_action);
            // 设置样式
            $message->setStyle(self::$message_style);
            
            $ret = self::$xingeApp->PushAccountList(0, $data['account'], $message);
            $data = array(
                'code' => isset($ret['ret_code']) ? $ret['ret_code'] : '',
                'data' => isset($ret['result']) ? $ret['result'] : '',
                'msg' => isset($ret['err_msg']) ? $ret['err_msg'] : '',
            );
            return $data;
        }
        $this->__getXingeAppInstance('ios');
        $message = new \MessageIOS();
        $message->setAlert($data['content']);
        $message->setCustom($data['custom']);
        $message->setSendTime($data['send_time']);
        $ret = self::$xingeApp->PushAccountList(0, $data['account'], $message, self::$ios_env);
        $data = array(
            'code' => isset($ret['ret_code']) ? $ret['ret_code'] : '',
            'data' => isset($ret['result']) ? $ret['result'] : '',
            'msg' => isset($ret['err_msg']) ? $ret['err_msg'] : '',
        );
        return $data;
    }

    /**
     * 推送给指定标签
     * @param type $data
     * array(
     *  title=>'标题', 不可空
     *  content=>'内容', 不可空
     *  tags=>array('city_id'), 不可空
     *  custom=>array(), 其他参数 可空
     *  plat=>'平台', android ios 默认不传为android
     *  send_time=>'发送的时间', 格式Y-m-d H:i:s  默认为当前时间
     * )
     * @return code==0 成功，其他失败； data array 记录推送结果；msg 错误信息
     */
    public function pushMessageToTags($data = array()) {

        if (!isset($data['title']) || !isset($data['content']) || !isset($data['tags'])) {
            return array('code' => -1, 'msg' => '参数错误');
        }
        if (!isset($data['custom']) || !is_array($data['custom'])) {
            $data['custom'] = array();
        }
        if (!is_array($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array($data['tags']);
        }
        if (!isset($data['plat']) || !trim($data['plat'])) {
            $data['plat'] = 'android';
        }
        if (!isset($data['send_time']) || !trim($data['send_time'])) {
            $data['send_time'] = date('Y-m-d H:i:s');
        }
        $messge_type = \Message::TYPE_MESSAGE;
        if (isset($data['message_type']) && trim($data['message_type']) == '1') {
            $messge_type = \Message::TYPE_NOTIFICATION;
        }

        $plat = strtolower($data['plat']);
        if ($plat == 'android') {
            $message = new \Message();
            $message->setTitle($data['title']);
            $message->setContent($data['content']);
            $message->setCustom($data['custom']);
            $message->setSendTime($data['send_time']);
            $message->setType($messge_type);
            // 设置点击
            $message->setAction(self::$message_click_action);
            // 设置样式
            $message->setStyle(self::$message_style);
            $ret = self::$xingeApp->PushTags(0, $data['tags'], 'OR', $message);
            $data = array(
                'code' => isset($ret['ret_code']) ? $ret['ret_code'] : '',
                'data' => isset($ret['result']) ? $ret['result'] : '',
                'msg' => isset($ret['err_msg']) ? $ret['err_msg'] : '',
            );
            return $data;
        }
        $this->__getXingeAppInstance('ios');
        $message = new \MessageIOS();
        $message->setAlert($data['content']);
        $message->setCustom($data['custom']);
        $message->setSendTime($data['send_time']);
        $ret = self::$xingeApp->PushTags(0, $data['tags'], 'OR', $message, self::$ios_env);
        $data = array(
            'code' => isset($ret['ret_code']) ? $ret['ret_code'] : '',
            'data' => isset($ret['result']) ? $ret['result'] : '',
            'msg' => isset($ret['err_msg']) ? $ret['err_msg'] : '',
        );
        return $data;
    }

    /**
     * 推送给所有设备
     * @param type $data
     * array(
     *  title=>'标题', 不可空
     *  content=>'内容', 不可空
     *  custom=>array(), 其他参数 可空
     *  plat=>'平台', android ios 默认不传为android
     *  send_time=>'发送的时间', 格式Y-m-d H:i:s  默认为当前时间
     * )
     * @return  code==0 成功，其他失败； data array 记录推送结果；msg 错误信息
     */
    public function pushMessageToAll($data = array()) {

        if (!isset($data['title']) || !isset($data['content'])) {
            return array('code' => -1, 'msg' => '参数错误');
        }
        if (!isset($data['custom']) || !is_array($data['custom'])) {
            $data['custom'] = array();
        }
        if (!isset($data['plat']) || !trim($data['plat'])) {
            $data['plat'] = 'android';
        }
        if (!isset($data['send_time']) || !trim($data['send_time'])) {
            $data['send_time'] = date('Y-m-d H:i:s');
        }
        $messge_type = \Message::TYPE_MESSAGE;
        if (isset($data['message_type']) && trim($data['message_type']) == '1') {
            $messge_type = \Message::TYPE_NOTIFICATION;
        }
        $plat = strtolower($data['plat']);
        if ($plat == 'android') {
            $message = new \Message();
            $message->setTitle($data['title']);
            $message->setContent($data['content']);
            $message->setCustom($data['custom']);
            $message->setSendTime($data['send_time']);
            $message->setType($messge_type);

            // 设置点击
            $message->setAction(self::$message_click_action);
            // 设置样式
            $message->setStyle(self::$message_style);

            $ret = self::$xingeApp->PushAllDevices(0, $message);
            $data = array(
                'code' => isset($ret['ret_code']) ? $ret['ret_code'] : '',
                'data' => isset($ret['result']) ? $ret['result'] : '',
                'msg' => isset($ret['err_msg']) ? $ret['err_msg'] : '',
            );
            return $data;
        }
        $this->__getXingeAppInstance('ios');
        $message = new \MessageIOS();
        $message->setAlert($data['content']);
        $message->setCustom($data['custom']);
        $message->setSendTime($data['send_time']);
        $ret = self::$xingeApp->PushAllDevices(0, $message, self::$ios_env);
        $data = array(
            'code' => isset($ret['ret_code']) ? $ret['ret_code'] : '',
            'data' => isset($ret['result']) ? $ret['result'] : '',
            'msg' => isset($ret['err_msg']) ? $ret['err_msg'] : '',
        );
        return $data;
    }

}
