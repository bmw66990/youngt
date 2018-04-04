<?php

namespace Api\Controller;

class UserController extends CommonController {

    protected $checkUser = false;

    /**
     * 用户登录
     */
    public function login() {

        // 参数接收
        $city_id = I('post.city_id', '', 'trim');
        $accessName = I('post.accessName', '', 'trim');
        $password = I('post.password', '', 'trim');
        $code = I('post.code', '', 'trim');
        $info = I('post.info', '', 'trim');
        $loginType = I('get.loginType', '', 'trim');
        //2016.3.12 校验新接口

        $jy = I('post.jy', '');
        if($jy)
        {
            $jy=strtolower($jy);
        }else{
            $jy='old';
        }
        //end
        if (!$loginType) {
            $loginType = I('post.loginType', 'default', 'trim');
        }

        // 非法参数判断
        $user = D('User');
        $loginType = strtolower($loginType);
        if (!$user->isLoginType($loginType)) {
            $this->outPut(null, 1021);
        }

        // 根据登录类型 来登录
        $res = array('error' => '');
        switch ($loginType) {
            // 微信登陆
            case 'weixin':
                $this->_checkblank('code');
                $res = $user->weixinLogin($code, $city_id);
                break;
            // QQ登陆
            case 'qzone':
                $res = $user->qqWebLogin();
                break;
            // QQ绑定时登录界面
            case 'qqbindlogin':
                $call_back_url = 'http://' . $_SERVER['HTTP_HOST'] . '/PayCallBack/qqBindWebLoginCallbackHandle';
                $res = $user->qqWebLogin($call_back_url);
                break;
            // 手机验证码登陆
            case 'quicklogin':
                $this->_checkblank(array('accessName', 'password'));
                $res = $user->quickLogin($accessName, $password, $city_id,$jy);
                break;
            // 默认方式登陆
            case 'default':
            default:
                $this->_checkblank(array('accessName', 'password'));
                $res = $user->defaultLogin($accessName, $password);
                break;
        }
        // 错误提示
        if (isset($res['error'])) {
            $this->_writeDBErrorLog($res, $user, 'api');
            $this->outPut(null, -1, null, ternary($res['error'], ''));
        }
        if (!$res) {
            $this->_writeDBErrorLog($res, $user, 'api');
            $this->outPut(null, 1022);
        }

        // 生成token
        $uid = $res['id'];
        $token = $this->_createToken($uid);

        // 整理返回数据
        $data = array(
            'token' => $token,
        );

        $this->outPut($data, 0, null);
    }

    /**
     * 用户注册 手机号注册
     */
    public function register() {

        // 参数接收
        $this->_checkblank(array('accessName', 'password', 'vCode'));
        $accessName = I('post.accessName', '', 'strval');
        $password = I('post.password', '', 'strval');
        $vCode = I('post.vCode', '', 'strval');
        $cityId = I('post.cityId', '', 'strval');
        //2016.3.12 校验新接口
        $jy = I('post.jy', '');
        if($jy)
        {
            $jy=strtolower($jy);
        }else{
            $jy='old';
        }
        // 非法参数判断
        if (!checkMobile($accessName)) {
            $this->outPut(null, 1023);
        }

        $user = D('User');
        $res = $user->mobileRegister($accessName, $password, $vCode, 'reg', $cityId, false,$jy);
        if (!$res) {
            $this->_writeDBErrorLog($res, $user, 'api');
            $this->outPut(null, 1024);
        }

        $this->outPut(null, 0, null);
    }

    /**
     * 手机验证码
     */
    public function mobileVerify() {

        // 参数接收
        $this->_checkblank(array('accessName', 'action'));
        $accessName = I('post.accessName', '');
        $action = I('post.action', '');
        $voices = I('post.voices', 0, 'intval');
       
        // 非法参数判断
        $user = D('User');

        $action = strtolower($action);
        if (!$user->isActionType($action)) {
            $this->outPut(null, 1025);
        }
        if (!checkMobile($accessName)) {
            $this->outPut(null, 1023);
        }

        // 根据用户行为校验
        $userRes = $user->isRegister(array('mobile' => trim($accessName)));
        switch (trim($action)) {
            case 'reg':
            case 'bindmobile':
            case 'changemobile':
            case 'pcreg':
                if ($userRes) {
                    if ($action == 'reg') {
                        $this->outPut(null, 1033);
                    }
                    $this->outPut(null, 1026);
                }
                break;
            case 'npwd':
            case 'pcnpwd':
                if (!$userRes) {
                    $this->outPut(null, 1027);
                }
                break;
        }

        $sms_day_count = C('SMS_DAY_COUNT');
        $sms_time_out = C('SMS_TIME_OUT');
        $sms_minute_time_out = C('SMS_MINUTE_TIME_OUT');
        $sms_hours_time_out = C('SMS_HOURS_TIME_OUT');
        $sms_hours_count = C('SMS_HOURS_COUNT');
        // 验证是否正常获取
        $sms = M('sms');
        $where = array('mobile' => trim($accessName), 'date' => date('Y-m-d'), 'action' => $action);
        $vCodeRes = $sms->where($where)->find();
        if (isset($vCodeRes['num']) && intval($vCodeRes['num']) >= $sms_day_count) {
            $this->outPut(null, 1028);
        }

        if (isset($vCodeRes['create_time']) && intval(time() - $vCodeRes['create_time']) < $sms_minute_time_out) {
            $this->outPut(null, 1034);
        }

        $client_ip = get_client_ip();
        $client_ip = str_replace('.', '_', $client_ip);
        $sms_key = "sms_{$accessName}_{$action}_{$client_ip}";
        $sms_time_out_data = S($sms_key);
        if (isset($sms_time_out_data['expire_time']) && intval(time() - $sms_time_out_data['expire_time']) < $sms_hours_time_out) {
            if (isset($sms_time_out_data['num']) && $sms_time_out_data['num'] >= $sms_hours_count) {
                $this->outPut(null, 1035);
            }
        }

        if (!isset($sms_time_out_data['num']) || !isset($sms_time_out_data['expire_time']) || intval(time() - $sms_time_out_data['expire_time']) >= $sms_hours_time_out) {
            $sms_time_out_data = array(
                'num' => 1,
                'expire_time' => time(),
            );
        } else {
            $sms_time_out_data['num'] = $sms_time_out_data['num'] + 1;
        }
        S($sms_key, $sms_time_out_data, $sms_hours_time_out);

        // 重新发送验证码
        $code = '';
        if ($vCodeRes) {
            $createTime = $vCodeRes['create_time'];
            $code = $vCodeRes['code'];
            $updateData = array('num' => $vCodeRes['num'] + 1);
            if (time() > $createTime + $sms_time_out) {
                $code = $updateData['code'] = $user->getCode();
                $updateData['create_time'] = time();
            }
            $sms->where(array('id' => $vCodeRes['id']))->save($updateData);
        } else {
            // 新加手机验证码
            $code = $user->getCode();
            $data = array(
                'mobile' => trim($accessName),
                'code' => $code,
                'create_time' => time(),
                'action' => $action,
                'date' => date('Y-m-d'),
                'num' => 1,
            );
            $sms->add($data);
        }

        // 判断是否语音验证码
        if (trim($voices)) {
            $sendVoices = new \Common\Org\sendVoices();
            $res = $sendVoices->sendVoice(trim($accessName), $code);
            if (isset($res['status']) && intval($res['status']) == 1) {
                $this->outPut(array('is_client_send' => false), 0, null);
            }
        }

        // 判断是否手机端发送验证码
        $clent_var = isset($_SERVER['HTTP_CLIENTVERSION']) && trim($_SERVER['HTTP_CLIENTVERSION'])?$_SERVER['HTTP_CLIENTVERSION']:0;
        $isClientSend = C('IS_CLIENT_SEND');
        if($clent_var && strcmp($clent_var, '4.0.8') > 0){
            $isClientSend = C('IS_CLIENT_SEND_NEW');
        }
        
        if ($isClientSend) {
            $this->outPut(array('is_client_send' => $isClientSend), 0, null);
        }

        // 服务器发送验证码
        $msg = $user->getSendSmsMsg($action);
        $msg = str_replace('MSMCode', $code, $msg);
        $res = $this->_sms(trim($accessName), $msg);
        if (isset($res['status']) && intval($res['status']) == 0) {
            $this->outPut(array('is_client_send' => $isClientSend), 0, null);
        }
        $this->_writeDBErrorLog($res, $user, 'api');
        $this->outPut(null, 1029, null, $res['data']);
    }

    /**
     * 找回密码
     */
    public function findPwd() {

        // 参数接收
        $this->_checkblank(array('accessName', 'password', 'vCode'));
        $accessName = I('post.accessName', '','trim');
        $password = I('post.password', '','trim');
        $vCode = I('post.vCode', '','trim');

        // 非法参数判断
        if (!checkMobile($accessName)) {
            $this->outPut(null, 1023);
        }

        // 校验手机验证码是否正确
        $user = D('User');
        $res = $user->checkMobileVcode($vCode, $accessName, 'npwd');
        if (!$res) {
            $this->_writeDBErrorLog($res, $user, 'api');
            $this->outPut(null, 1030);
        }
        
        $uid = $user->where(array('mobile' => strval($accessName)))->field('id')->find();
        if(!$uid || !isset($uid['id'])){
            $this->outPut(null, 1031);
        }

        // 修改密码
        $data['password'] = encryptPwd(trim($password));
        $res = $user->where(array('id' => $uid['id']))->save($data);
        if ($res === false) {
            $this->_writeDBErrorLog($res, $user, 'api');
            $this->outPut(null, 1031);
        }

        $this->outPut(null, 0, null);
    }

    /**
     * 校验手机验证码是否正确
     */
    public function checkMobileVcode($vCode, $accessName, $action) {
        // 参数接收
        $this->_checkblank(array('accessName', 'action', 'vCode'));
        $accessName = I('post.accessName', '');
        $vCode = I('post.vCode', '');
        $action = I('post.action', '');
        $jy = I('post.jy', '');
        // 非法参数判断
        $user = D('User');

        $action = strtolower($action);
        if (!$user->isActionType($action)) {
            $this->outPut(null, 1025);
        }
        if (!checkMobile($accessName)) {
            $this->outPut(null, 1023);
        }
        //2016.3.12 号加新版短信校验
        if($jy){
            $jy=strtolower($jy);
            $res = $user->checkMobileVcode($vCode, $accessName, $action,$jy);
        }else{
            $res = $user->checkMobileVcode($vCode, $accessName, $action);
        }
        //end
        if (!$res) {
            $this->_writeDBErrorLog($res, $user, 'api');
            $this->outPut(null, 1030);
        }

        $this->outPut(null, 0, null);
    }
    /**
     * 手机app启动请求初始化 页面
     */
    public function appLoadInit() {

        // 接收参数
        $lng = I('get.lng', 0, 'doubleval');
        $lat = I('get.lat', 0, 'doubleval');
        $distance = I('get.distance ', 5, 'doubleval');
        $plat = I('get.plat ', 'android', 'trim');
        $app_ver = I('get.ver', '', 'trim');
        $cache_ver = I('get.cache_ver', 0, 'trim');

        // 定位城市
        $res = array();
        $team = D('Team');
        if (trim($lng) && trim($lat)) {
            $lngLatSquarePoint = $team->returnSquarePoint($lng, $lat, $distance);
            $where = array(
                'partner.city_id' => array('GT', 0),
            );
            $where['_string'] = "partner.`long`>='{$lngLatSquarePoint['left-top']['lng']}' AND partner.lat>='{$lngLatSquarePoint['left-bottom']['lat']}' AND partner.`long`<='{$lngLatSquarePoint['right-bottom']['lng']}' AND partner.`lat`<='{$lngLatSquarePoint['left-top']['lat']}'";
            $distanceField = $team->getMysqlDistanceField($lat, $lng);
            $field = array(
                'city_id' => 'city_id',
                $distanceField => 'distance',
            );
            $query_table = M('partner')->where($where)->field($field)->order('distance asc')->limit(20)->select(false);
            $res = M('partner')->table("($query_table) as t")->field(array('t.city_id' => 'city_id', 'count(t.city_id)' => 'city_id_count'))->group('t.city_id')->order('city_id_count desc')->find();
        }

        $city_id = '';
        $city_name = '';
        if (isset($res['city_id']) && trim($res['city_id'])) {
            $city_res = $this->_getCategoryList('city');
            $city_id = $res['city_id'];
            $city_name = ternary($city_res[$city_id]['name'], '');
        }

        // 获取启动页面
        $load_image = C('APP_LOAD_IMAGE');
        if (isset($load_image['url']) && trim($load_image['url'])) {
            $load_image['url'] = getImagePath($load_image['url']);
            $load_image['md5_file'] = md5_file($load_image['url']);
        }

        // 升级信息
        $upgrade = C('AppUpdateAndroid');
        if (strtolower($plat) == 'ios') {
            $upgrade = C('AppUpdateIos');
        }
        $service_ver = ternary($upgrade['ver'], '');
        $is_upgrade = 'N';
        if ($app_ver && strcmp($service_ver, $app_ver) > 0) {
            $is_upgrade = 'Y';
        }
        $upgrade_info = array(
            'version' => $service_ver,
            'is_force' => ternary($upgrade['is_force'], ''),
            'is_upgrade' => $is_upgrade,
            'description' => ternary($upgrade['description'], ''),
            'download_url' => ternary($upgrade['url'], ''),
        );

        // 缓存是否更新
        $update_cache = array(
            'service_cache_var' => strval($cache_ver),
            'is_update_cache' => 'N',
        );
        $service_cache_var = M('system')->where(array('keys' => 'update_cache'))->getField('values');
        if (!$service_cache_var) {
            $service_cache_var = 0;
        }
        if (intval($cache_ver) < intval($service_cache_var)) {
            $update_cache = array(
                'service_cache_var' => strval($service_cache_var),
                'is_update_cache' => 'Y',
            );
        }
        $data = array(
            'city_id' => $city_id,
            'city_name' => $city_name,
            'load_image' => $load_image ? $load_image : '',
            'upgrade' => $upgrade_info,
            'update_cache' => $update_cache,
        );
        $this->outPut($data, 0, null);
    }

    /**
     * 获取用户地址列表
     */
    public function getUserAddressList() {

        // 检测用户是否登录
       $this->check();

        if (!isset($this->uid) || !trim($this->uid)) {
            $this->outPut(null, -1, null, '用户未登录，不能获取地址！');
        }
        $where = array(
            'user_id' => $this->uid
        );
        $address_list = M('address')->where($where)->order(array('id'=>'desc'))->select();
        
        // 整理数据
        if($address_list){
            foreach($address_list as &$v){
                $v['default_type'] =  ternary($v['default'], 0);
                if(isset($v['default'])){
                    unset($v['default']);
                }
                if(isset($v['user_id'])){
                     unset($v['user_id']);
                }
                $v['mobile_hide'] = substr($v['mobile'],0,4) . '****' . substr($v['mobile'], -4,4);
            }
        }
        
        $this->outPut($address_list, 0);
    }

    /**
     * 添加收货地址
     */
    public function addUserAddress() {
        $province = I('post.province', '', 'trim');
        $area = I('post.area', '', 'trim');
        $city = I('post.city', '', 'trim');
        $street = I('post.street', '', 'trim');
        $zipcode = I('post.zipcode', '', 'trim');
        $name = I('post.name', '', 'trim');
        $mobile = I('post.mobile', '', 'trim');
        $default_type = I('post.default_type', 'N', 'trim');
        
        // 检测用户是否登录
        $this->check();
        if (!isset($this->uid) || !trim($this->uid)) {
            $this->outPut(null, -1, null, '用户未登录，不能添加地址！');
        }

        if (!$province) {
            $this->outPut(null, -1, null, '请选择所在省！');
        }
        if (!$area) {
            $this->outPut(null, -1, null, '请选择所在市！');
        }
        if (!$city) {
            $this->outPut(null, -1, null, '请选择所在城市！');
        }
        if (!$street) {
            $this->outPut(null, -1, null, '请填写详细地址！');
        }
        if (!$zipcode) {
//            $this->outPut(null, -1, null, '请填写邮政编码！');
        }
        if (!$name) {
            $this->outPut(null, -1, null, '请填写收货人姓名！');
        }
        if (!$mobile) {
            $this->outPut(null, -1, null, '请填写联系电话！');
        }
        if (!checkMobile($mobile)) {
            $this->outPut(null, -1, null, '联系电话格式错误！');
        }
        
        // 地址添加不能超过5个
        $address_count =  M('address')->where(array('user_id'=>$this->uid))->count();
        if($address_count && $address_count>=5){
              $this->outPut(null, -1, null, '每人最多添加五个地址！');
        }

        $model = M();
        $model->startTrans();
        $data = array(
            'province' => $province,
            'area' => $area,
            'city' => $city,
            'street' => $street,
            'zipcode' => $zipcode,
            'name' => $name,
            'mobile' => $mobile,
            'user_id' => $this->uid,
            'default' => 'N',
            'create_time' => time(),
        );
        $address_id = M('address')->add($data);
        if (!$address_id) {
            $model->rollback();
            $this->outPut(null, -1, null, '地址添加失败！');
        }
        if ($default_type == 'Y') {
            $res = M('address')->where(array('user_id' => $this->uid))->save(array('default' => 'N'));
            if ($res === false) {
                $model->rollback();
                $this->outPut(null, -1, null, '设置为默认地址失败！');
            }
            $res = M('address')->where(array('id' => $address_id))->save(array('default' => 'Y'));
            if ($res === false) {
                $model->rollback();
                $this->outPut(null, -1, null, '设置为默认地址失败！');
            }
        }
        $model->commit();
        $this->outPut(array('address_id'=>$address_id), 0);
    }

    /**
     * 编辑收货地址
     */
    public function editUserAddress() {
        $address_id = I('post.address_id', '', 'trim');
        $province = I('post.province', '', 'trim');
        $area = I('post.area', '', 'trim');
        $city = I('post.city', '', 'trim');
        $street = I('post.street', '', 'trim');
        $zipcode = I('post.zipcode', '', 'trim');
        $name = I('post.name', '', 'trim');
        $mobile = I('post.mobile', '', 'trim');
        $default_type = I('post.default_type', 'N', 'trim');

        // 检测用户是否登录
        $this->check();

        if (!isset($this->uid) || !trim($this->uid)) {
            $this->outPut(null, -1, null, '用户未登录，不能编辑地址！');
        }

        if (!$address_id) {
            $this->outPut(null, -1, null, '修改的地址id不能为空！');
        }
        if (!$province) {
            $this->outPut(null, -1, null, '请选择所在省！');
        }
        if (!$area) {
            $this->outPut(null, -1, null, '请选择所在市！');
        }
        if (!$city) {
            $this->outPut(null, -1, null, '请选择所在城市！');
        }
        if (!$street) {
            $this->outPut(null, -1, null, '请填写详细地址！');
        }
        if (!$zipcode) {
         //  $this->outPut(null, -1, null, '请填写邮政编码！');
        }
        if (!$name) {
            $this->outPut(null, -1, null, '请填写收货人姓名！');
        }
        if (!$mobile) {
            $this->outPut(null, -1, null, '请填写联系电话！');
        }
        if (!checkMobile($mobile)) {
            $this->outPut(null, -1, null, '联系电话格式错误！');
        }

        $addres_res = M('address')->where(array('id' => $address_id))->field('user_id')->find();
        if (!$addres_res) {
            $this->outPut(null, -1, null, '需要修改的地址不存在！');
        }
        if (!isset($addres_res['user_id']) || intval($addres_res['user_id']) !== intval($this->uid)) {
            $this->outPut(null, -1, null, '修改的地址不是自己的地址！');
        }

        $model = M();
        $model->startTrans();

        $data = array(
            'province' => $province,
            'area' => $area,
            'city' => $city,
            'street' => $street,
            'zipcode' => $zipcode,
            'name' => $name,
            'mobile' => $mobile,
            'user_id' => $this->uid,
            'default' => 'N',
            'create_time' => time(),
        );
        $res = M('address')->where(array('id' => $address_id))->save($data);
        if ($res===false) {
            $model->rollback();
            $this->outPut(null, -1, null, '地址更新失败！');
        }
        if ($default_type == 'Y') {
            $res = M('address')->where(array('user_id' => $this->uid))->save(array('default' => 'N'));
            if ($res === false) {
                $model->rollback();
                $this->outPut(null, -1, null, '设置为默认地址失败！');
            }
            $res = M('address')->where(array('id' => $address_id))->save(array('default' => 'Y'));
            if ($res === false) {
                $model->rollback();
                $this->outPut(null, -1, null, '设置为默认地址失败！');
            }
        }
        $model->commit();
        $this->outPut(array(), 0);
    }

    /**
     * 删除收货地址
     */
    public function deleteUserAddress() {
        $address_id = I('post.address_id', '', 'trim');

        // 检测用户是否登录
        $this->check();

        if (!isset($this->uid) || !trim($this->uid)) {
            $this->outPut(null, -1, null, '用户未登录，不能删除地址！');
        }

        if (!$address_id) {
            $this->outPut(null, -1, null, '删除的地址id不能为空！');
        }

        $addres_res = M('address')->where(array('id' => $address_id))->field('user_id')->find();
        if (!$addres_res) {
            $this->outPut(null, -1, null, '需要删除的地址不存在！');
        }
        if (!isset($addres_res['user_id']) || intval($addres_res['user_id']) !== intval($this->uid)) {
            $this->outPut(null, -1, null, '删除的地址不是自己的地址！');
        }
        $res = M('address')->where(array('id' => $address_id))->delete();
        if (!$res) {
            $this->outPut(null, -1, null, '删除的地址失败！');
        }
        $this->outPut(array(), 0);
    }

}
