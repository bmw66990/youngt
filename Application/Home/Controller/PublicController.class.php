<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/24
 * Time: 9:58
 */

namespace Home\Controller;
/**
 * Class PublicController
 * @package Home\Controller
 */
class PublicController extends CommonController {
    /**
     * @var bool 是否验证city
     */
    protected $cityCheck = false;

    /**
     * 城市选择页
     */
    public function city() {
        $city_ename = I('get.ename', '', 'strval');
        if ($city_ename) {
            if (APP_DOMAIN == 'localhost' || APP_DOMAIN == 'ce.com') {
                redirect(U('Index/index', array('site' => $city_ename)));
            } else {
                redirect('http://' . $city_ename . '.' . APP_DOMAIN);
            }
        }
        $city                   = cookie('city');
        $soncity_where['czone'] = $city['czone'];
        $son_city               = $this->_getAllCityInfo($soncity_where);
        $all_cities             = $this->_getCategoryList('city');
        $all_city               = $this->__array_sort($all_cities, 'letter');
        $hot_city               = $this->_hotCity();
        $this->assign('hot_city',$hot_city);
        $this->assign('domain', APP_DOMAIN);
        $this->assign('son_city', $son_city);
        $this->assign('all_city', $all_city);
        $this->_getWebTitle(array('title' => '选择城市'));
        $this->display();
    }

    /**
     * 异步切换城市处理
     */
    public function ajaxChangeCity() {
        if (IS_AJAX) {
            $host     = I('get.host', '', 'strval');
            @list($site,$_,$_) = explode('.',$host);
            $cityInfo = $this->_getCityInfo();
            if(!$cityInfo){
                $City = new \Home\Model\CityModel();
                $cityInfo = $City->getCityByEname('yangling');
                cookie('city', $cityInfo);
            }
            if($site){
                if(($site == 'www' || $site == 'youngt') && APP_DOMAIN == 'youngt.com'){
                    $data = array('status' => 1, 'city'=>$cityInfo,'url'=>"http://".$cityInfo['ename'].'.'.APP_DOMAIN);
                }else{
                    $city     = M('category')->where(array('zone' => 'city', 'ename' => $site))->find();
                    if ($city && $cityInfo['id'] != $city['id']) {
                        cookie('city', $city);
                    }
                    $data = array('status' => 1, 'city'=>ternary($city,$cityInfo),'url'=>'');
                }
            }else{
                $data = array('status' => 1, 'city'=>$cityInfo,'url'=>'');
            }
        } else {
            $data = array('status' => -1, 'error' => '非法操作');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 异步加载城市选择
     */
    public function getProvince() {
        //城市选择
        if (IS_AJAX) {
            $where['fid'] = 0;
            $province     = $this->_getCategoryList('province');
            $data         = array('status' => 1, 'data' => $province);
        } else {
            $data = array('status' => -1, 'error' => '非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 省份，城市二级菜单显示；
     */
    public function getCities() {
        if (IS_AJAX) {
            $id = I('get.id', 0, 'intval');
            if ($id) {
                $where['czone'] = M('category')->getFieldById($id, 'czone');
                $city           = $this->_getAllCityInfo($where);
                $data           = array('status' => 1, 'data' => $city);
            } else {
                $data = array('status' => -1, 'error' => '参数错误');
            }
        } else {
            $data = array('status' => -1, 'error' => '非法请求');
        }
        $this->ajaxReturn($data);

    }

    /**
     * 搜索城市
     */
    public function searchCity() {
        if (IS_AJAX) {
            $key = I('get.key', '', 'strval');
            if ($key) {
                $Cate              = M("category");
                $map['zone']       = 'city';
                $map['ename|name'] = $key;
                $ename             = $Cate->where($map)->getfield('ename');
                if ($ename) {
                    $data['status'] = 1;
                    $data['ename']  = $ename;
                } else {
                    $data['status'] = -1;
                    $data['error']  = '该城市暂未开通';
                }
            } else {
                $data['status'] = -1;
                $data['error']  = '参数错误！';
            }
            $this->ajaxReturn($data);
        }
    }

    /**
     * 城市列表排序
     * @param $arr
     * @param $keys
     * @param string $type
     * @return array
     */
    private function __array_sort($arr, $keys, $type = 'asc') {
        $key_value = $new_array = array();
        foreach ($arr as $k => $v) {
            $key_value[$v[$keys]][] = $v;
        }
        return $key_value;
    }

    /**
     * 获取城市相关信息
     * @param array $where 查询条件
     * @param string $field 查询字段
     * @return mixed
     */
    protected function _getAllCityInfo($where = array()) {
        $data = $this->_getCategoryList('city', $where);
        return $data;
    }

    /**
     * 用户登录
     */
    public function login() {
        //判断用户是否已经登录

        if ($this->_getUserId()) {
            redirect(U('Index/index'));
        }
        //记录用户来源
        if (isset($_GET['referer'])) {
            $jumpUrl = urldecode($_GET['referer']);
        } else if (isset($_SERVER['HTTP_REFERER'])) {
            $jumpUrl = $_SERVER['HTTP_REFERER'];
        } else {
            $jumpUrl = U('Index/index');
        }
        session('jumpUrl',$jumpUrl);
        $this->_getWebTitle(array('title' => '用户登录'));
        $this->display();
    }

    /**
     * 获取验证码
     */
    public function verify() {
        $Verify           = new \Think\Verify();
        $Verify->codeSet  = '0123456789';
        $Verify->fontSize = 40;
        $Verify->length   = 5;
        $Verify->useNoise = true;
        $Verify->entry();
    }

    /**
     * @param $code      验证码
     * @param string $id
     * @return bool
     */
    protected function _checkVerify($code, $id = '') {
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }

    /**
     * 用户登录处理
     */
    public function doLogin() {
        if (IS_POST) {
            $account   = I('post.account', '', 'strval');
            $password  = I('post.password', '', 'strval');
            $autoLogin = I('post.autoLogin', 0, 'intval');
            $code      = I('post.code',0,'intval');
            $data = $this->__checkLogin($account,$password,$code,1);
            if($data && isset($data['error'])){
                redirect(U('Public/login',array('error'=>base64_encode($data['error']))));
            }
            $where['username|email|mobile'] = $account;
            $where['password']              = encryptPwd($password);
            $saveType                       = $autoLogin ? 'cookie' : 'session';
            $field                          = "id,username,password,mobile";
            $this->_loginJumpUrl($where, '', $field, $saveType);
        } else {
            redirect(U('Public/login',array('error'=>base64_encode('非法请求'))));
        }
    }

    /**
     * 检测用户
     */
    public function checkUser() {
        if (IS_AJAX) {
            $act = I('get.act', '', 'strval');
            switch ($act) {
                case 'login' :
                    $account  = I('post.account', '', 'strval');
                    $password = I('post.password', '', 'strval');
                    $code     = I('post.verify', 0, 'intval');
                    $type     = I('post.type', 0, 'intval');
                    $data     = $this->__checkLogin($account, $password, $code, $type);
                    break;
                case 'reg'   :
                    $mobile     = I('post.mobile', '', 'strval');
                    $code       = I('post.code', 0, 'intval');
                    $password   = I('post.password', '', 'strval');
                    $repassword = I('post.repassword', '', 'strval');
                    $data       = $this->__checkReg($mobile, $code, $password, $repassword);
                    break;
                case 'user'  :
                    $mobile = I('post.mobile', '', 'strval');
                    $verify = I('post.verify',0,'strval');
                    $is_verify = I('post.is_verify','','strval');
                    $act    = I('post.act', '', 'strval') ? true : false;
                    if($is_verify){
                        $data   = $this->__checkUser($mobile, $act,$verify,true);
                    }else{
                        $data   = $this->__checkUser($mobile, $act);
                    }
                    break;
                case 'field' :
                    $key  = I('post.key', '', 'strval');
                    $val  = I('post.val', '', 'strval');
                    $data = $this->__checkField($key, $val);
                    break;
                case 'resetPwd' :
                    $mobile     = I('post.mobile', '', 'strval');
                    $code       = I('post.code', 0, 'intval');
                    $password   = I('post.password', '', 'strval');
                    $repassword = I('post.repassword', '', 'strval');
                    $data       = $this->__resetPwd($mobile,$code, $password, $repassword);
                    break;
                case 'userLoginBind':
                    $account  = I('post.account', '', 'strval');
                    $password = I('post.password', '', 'strval');
                    $data     = $this->__userBindCheck($account, $password);
                    break;
                case 'userRegBind':
                    $mobile     = I('post.mobile', '', 'strval');
                    $code       = I('post.code', 0, 'intval');
                    $password   = I('post.password', '', 'strval');
                    $repassword = I('post.repassword', '', 'strval');
                    $data       = $this->__checkReg($mobile, $code, $password, $repassword);
                    break;
                default    :
                    $data = array('status' => -1, 'error' => '非法请求');
                    break;
            }
        } else {
            $data = array('status' => -1, 'error' => '非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 登陆验证
     */
    private function __checkLogin($account, $password, $code, $type) {
        if (!$account) {
            $data = array('status' => -1, 'error' => '请输入账户');
        } elseif (!$password) {
            $data = array('status' => -1, 'error' => '请输入密码');
        } elseif ($type != 1) {
            if (!$code) {
                $data = array('status' => -1, 'error' => '请输入验证码');
            } elseif ($this->_checkVerify($code) === false) {
                $data = array('status' => -1, 'error' => '验证码错误');
            }
        }
        if (!$data) {
            $where['username|email|mobile'] = $account;
            $user                           = D('User')->getDetail($where, 'id,password');
            if (empty($user) || $user['password'] != encryptPwd($password)) {
                $data = array('status' => -1, 'error' => '账号或密码错误');
            } else {
                if ($type == 1) $this->_saveLoginInfo($user, 'session');
                $data = array('status' => 1);
            }
        }
        return $data;
    }

    /**
     * 联合登陆绑定验证
     * @param $account
     * @param $password
     */
    public function __userBindCheck($account, $password){
        if (!$account) {
            $data = array('status' => -1, 'error' => '请输入账户');
        } elseif (!$password) {
            $data = array('status' => -1, 'error' => '请输入密码');
        }
        if(!isset($data) && !$data){
            $where['username|email|mobile'] = $account;
            $user                           = D('User')->getDetail($where, 'id,password');
            if (empty($user) || $user['password'] != encryptPwd($password)) {
                $data = array('status' => -1, 'error' => '账号或密码错误');
            } else {
                $data = array('status' => 1);
            }
        }
        return $data;
    }

    /**
     * 注册信息验证
     */
    private function __checkReg($mobile,$code, $password, $repassword) {
        $userRes = $this->__checkUser($mobile, false);
        if ($userRes['status'] == -1) return $userRes;
        $codeRes = $this->_checkCode($code, $mobile, 'pcReg');
        if ($codeRes === false) return array('status' => -1, 'error' => '验证码错误');
        if (strlen($password) < 6 || strlen($password) > 20) return array('status' => -1, 'error' => '密码长度必须是6-18位数字、字母、下划线等');
        if (strlen($repassword) < 6 || strlen($repassword) > 20) return array('status' => -1, 'error' => '密码长度必须是6-18位数字、字母、下划线等');
        if ($password != $repassword) return array('status' => -1, 'error' => '两次密码不一致');
        return array('status' => 1);
    }

    private function __resetPwd($mobile,$code, $password, $repassword) {
        $userRes = $this->__checkUser($mobile, true);
        if ($userRes['status'] == -1) return $userRes;
        if (!$code) return array('status' => -1, 'error' => '请输入验证码');
        $codeRes = $this->_checkCode($code, $mobile, 'pcnpwd');
        if ($codeRes === false) return array('status' => -1, 'error' => '验证码错误');
        if (strlen($password) < 6 || strlen($password) > 20) return array('status' => -1, 'error' => '密码长度必须是6-18位数字、字母、下划线等');
        if (strlen($repassword) < 6 || strlen($repassword) > 20) return array('status' => -1, 'error' => '密码长度必须是6-18位数字、字母、下划线等');
        if ($password != $repassword) return array('status' => -1, 'error' => '两次密码不一致');
        $upData = array('password' => encryptPwd($password));
       // $res    = M('user')->where(array('mobile' => $mobile))->save($upData);
        $res    = M('user')->where("mobile='{$mobile}'")->save($upData);
        if ($res) {
            return array('status' => 1);
        } else {
            return array('status' => -1, 'error' => '新密码不能与原始密码相同');
        }
    }

    /**
     * 检测用户是否存在
     */
    private function __checkUser($mobile,$act = false,$verify = '',$is_verify = false) {
        if (!$mobile) return array('status' => -1, 'error' => '请输入手机号');
        if (!checkMobile($mobile)) return array('status' => -1, 'error' => '手机号码格式不正确');
        $where['mobile|username|email'] = $mobile;
        $userCount                      = D('User')->getTotal($where);
        if ($act === false) {
            if ($userCount) {
                return array('status' => -1, 'error' => '手机号码已注册');
            } else {
                if($is_verify === true){
                    if(!$verify || !is_numeric($verify) || !$this->_checkVerify($verify)){
                        return array('status' => -1, 'error' => '图形验证码错误');
                    }
                }
                return array('status' => 1);
            }
        } else {
            if ($userCount) {
                if($is_verify === true){
                    if(!$verify || !is_numeric($verify) || !$this->_checkVerify($verify)){
                        return array('status' => -1, 'error' => '图形验证码错误');
                    }
                }
                return array('status' => 1);
            } else {
                return array('status' => -1, 'error' => '手机号码尚未注册');
            }
        }
    }

    /**
     * 检测字段
     */
    private function __checkField($key, $val) {
        $error = '';
        switch ($key) {
            case 'mobile'     :
                $res = $this->__checkUser($val);
                if ($res['status'] == -1) $error = $res['error'];
                break;
            case 'verify'     :
                if(!$val || !is_numeric ($val)){
                    $error = '请输入图形验证码或图形验证码格式错误';
                }
                break;
            case 'code'       :
                if (!$val) {
                    $error = '请输入手机验证码';
                } elseif (!session('mobile')) {
                    $error = '未生成手机验证码';
                } else {
                    $res = true;//$this->_checkCode($val, session('mobile'), 'pcReg');
                    if ($res === false) {
                        $error = '手机验证码错误';
                    }
                }
                break;
            case 'password'   :
                if (strlen($val) < 6 || strlen($val) > 20) {
                    $error = '密码长度必须是6-18位数字、字母、下划线等';
                } else {
                    session('password', $val);
                }
                break;
            case 'repassword' :
                if (strlen($val) < 6 || strlen($val) > 20) {
                    $error = '密码长度必须是6-18位数字、字母、下划线等';
                } elseif (session('password') && $val != session('password')) {
                    $error = '两次密码输入不一致';
                } else {
                    session('password', null);
                }
                break;
            default           :
                $error = '请求非法';
                break;
        }
        if ($error) {
            $data = array('status' => -1, 'field' => $key, 'error' => $error);
        } else {
            $data = array('status' => 1, 'field' => $key);
        }
        return $data;
    }
    protected function _checkCode($code, $mobile, $action) {
        return D('Sms')->checkMobileVcode($mobile,$code,$action);

        /*
        $type=C('SMSTYPE');
        if($type=='MonSend'){

        }
        $where = array('code' => $code, 'mobile' => $mobile, 'action' => $action, 'date' => date('Y-m-d'));
        $count = D('Sms')->getTotal($where);
        if ($count > 0) {
            return true;
        } else {
            return false;
        }*/
    }

    /**
     * QQ联合登陆
     */
    public function qqLogin() {
        $qq = new \Common\Org\QqLogin();
        $qq->login();
    }

    /**
     * qq联合登陆回调
     */
    public function qqCallBack() {
        $qq   = new \Common\Org\QqLogin();
        $data = $qq->callBack();
        if ($data['sns']) {
            $where['sns'] = $data['sns'];
            $this->_loginJumpUrl($where, 'qq');
        } else {
            redirect(U('Public/login',array('error'=>base64_encode($data['error']))));
        }
    }

    /**
     * 微信联合登陆
     */
    public function weChatLogin() {
        $code = I('get.code','','strval');
        if($code){
            $weChat = new \Common\Org\WeiXin();
            $info   = $weChat->getWeChatInfo($code);
            if (!isset($info['error']) || !$info['error']) {
                session('wxData',$info);
                $where['unid'] = $info['unionid'];
                $this->_loginJumpUrl($where, 'weChat');
            }
        }
        redirect(U('Public/login'));
    }

    /**
     * 联合登陆绑定用户账号
     */
    public function userBind() {
        //获取绑定方式
        $act = I('get.act', '', 'strval');
        if ($act == 'weChat') {
            $wxData = session('wxData');
            $name   = $wxData['nickname'];
        } else if ($act == 'qq') {
            $qqData = session('qqData');
            $name = $qqData['name'];
            $this->assign('sns', 'qzone:'.$qqData['openId']);
        }
        //获取当前登陆用户ID
        $id = $this->_getUserId();
        if ($id) {
            if ($qqData['openId']) {
                $url      = U('Member/bindQQ');
                $saveData = array('id' => $id, 'sns' => $qqData['sns']);
            } else {
                $url      = U('Member/bindWeixin');
                $saveData = array('id' => $id, 'unid' => $wxData['unid'], 'wxtoken' => $wxData['openid']);
            }
            $res = M('user')->save($saveData);
            if ($res) {
                $this->redirect($url);
            } else {
                redirect(U('Index/index'));
            }
        }
        $this->assign('name', $name);
        $this->_getWebTitle(array('title' => '用户绑定'));
        $this->display();
    }


    /**
     * 联合登陆逻辑处理
     */
    public function doUserBind(){
        if(IS_POST){
            $act = I('post.action','','strval');
            if($act == 'login_bind'){
                $this->doLogin();
            }elseif($act == 'reg_bind'){
                $this->doRegister();
            }else{
                redirect(U('Public/login'));
            }
        }else{
            redirect(U('Public/login'));
        }
    }

    /**
     * 登陆跳转页面
     * @param $where            查询用户条件
     * @param string $bindType 绑定用户类型
     * @param string $field 查询用户字段
     * @param string $saveType 保存用户方式
     */
    public function _loginJumpUrl($where, $bindType = '', $field = 'id', $saveType = 'session') {
        $user = M('user')->field($field)->where($where)->find();
        if ($user['id']) {
            $this->_saveLoginInfo($user, $saveType);
            if(session('wxData')){
                session('wxData',null);
            }
            if(session('qqData')){
                session('qqData',null);
            }
            if (session('jumpUrl')) {
                redirect(session('jumpUrl'));
            } else {
                redirect(U('Index/index'));
            }
        } else {
            if ($bindType) {
                redirect(U('Public/userBind', array('act' => $bindType)));
            } else {
                redirect(U('Public/login'));
            }
        }
    }

    /**
     * 保存用户登陆信息
     * @param $user_id      用户id
     * @param string $type 保存类型
     */
    protected function _saveLoginInfo($user, $type) {
        if ($type == 'cookie') {
            //通过cookie保存登陆信息
            session(C('MEMBER_AUTH_KEY'), $user['id']);
            cookie(C('MEMBER_AUTH_KEY'), $user['id']);
            cookie('m_' . $user['id'], md5($user['account'] . $user['password']), 86400 * 180);
        } else {
            //通过session保存用户登陆信息
            session(C('MEMBER_AUTH_KEY'), $user['id']);
        }
        $info['id']            = $user['id'];
        $info['login_time']    = time();
        $info['login_count']   = array('exp', 'login_count+1');
        $info['last_login_ip'] = get_client_ip();
        $sns = I('post.sns','','strval');
        if($sns){
            $info['sns'] = $sns;
            session('qqDate',null);
        }
        $wxData = session('wxData');
        if($wxData){
            $info['unid'] = $wxData['unionid'];
            session('wxDate',null);
        }
        M('user')->save($info);
        if (!empty($_COOKIE['cart'])) {
            $cookieCart = json_decode($_COOKIE['cart'], true);
            D('Cart')->updateCartWithCookie($user['id'], $cookieCart);
            setcookie('cart', null);
        }
    }

    /**
     * 用户注册
     */
    public function register() {
        //判断用户是否已经登录
        if ($this->_getUserId()) {
            redirect(U('Index/index'));
        }
        //2016.3.6号加 根据注册是添加的ip以及当天时间判断是否有恶意注册
        $ip=get_client_ip();
        $time=time();
        $map = array(
            'ip' => $ip,
            'create_time' => $time
        );
        $count = M('user')->where($map)->count();
        if($count>10){
            redirect(U('Index/index'));
        }
        //end
        $this->_getWebTitle(array('title' => '用户注册'));
        $this->display();
    }

    /**
     * 用户注册处理
     */
    public function doRegister() {
        $city = $this->_getCityInfo();
        $ip=get_client_ip();
        $data['mobile']      = I('post.mobile', '', 'trim');
        $data['password']    = encryptPwd(I('post.password', '', 'trim'));
        $data['username']    = $data['mobile'];
        $data['create_time'] = time();
        $data['active']      = 1;
        $data['score']       = C('POINTS.REG_USER') ? C('POINTS.REG_USER') : 20;
        $data['city_id']     = ternary($city['id'],0);
        $data['snsfrom']     = 'pcd';//注册来源
        $data['ip']            =$ip;
        $sns = I('post.sns','','strval');
        if($sns){
            $data['sns'] = $sns;
        }
        $wxData = session('wxData');
        if($wxData){
            $data['unid'] = $wxData['unionid'];
            $data['username'] = ternary($wxData['nickname'],$data['mobile']);
        }
        $res = $this->__checkReg($data['mobile'], I('post.code', '', 'intval'), I('post.password', '', 'trim'), I('post.repassword', '', 'trim'));
        if($res && $res['status'] == '-1'){
            if($sns){
                redirect(U('Public/userBind',array('act'=>'qq','error'=>base64_encode($res['error']))));
            }
            if($wxData){
                redirect(U('Public/userBind',array('act'=>'weChat','error'=>base64_encode($res['error']))));
            }
            redirect(U('Public/register',array('error'=>base64_encode($res['error']))));
        }
        $Model               = M();
        $Model->startTrans();
        $mid       = M('user')->add($data);
        $scoreData = array(
            'create_time' => time(),
            'user_id'     => $mid,
            'score'       => $data['score'],
            'action'      => 'register',
            'sumscore'    => $data['score']
        );
        $sid       = M('credit')->add($scoreData);
        if ($mid && $sid) {
            $Model->commit();
            $user['id'] = $mid;
            $this->_saveLoginInfo($user, 'session');
            if($wxData){
                session('wxData',null);
            }
            if(session('qqData')){
                session('qqData',null);
            }
            redirect(U('Index/index'));
        } else {
            $Model->rollback();
            redirect(U('Public/register'));
        }
    }

    /**
     * 找回密码
     */
    public function resetPwd() {
        $this->_getWebTitle(array('title' => '找回密码'));
        $this->display();
    }

    /**
     * 注销登录
     */
    public function logout() {
        if (session(C('MEMBER_AUTH_KEY'))) {
            cookie(C('MEMBER_AUTH_KEY'), null);
            cookie('m_' . session(C('MEMBER_AUTH_KEY')), null);
            session(C('MEMBER_AUTH_KEY'), null);
        }
        setcookie('cart', '', time() - 1, '/');
        redirect(U('Public/login'));
    }

    /**
     * 发送短信验证码
     */
    public function smsCode() {
        if (IS_AJAX) {
            $mobile   = I('post.mobile', '', 'strval');
            $reSetPwd = I('post.reSetPwd', 'pcreg', 'strval');
            session('mobile', $mobile);
            $data = $this->_sendSms($mobile, '', 'home', $reSetPwd);
            if ($data['status'] == 0) {
                $data = array('status' => 1, 'success' => '发送成功');
            }
        } else {
            $data = array('status' => -1, 'error' => '非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 提供团购信息
     */
    public function signUp() {
        //seo优化
        $this->_getWebTitle(array('title' => '团购报名'));
        $this->display();
    }

    /**
     * 弹出登陆框
     */
    public function popLogin() {
        $this->_getWebTitle(array('title' => '用户登录'));
        $this->display();
    }
    
   /**
     * 客户端下载
     */
    public function client() {
        $this->_getWebTitle(array('title' => '客户端下载'));
        $this->display();
    }
    
     /**
     * 用户反馈
     */
    public function userFeedback() {
        
        if (IS_AJAX) {
            $Model    = D('Feedback');
            $_POST['category'] = 'suggest';
            $_POST['create_time'] = time();
            $city_id=$this->_getCityInfo();
            $_POST['city_id']=0;
            $_POST['address']='未知区域';
            if(isset($city_id['id'])){
                 $_POST['city_id'] = $city_id['id'];
                 $_POST['address'] = $city_id['name'];
            }
            if(isset($_POST['content'])){
                $_POST['content'] = "{$_POST['content']},用户姓名:{$_POST['title']},邮箱：{$_POST['contact']}";
            }
            
            $verify = I('post.verify', 0, 'intval');
           // if ($this->_checkVerify($verify) === true) {
            if(true){
                $result = $Model->insert();
                if ($result === false) {
                    $data = array('status' => -1, 'error' => $Model->getError());
                } else {
                    $data = array('status' => 1, 'error' => '成功');
                }
            } else {
                $data = array('status' => -1, 'error' => '验证码错误');
            }
        } else {
            $data = array('status' => -1, 'error' => '请求非法');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 客户端下载 旧版
     */
    public function client_20151112() {
        $this->_getWebTitle(array('title' => '客户端下载'));
        $this->display();
    }

    /**
     * 分站加盟以及团购报名处理
     */
    public function doFeedBack() {
        if (IS_AJAX) {
            $Model    = D('Feedback');
            $province = I('post.province', '', 'strval');
            $city     = I('post.city' . '' . 'strval');
            $county   = I('post.county', '', 'strval');
            if (I('post.qq')) $_POST['phone'] = I('post.qq');
            if ($province && $province != '省份') $_POST['address'] = $province . '-' . $city . '-' . $county;
            $verify = I('post.verify', 0, 'intval');
            if ($this->_checkVerify($verify) === true) {
                $result = $Model->insert();
                if ($result === false) {
                    $data = array('status' => -1, 'error' => $Model->getError());
                } else {
                    $data = array('status' => 1, 'error' => '成功');
                }
            } else {
                $data = array('status' => -1, 'error' => '验证码错误');
            }
        } else {
            $data = array('status' => -1, 'error' => '请求非法');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 招商加盟
     */
    public function merchants() {
        $this->_getWebTitle(array('title' => '青团招商'));
        $this->display();
    }

    /**
     * 券号验证
     */
    public function verifyCoupon() {
        $this->_getWebTitle(array('title' => '青团券验证'));
        $this->display();
    }

    /**
     * 券号验证处理
     */
    public function doVerifyCoupon() {
        if (IS_AJAX) {
            $id  = str_replace(' ', '', I('post.id', 0, 'strval'));
            $act = I('post.act', '', 'strval');
            if ($id && $act) {
                $Model      = D('Coupon');
                $couponInfo = $Model->checkCoupon($id, $act);
                $data       = array('status' => $couponInfo['status'], 'content' => $this->_verifyCouponInfo($couponInfo));
            } else {
                $data = array('status' => -1, 'content' => '非法请求');
            }
        } else {
            $data = array('status' => -1, 'content' => '非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 组装前台提示信息
     * @param $data
     */
    public function _verifyCouponInfo($data) {
        $html = "<p>券号状态：" . $data['data'] . "</p>";
        if (isset($data['info'])) {
            $html .= "<p>青团券号：" . $data['info']['id'] . "</p>";
            $html .= "<p>团单标题：" . $data['info']['product'] . "</p>";
            $html .= "<p>团单价格：" . $data['info']['team_price'] . "</p>";
            if (isset($data['info']['expire_time']) && $data['info']['expire_time']) {
                $html .= "<p>过期时间：" . date('Y-m-d', $data['info']['expire_time']) . "</p>";
            }
            if (isset($data['info']['consume_time']) && $data['info']['consume_time']) {
                $html .= "<p>消费时间：" . date('Y-m-d H:i:s', $data['info']['consume_time']) . "</p>";
            }
        }
        return $html;
    }

    /**
     * 获取最近浏览记录
     */
    public function history() {
        if (IS_AJAX) {
            $history = cookie('history');
            if (get_magic_quotes_gpc()) {
                $history = stripslashes($history);
            }
            $history = unserialize($history);
            $data    = array('status' => 1, 'data' => $history);
        } else {
            $data = array('status' => -1, 'error' => '非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 异步判断用户是否登陆
     */
    public function isLogin() {
        if (IS_AJAX) {
            if ($this->uid) {
                $userInfo = $this->_getUserInfo();
                $city_info = cookie('city');
                $data     = array('status' => 1,'id'=>  ternary($userInfo['id'], ''),'city'=>"{$city_info['name']}({$city_info['id']})", 'username' => ternary($userInfo['username'], $userInfo['mobile']),'mobile'=>$userInfo['mobile']);
            } else {
                $data = array('status' => -1);
            }
            $this->ajaxReturn($data);
        }
    }

    /**
     * 评论晒图
     */
    public function uploadReview() {
        $data = $this->upload('img', 'review');
        if($data) {
            $info['state'] = 'SUCCESS';
            $str = $img = '';
            foreach($data as $row) {
                $str .= '<img src="' . getImagePath($row['newpath'] . '/' . $row['savename']) . '" width="50">';
                $img .= getImagePath($row['newpath'] . '/' . $row['savename']);
            }
            $info['url'] = $str;
            $info['img'] = $img;
            $info['msg'] = '上传成功';
        } else {
            $info['state'] = 'ERROR';
            $info['msg']   = '上传失败';
        }
        ob_end_clean();
        $this->ajaxReturn($info);
    }
}