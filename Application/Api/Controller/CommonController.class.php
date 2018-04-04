<?php

namespace Api\Controller;

use Common\Controller\CommonBusinessController;

/**
 * Class CommonAction
 */
class CommonController extends CommonBusinessController {
    
    /**
     *接口缓存方式
     * @var type 
     */
    protected $api_cache_type = 'S';
    
    /**
     * 接口数据缓存时间
     */
    const CACHE_TIME_OUT = 300;

    /**
     * @var bool 是否验证uid
     */
    protected $checkUser = false;

    /**
     * @var int 用户id
     */
    protected $uid = 0;

    /**
     * @var bool 是否验证签名
     */
    protected $signCheck = true;

    /**
     * @var bool 是否验证token
     */
    protected $tokenCheck = false;

    /**
     * @var int 每页请求多少条数据
     */
    protected $reqnum = 20;

    /**
     * @var int 错误1级标志
     */
    private $msg = '';

    /**
     * @var int 错误标志
     */
    private $code = 0;

    /**
     * @var int 是否有下一页
     */
    private $hasNext = 0;

    /**
     * @var null 返回数据
     */
    private $data = '';

    /**
     * @var string 返回结果
     */
    private $result = '';

    /**
     * @var null 全局变量
     */
    private $G = null;

    /**
     * @var null  访问资源类型
     */
    protected $resolution = '';

    /**
     * @var null  版本号
     */
    protected $version = '';

    /**
     * 构造函数
     *
     * @access public
     */
    function __construct() {
        parent:: __construct();
        if ($this->checkUser) {
            $this->check();
        }


        if (C('signCheck')) {
            $this->signCheck = C('signCheck');
        }

        if (C('tokenCheck')) {
            $this->tokenCheck = C('tokenCheck');
        }

        //签名验证
        if ($this->signCheck !== false)
            $this->_sigCheck();

        //token验证
        if ($this->tokenCheck !== false)
            $this->_tokenCheck();

        //设置版本
        $this->setVersion();
    }

    /**
     * 获取版本号
     */
    protected function setVersion() {
        $version = I('param.version', 1);

        if ($version != '1') {
            $versionArr = str_replace('.', '_', $version);
            C('ACTION_SUFFIX', 'V' . $versionArr);
        }
    }

    /**
     * @param        $model             模型
     * @param        $idkey             主键
     * @param        $dataArr           数据
     * @param        $whereArr          where条件
     * @param string $whereStr          where条件2
     */
    protected function getHasNext($model, $idkey, $dataArr, $whereArr, $whereStr = "1=1") {
        if (count($dataArr) < $this->reqnum) {
            $this->hasNext = 0;
        } else {
            if ($this->pageflag == 2) {
                $first = array_shift($dataArr);
                $whereStr .= " and " . $idkey . ">" . intval($first[$idkey]);
            } else {
                $end = array_pop($dataArr);
                $whereStr .= " and " . $idkey . "<" . intval($end[$idkey]);
            }
            $count = $model->where($whereArr)->where($whereStr)->count();
            $this->hasNext = $count == 0 ? $count : 1;
        }
    }

    /**
     * 设置 hasnext
     * @param type $hasnaxt
     * @param type $data
     * @param type $limitNum
     * @return boolean
     */
    protected function setHasNext($hasnaxt = false, &$data = array(), $limitNum = 20) {

        if ($hasnaxt !== false) {
            $this->hasNext = $hasnaxt;
            return $this->hasNext;
        }
        $this->hasNext = '0';
        if ($data && $limitNum) {
            if (count($data) > $limitNum) {
                $this->hasNext = '1';
                array_pop($data);
            }
        }
        return $this->hasNext;
    }

    /**
     * 删除方法
     * @param $modelname   模型表名
     * @param $idkey       键名
     * @param $checkuid    键值
     */
    protected function com_delete($modelname, $idkey, $checkuid = 0) {
        if ($checkuid == 1) {
            $this->check();
        }
        $this->_checkblank($idkey);
        $idval = intval(I("post.$idkey"));
        $model = M($modelname);
        $where = array('uid' => $this->uid, $idkey => $idval);
        $result = $model->where($where)->delete();
        if ($result !== false) {
            $this->outPut(array($idkey => $idval), 0, 0);
        } else {
            $this->outPut(null, 4, 11);
        }
    }

    /**
     * 验证函数
     *
     * @access private
     */
    protected function check() {
        $this->uid = intval($this->__getUid());
        $userModel = D('User');
        if (!$userModel->isExist($this->uid)) {
            $this->outPut('', 1);
        }
    }

    /**
     * 输出函数，如果启用debug,输出日志
     *
     * @param array  $result     输出数据
     * @param int    $errCode    错误code
     * @param string $proType    处理类型
     */
    public function outPut($result = null, $errCode, $proType = null, $msg = '') {
        $this->setData(is_null($result) ? '' : $result);
		
        $this->setError($errCode, $proType, $msg);
        $this->setResult();
		
        ob_clean();
        echo json_encode($this->result);
        if (C('SER_LOG')) {
            $this->logOut($result);
        }
        exit;
    }

    /**
     * 设置输出结果
     *
     */
    private function setResult() {
        $this->result = array('code' => $this->code, 'msg' => $this->msg, 'hasnext' => $this->hasNext, 'data' => $this->data);
    }

    /**
     * 设置数据
     *
     */
    private function setData($data) {
        //新增数据过滤转换   daipingshan 2015-04-14
        $data = $this->_checkData($data);
        $this->data = $data;
    }

    /**
     * 设置错误信息
     *
     * @param int    $error      错误code
     * @param string $proType    处理类型
     */
    private function setError($error = 0, $proType = null, $msg = '') {
        $this->code = $error;
        $this->msg = $this->ef->getErrMsg($error, $proType) . $msg;
    }

    /**
     * 日志输出
     *
     */
    protected function logOut($result) {
        \Think\Log::write('start-------------------------------------------start', \Think\Log::INFO);
        \Think\Log::write('访问页面：' . $_SERVER['PHP_SELF'], \Think\Log::INFO);
        \Think\Log::write('请求方法：' . $_SERVER['REQUEST_METHOD'], \Think\Log::INFO);
        \Think\Log::write('通信协议：' . $_SERVER['SERVER_PROTOCOL'], \Think\Log::INFO);
        \Think\Log::write('请求时间：' . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']), \Think\Log::INFO);
        \Think\Log::write('用户代理：' . $_SERVER['HTTP_USER_AGENT'], \Think\Log::INFO);
        \Think\Log::write('CONTENT_TYPE：' . $_SERVER['CONTENT_TYPE'], \Think\Log::INFO);
        \Think\Log::write('提交数据：', \Think\Log::INFO);
        foreach ($_REQUEST as $key => $value) {
            \Think\Log::write($key . "=" . $value, \Think\Log::INFO);
        }
        \Think\Log::write('输出结果：', \Think\Log::INFO);
        foreach ($result as $key => $value) {
            \Think\Log::write($key . "=" . $value, \Think\Log::INFO);
        }

        if (count($_FILES) != 0) {
            \Think\Log::write('提交文件：', \Think\Log::INFO);
            foreach ($_FILES as $key => $value) {
                \Think\Log::write($key . "=" . $value['type'], \Think\Log::INFO);
            }
        }

        \Think\Log::write('end-----------------------------------------------end', \Think\Log::INFO);
    }

    /**
     *
     */
    public function setG() {
        foreach ($_GET as $key => $value) {
            $this->G[$key] = trim(I('get.' . $key));
        }
        foreach ($_POST as $key => $value) {
            $this->G[$key] = trim(I('post.' . $key));
        }
    }

    /**
     * 验证是否为空
     * @param $array
     */
    public function _checkblank($array) {
        $errmsg = '';
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (I('param.' . $value) === "") {
                    $errmsg .= $value . ',';
                }
            }
        } else {
            if (I('param.' . $array) === "") {
                $errmsg .= $array . ',';
            }
        }
        if (!empty($errmsg)) {
            $strc = substr($errmsg, 0, strlen($errmsg) - 1);
            $this->outPut('', 1, null, ":缺少参数" . $strc);
        }
    }

    /**
     * 签名验证
     *
     */
    protected function _sigCheck() {
        if ($this->signCheck === false)
            return true;
        $this->setG();
        $method = $_SERVER['REQUEST_METHOD'];
        //TODO 开启子域名配置时使用的地址格式
        $url_path = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PATH_INFO'];
        //TODO 关闭子域名或调试阶段配置时使用的地址格式
        //$url_path = 'http://' . $_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
        //$url_path = 'http://www.youngt.com';
        $url_path = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if (strpos($url_path, '?') !== false) {
            list($url_path, $_) = @explode('?', $url_path, 2);
        }

        $params = $this->G;
        //file_put_contents('/tmp/ddhome_paycallback.log',var_export($params, true).'||',FILE_APPEND);
        unset($params['sig']);
        $sigServer = $this->__makeSig($method, $url_path, $params);
        $verifyKey = C('verifyKey');
        $sign = \Think\Crypt\Driver\Xxtea::encrypt($sigServer, $verifyKey);
        $sigClient = $this->__xxteaDecrypt(base64_decode(rawurldecode($this->G['sig'])), $verifyKey);

        $checkresult = strcmp($sigServer, $sigClient) ? false : true;
        if (!$checkresult) {
            $this->outPut(null, 2);
            exit;
        }
    }

    /**
     * 生成签名
     *
     * @param string $method  请求方法 "get" or "post"
     * @param string $url_path
     * @param array  $params  表单参数
     */
    private function __makeSig($method, $url_path, $params) {
        $mk = self::__makeSource($method, $url_path, $params);
        return sha1($mk);
    }

    /**
     * 生成验证字符
     *
     * @param string $method  请求方法 "get" or "post"
     * @param string $url_path
     * @param array  $params  表单参数
     */
    private function __makeSource($method, $url_path, $params) {
        ksort($params);
        $strs = strtoupper($method) . '&' . rawurlencode($url_path) . '&';

        $str = "";
        foreach ($params as $key => $val) {
            $str .= "$key=$val&";
        }
        $strc = substr($str, 0, strlen($str) - 1);
        return $strs . rawurlencode($strc);
    }

    /**
     * @param $str   解密字符串
     * @param $key   密钥
     * @return string
     */
    private function __xxteaDecrypt($str, $key) {
        return \Think\Crypt\Driver\Xxtea::decrypt($str, $key);
    }

    /**
     * 数据解密
     * @param $arr 解密数据
     * @return array|null|string
     */
    protected function _decryptRequest($arr) {
        $result = null;
        $dataKey = C('dataKey');
        if (is_array($arr)) {
            foreach ($arr as $value) {
                $result[] = $this->__xxteaDecrypt($value, $dataKey);
            }

            return $result;
        } else {
            return $this->__xxteaDecrypt($arr, $dataKey);
        }
    }

    /**
     * token解密
     * @param $token token
     *
     * @return string
     */
    private function __decryptToken($token) {
        $tokenKey = C('tokenKey');
        return $this->__xxteaDecrypt(pack("H*", $token), $tokenKey);
    }

    /**
     * 创建token
     * @param $uid
     *
     * @return string
     */
    public function _createToken($uid) {
        $tokenKey = C('tokenKey');
        $rand = \Org\Util\String::randString(6);
        $token = bin2hex(\Think\Crypt\Driver\Xxtea::encrypt($uid . '|' . $rand, $tokenKey));
        return bin2hex(\Think\Crypt\Driver\Xxtea::encrypt($uid . '|' . $rand, $tokenKey));
    }

    /**
     * 获取用户uid
     * @return mixed
     */
    private function __getUid() {
        $token = $this->__decryptToken(I('param.token'));
        list($uid, $rand) = explode('|', $token);
        return $uid;
    }

    /**
     * token绑定
     * @param $token token
     * @param $devid 设备id
     *
     * @return \Think\Model
     */
    protected function _bindToken($token, $devid) {
        $UserTokenModel = D('UserTokenModel');
        return $UserTokenModel->bind($token, $devid);
    }

    /**
     * token验证
     * @return bool
     */
    protected function _tokenCheck() {

        $this->setG();
        $UserTokenModel = D('UserToken');
        $checkresult = $UserTokenModel->verify($this->G['token'], $this->G['mac']);
        if (!$checkresult) {
            $this->outPut(null, 3);
            exit;
        }
        return true;
    }

    /**
     * 获取缓存的key值
     * @param type $function_name
     * @param type $ignore_param
     * @param type $key
     * @return type
     */
    protected function _getCacheKey($function_name, $ignore_param = array(), $key = false) {

        if ($function_name && $key) {
            return $function_name . '_' . md5($key);
        }

        $params = array_merge($_GET, $_POST);
        foreach ($params as $k => $v) {
            if (!trim($v) && trim($v) != '0') {
                unset($params[$k]);
                continue;
            }
            if (trim($k) == 'sig') {
                unset($params[$k]);
                continue;
            }
            if ($ignore_param && is_array($ignore_param) && in_array($k, $ignore_param)) {
                unset($params[$k]);
                continue;
            }
        }
        ksort($params);
        return $function_name . '_' . md5(json_encode($params));
    }

    /**
     * api 缓存
     * @param type $key
     * @param type $value
     * @return type
     */
    protected function apiCache($key, $value = false) {

        if (strtolower(trim($this->api_cache_type)) == 'redis') {
            static $redis = null;
            if (!$redis) {
                $redis = new \Common\Org\phpRedis('connect');
            }
            if ($redis::$redis && isset($redis::$redis->scoket)) {
                if ($value === false) {
                    $data = $redis::$redis->get($key);
                    if (is_string($data)) {
                        $data = @json_decode($data, true);
                    }
                    return $data;
                }
                if (is_array($value)) {
                    $value = @json_encode($value);
                }
                $redis::$redis->setex($key, self::CACHE_TIME_OUT, $value);
            }
        }
        if ($value === false) {
            return S($key);
        }
        S($key, $value, self::CACHE_TIME_OUT);
    }

}
