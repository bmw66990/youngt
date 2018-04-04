<?php

namespace Common\Org;

include_once __DIR__ . '/lib/wechat/qywechat.class.php';

class TPWechat extends \Wechat {

    /**
     * 填写你设定的key
     */
    const TOKEN = '3ac156eead4ae6b40e9c498d532b4448';

    /**
     * 填写加密用的EncodingAESKey
     */
    const ENCODEING_AES_KEY = 'oU7Bgn6d8hKN4lugmagsdyERj9Q2CJofteM08FVR9-t4OqndKeGjDXjWWaJ2AF3k';

    /**
     * 填写高级调用功能的app id
     */
    const APP_ID = 'wx91643da30fe85560';

    /**
     * 应用的id
     */
    const AGENT_ID = '0';

    /**
     * 填写高级调用功能的密钥
     */
    const APP_SECRET = '3k9n-VES_zib6g-MAXS54yMreE1twliTmjdh48QSNxsvBGAqz6bLBxUwRdx-I6Dp';

    public $debug = false;

    public function __construct($options = array()) {
        if (!$options) {
            $options = array(
                //      'token' => self::TOKEN, //填写应用接口的Token
                //       'encodingaeskey' =>self::ENCODEING_AES_KEY , //填写加密用的EncodingAESKey
                'appid' => self::APP_ID, //填写高级调用功能的app id
                'appsecret' => self::APP_SECRET, //填写高级调用功能的密钥
                'agentid' => self::AGENT_ID, //应用的id
                'debug' => $this->debug, //调试开关
                    //    'logcallback' => 'logg', //调试输出方法，需要有一个string类型的参数
            );
        }
        parent::__construct($options);
    }

    public function sendMessageToText($content = '', $user = array()) {
        
        $uids = $this->getWeiXinUserInfo($user);
        if(!$uids){
            return false;
        }
        
        $data = array(
            'touser' => $uids,
            'msgtype' => 'text',
            'agentid' => self::AGENT_ID,
            'text' => array(
                'content' => $content,
            ),
        );
        return $this->sendMessage($data);
    }
    
    /**
     * 根据青团用户获取企业号用户
     * @param type $user
     * @return boolean
     */
    public function getWeiXinUserInfo($user = array()) {
        
        if(!$user){
            return false;
        }

        $pinyin = new \Common\Org\pinyin();
        $cache_key = 'weixin_qyh_user_list';
        $data = S($cache_key);
        if (!$data) {
            $wx_user_res = $this->getUserListInfo('1', 1, 0);
            if (isset($wx_user_res['userlist']) && is_array($wx_user_res['userlist'])) {
                foreach ($wx_user_res['userlist'] as $v) {
                    if (isset($v['name']) && trim($v['name'])) {
                        $key_name = md5(trim($v['name']));
                        $key_name_pinyin = md5($pinyin->str2py(trim($v['name']), 'all'));
                        $data[$key_name] = $data[$key_name_pinyin] = $v['userid'];
                    }
                    if (isset($v['mobile']) && trim($v['mobile'])) {
                        $key_mobile = md5($v['mobile']);
                        $data[$key_mobile] = $v['userid'];
                    }
                }
            }
            $cache_time = 60*60*24;
            S($cache_key,$data,$cache_time);
        }
        $user_ids = array();
        if ($user) {
            foreach ($user as $v) {
                if (isset($v['name']) && trim($v['name'])) {
                    $key_name = md5(trim($v['name']));
                    if (isset($data[$key_name])) {
                        $user_ids[] = $data[$key_name];
                        continue;
                    }
                    $key_name_pinyin = md5($pinyin->str2py(trim($v['name']), 'all'));
                    if (isset($data[$key_name_pinyin])) {
                        $user_ids[] = $data[$key_name_pinyin];
                        continue;
                    }
                }
                if (isset($v['mobile']) && trim($v['mobile'])) {
                    $key_mobile = md5($v['mobile']);
                    if (isset($data[$key_mobile])) {
                        $user_ids[] = $data[$key_mobile];
                        continue;
                    }
                }
            }
        }

        if (!$user_ids) {
            return false;
        }
        return implode('|', $user_ids);
    }

    /**
     * log overwrite
     * @see Wechat::log()
     */
    protected function log($log) {
        if ($this->debug) {
            if (function_exists($this->logcallback)) {
                if (is_array($log))
                    $log = print_r($log, true);
                return call_user_func($this->logcallback, $log);
            }elseif (class_exists('Log')) {
                \Think\Log::write('wechat：' . $log, \Think\Log::DEBUG);
                return true;
            }
        }
        return false;
    }

    /**
     * 重载设置缓存
     * @param string $cachename
     * @param mixed $value
     * @param int $expired
     * @return boolean
     */
    protected function setCache($cachename, $value, $expired) {
        return false;
        return S($cachename, $value, $expired);
    }

    /**
     * 重载获取缓存
     * @param string $cachename
     * @return mixed
     */
    protected function getCache($cachename) {
        return false;
        return S($cachename);
    }

    /**
     * 重载清除缓存
     * @param string $cachename
     * @return boolean
     */
    protected function removeCache($cachename) {
        return false;
        return S($cachename, null);
    }

}
