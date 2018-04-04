<?php

/**
 * Created by JetBrains PhpStorm.
 * User: runtoad
 * Date: 15-3-6
 * Time: 下午3:01
 *
 */

namespace Common\Controller;

use Common\Controller\CommonBaseController;

class CommonBusinessController extends CommonBaseController {

    public function __construct() {
        parent:: __construct();
    }

    /**
     * 获取列表（包含search功能）
     */
    protected function _getList($model, $paramArray, $field = true) {
        $_GET['p'] = intval($_GET['p']);
        $where     = $this->createSearchWhere($paramArray);
        $id        = $model->getPk();
        $count     = $model->where($where)->count();
        $page      = $this->pages($count, $this->reqnum, $where);
        //TODO 后期添加缓存读取数据
        $list = $model->field($field)->where($where)->order("$id desc")->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->_writeDBErrorLog($list, $model);
        $this->assign('count', $count);
        $this->assign('pages', $page->show());
        return $list;
    }

    /**
     * 获取分类列表
     * @param $zone
     * @param array $where
     * @return mixed
     */
    protected function _getCategoryList($zone, $where = array(), $is_loop = true) {
        $category = S('category');
        $key      = $zone;
        $data     = array();
        if (isset($category[$key])) {
            if (!empty($where)) {
                $val = array_shift($where);
                if (in_array($zone, array('city', 'group'))) {
                    $key  = 'sub_' . $zone;
                    $data = ternary($category[$zone][$key][$val], array());
                } else {
                    $data = ternary($category[$key][$val], array());
                }
            } else {
                if (in_array($zone, array('city', 'group'))) {
                    $data = ternary($category[$zone][$zone], '');
                } else {
                    $data = ternary($category[$zone], '');
                }
            }
        }

        if (!isset($category[$zone]) && $is_loop) {
            switch ($zone) {
                case 'city':
                    $data = $this->_getCityList();
                    break;
                case 'district':
                    $data = $this->_getDistrictList();
                    break;
                case 'group':
                    $data = $this->_getGroupList();
                    break;
                case 'station':
                    $data = $this->_getStationList();
                    break;
                case 'class':
                case 'partner':
                case 'feedback':
                case 'express':
                    $data = $this->_getFirstCateList($zone);
                    break;
                case 'province':
                    $data = $this->_getProvinceList();
                    break;
            }

            $category[$zone] = $data;
            S('category', $category);
            $data = $this->_getCategoryList($zone, $where, false);
        }
        if (method_exists($this, 'assign')) {
            $this->assign($zone, $data);
        }
        return $data;
    }

    protected function _getFirstCateList($type) {
        $map = array(
            'zone' => $type,
            'display' => 'Y'
        );
        $list = M('Category')->where($map)->order('`sort_order` DESC')->select();
        $newList = array();
        foreach ($list as $row) {
            $newList[$row['id']] = $row;
        }
        return $newList;
    }

    protected function _getCityList() {
        $map = array(
            'zone' => 'city',
            'display' => 'Y',
        );

        $list = M('Category')->where($map)->order('`letter` ASC,`sort_order` DESC')->select();
        $data = array();
        foreach ($list as $row) {
            $data['sub_city'][$row['czone']][$row['id']] = $row;
            $data['city'][$row['id']] = $row;
        }

        return $data;
    }

    protected function _getDistrictList() {
        $map = array(
            'zone' => 'district',
            'display' => 'Y',
            'fid' => array('NEQ', 0)
        );
        $list = M('Category')->where($map)->order('`letter` ASC,`sort_order` DESC')->select();
        $data = array();
        foreach ($list as $row) {
            $data[$row['fid']][$row['id']] = $row;
        }
        return $data;
    }

    protected function _getStationList() {
        $map = array(
            'zone' => 'station',
            'display' => 'Y',
            'fid' => array('NEQ', 0)
        );

        $list = M('Category')->where($map)->order('`sort_order` DESC')->select();
        $data = array();
        foreach ($list as $row) {
            $data[$row['fid']][$row['id']] = $row;
        }
        return $data;
    }

    protected function _getGroupList() {
        $map = array(
            'zone' => 'group',
            'display' => 'Y'
        );

        $list = M('Category')->where($map)->order('`sort_order` DESC')->select();
        $data = array();
        foreach ($list as $row) {
            $data['sub_group'][$row['fid']][$row['id']] = $row;
            $data['group'][$row['id']] = $row;
        }
        return $data;
    }

    /**
     * ajax获取省
     */
    public function getProvince() {
        if (IS_AJAX) {
            $category = S('category');
            $list = $this->_getCategoryList('province');
            if ($this->uid) {
                $city_id = M('user')->where('id=' . $this->uid)->getField('city_id');
                $citys = $this->_getCategoryList('city');
                $zone = ternary($citys[$city_id]['czone'], '');
            }
            $data['html'] = '<option value="">请选择</option>';
            foreach ($list as $key => $row) {
                $data['html'] .= '<option value="' . $key . '" ' . ($row == $zone ? 'selected' : '') . '>' . $row . '</option>';
            }
            $this->ajaxReturn($data);
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 获取省列表
     * @return array
     */
    protected function _getProvinceList() {
        $where = array(
            'zone' => 'city',
            'czone' => array('NEQ', '')
        );
        $city = D('Category')->getList($where, '`sort_order` DESC', '', 'id,czone');
        $newData = array();
        foreach ($city as $row) {
            if ($row['czone'])
                $newData[$row['id']] = $row['czone'];
        }
        return array_unique($newData);
    }

    /**
     * ajax获取市
     */
    public function getCities() {
        $id = I('param.id', 0, 'intval');
        $city = $this->_getCategoryList('city');
        $zone = ternary($city[$id]['czone'], '');
        $str = '<option value="0">请选择</option>';
        if ($zone) {
            if ($this->uid) {
                $city_id = M('user')->where('id=' . $this->uid)->getField('city_id');
            }
            $zone = $this->_getCategoryList('city', array('czone' => $zone));
            foreach ($zone as $row) {
                if ($row['id'] == $city_id) {
                    $str .= '<option value="' . $row['id'] . '" selected>' . $row['name'] . '</option>';
                } else {
                    $str .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                }
            }
        }
        $data['html'] = $str;
        $this->ajaxReturn($data);
    }

    /**
     * ajax获取二级商圈
     */
    public function getStationList() {
        if (IS_AJAX) {
            if ($this->_checkblank('zone_id') !== true) {
                $this->error('请选择商圈！');
            }
            $zone_id = I('param.zone_id', 0, 'intval');
            $stationList = $this->_getCategoryList('station', array('fid' => $zone_id));
            $stationList = $stationList[$zone_id];
            $str = '<option>选择二级商圈</option>';
            foreach ($stationList as $row) {
                $str .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
            }
            $data['info'] = $str;
            $data['status'] = 1;
            $this->ajaxReturn($data);
        } else {
            $this->error('错误访问！');
        }
    }

    protected function getSendSms($mobile, $content, $source = 'api', $action = null) {
        $userRes = M('fanli_wxuser')->where(array('mobile' => trim($mobile)))->getField('id');
        $code = getMobileCode();
        $tmpCode = $code;
        if ($action) {
            $msgType = C('MSG_TYPE');
            switch ($action) {
                case 'reg':
                case 'bindmobile':
                case 'changemobile':
                case 'pcbindmobile':
                case 'pcchangemobile':
                case 'pcreg':
                case 'wapreg':
                    if ($userRes) {
                        return array('status' => -1, 'error' => $mobile);
                    }
                    $content = str_replace('MSMCode', $code, $msgType['other']);
                    break;
                case 'npwd':
                case 'pcnpwd':
                case 'wapnpwd':
                    if (!$userRes) {
                        return array('status' => -1, 'error' => '该用户不存在');
                    }
                    $content = str_replace('MSMCode', $code, $msgType['npwd']);
                    break;
                    
                    // 领奖发送验证码
                case 'receive_prize';
                    $content = str_replace('MSMCode', $code, $msgType['other']);
                    break;
            }
            $smsRes = D('Sms')->sendSms($mobile, $action, $code);
            if ($smsRes['status'] == -1) {
                return array('status' => -1, 'error' => $smsRes['error']);
            }
            if($code != $tmpCode){
                $content = str_replace($tmpCode,$code,$content);
            }
        }
        $type=C('SMSTYPE');
        return $this->_sms($mobile, $content, $type, $source);
    }

    protected function _sendSms($mobile, $content, $source = 'api', $action = null) {
        if (!checkMobile($mobile)) {
            $this->_writeLog($mobile . '--手机号码格式有误', 'EMERG', $source);
            return array('status' => -1, 'error' => $mobile);
        }
        $userRes = D('User')->isRegister(array('mobile' => trim($mobile)));
        $code = getMobileCode();
        $tmpCode = $code;
        if ($action) {
            $msgType = C('MSG_TYPE');
            switch ($action) {
                case 'reg':
                case 'bindmobile':
                case 'changemobile':
                case 'pcbindmobile':
                case 'pcchangemobile':
                case 'pcreg':
                case 'wapreg':
                    if ($userRes) {
                        return array('status' => -1, 'error' => '手机号码已注册');
                    }
                    $content = str_replace('MSMCode', $code, $msgType['other']);
                    break;
                case 'npwd':
                case 'pcnpwd':
                case 'wapnpwd':
                    if (!$userRes) {
                        return array('status' => -1, 'error' => '该用户不存在');
                    }
                    $content = str_replace('MSMCode', $code, $msgType['npwd']);
                    break;
                    
                    // 领奖发送验证码
                case 'receive_prize';
                    $content = str_replace('MSMCode', $code, $msgType['other']);
                    break;
            }
            $smsRes = D('Sms')->sendSms($mobile, $action, $code);
            if ($smsRes['status'] == -1) {
                return array('status' => -1, 'error' => $smsRes['error']);
            }
            if($code != $tmpCode){
                $content = str_replace($tmpCode,$code,$content);
            }
        }
        $type=C('SMSTYPE');
        return $this->_sms($mobile, $content, $type, $source);
    }

    /**
     * @param $mobile 为接收方手机号码，如有多个以英文逗号隔开
     * @param $msg    短信内容，最多1000个字
     * @return array (status=0 短信发送成功 status=0 发送失败 error为错误信息)
     */
    protected function _sms($mobile, $msg, $type = 'Jcsms', $source = 'api') {
        if($type != 'voice' && $type!='MonSend'){
            $type = 'Jcsms';
        }
        if (!checkMobile($mobile)) {
            $this->_writeLog($mobile . '--手机号码格式有误', 'EMERG', $source);
            return array('status' => -1, 'error' => '手机号码格式有误');
        }
        if (!trim($msg)) {
            $this->_writeLog($mobile . '--短信内容不能为空', 'EMERG', $source);
            return array('status' => -1, 'error' => '短信内容不能为空');
        }
        if ($type == 'voice') {
            $sendSms = new \Common\Org\sendVoices();
            $res = $sendSms->sendVoice($mobile, $msg);
        } else {
            $sendSms = new \Common\Org\sendSms();
            $res = $sendSms->sendMsg($mobile, $msg, $type);
        }
        if ($res['status'] == 1) {
            return array('status' => 0);
        } else {
            $this->_writeLog($mobile . '--短信发送失败', 'EMERG', $source);
            return array('status' => -1, 'error' => $res['data']);
        }
    }

    /**
     * 乐观锁机制更新商品销售数量
     * @param $redis  redis连接
     * @param $buyCountKey 商品销售数key
     * @param $max_number   最大数量
     * @param $num          购买数量
     *
     * @return bool
     */
    protected function _IncRedis(&$redis, $buyCountKey, $max_number, $num) {
        $redis->watch($buyCountKey);
        $buyCount = $redis->get($buyCountKey);
        $max_number = intval($max_number);
        if ($max_number && $buyCount && intval($buyCount) >= $max_number) {
            $redis->unwatch($buyCountKey);

            return false;
        } elseif ($max_number && $buyCount + $num > $max_number) {
            $redis->unwatch($buyCountKey);
            return 'poor';
        } else {
            $redis->multi();
            $redis->set($buyCountKey, $buyCount + $num);
            $setResult = $redis->exec();
            return $setResult;
        }
    }

    /**
     * 通过微信公众平台发送coupon
     * @param $data  array('user_id'=>'用户id','order_id' => '订单id' ,'price'=>'团单单价','end_time'=>'过期时间','title'=>'图单标题','coupon'=>'券号，多个券号用‘,’隔开')
     * @param string $act
     */
    protected function _WeiXinSendCoupon($data, $act = 'buy') {
        $opend_id = M('weixin_user')->where('username="' . $data['user_id'].'"')->getField('openid');
        if ($opend_id) {
            $token_where = array('expire_time' => array('gt', time()), 'id' => 1);
            $access_token = M('token')->where($token_where)->getField('access_token');
            $WeiXin = new \Common\Org\WeiXin();
            if (!$access_token) {
                $access_token = $WeiXin->getAccessToken();
                if ($access_token !== false) {
                    $save_token = array('access_token' => $access_token, 'expire_time' => time() + 7200, 'id' => 1);
                    M('token')->save($save_token);
                }
            }
            if ($access_token) {
                $data['token_id'] = $access_token;
                $data['wxuser'] = $opend_id;
                if ($act == 'buy') {
                    $WeiXin->sendBuyCoupon($data);
                } else {
                    $WeiXin->sendVerifyCoupon($data);
                }
            } else {
                $this->_writeLog('access_token', 'ALERT', 'api');
            }
        }
    }

    /**
     * 通过订单id换取二维码
     * @param $order_id
     * @return string
     */
    protected function _getQrImageUrl($order_id) {
        $token_where = array('expire_time' => array('gt', time()), 'id' => 1);
        $access_token = M('token')->where($token_where)->getField('access_token');
        $WeiXin = new \Common\Org\WeiXin();
        if (!$access_token) {
            $access_token = $WeiXin->getAccessToken();
            if ($access_token !== false) {
                $save_token = array('access_token' => $access_token, 'expire_time' => time() + 7200, 'id' => 1);
                M('token')->save($save_token);
            }
        }
        if ($access_token) {
            return $WeiXin->getQRImageUrl($order_id, $access_token);
        } else {
            $this->_writeLog('access_token', 'ALERT', 'api');
        }
    }

    /**
     * 记录点击数
     * @param type $teamId
     */
    protected function _recordViewCount($teamId = '', $plat = 'pc', $uuid = '') {

        if (!trim($teamId)) {
            return false;
        }
        $nowTime = time();
        $dateKey = 'view_count_date';
        $dtime = S($dateKey);
        if (!$dtime) {
            $dtime = strtotime(date('Y-m-d'));
        }
        $team = M('team');
        $time = $nowTime - $dtime;
        if ($time > 86400) {
            $dviews = array('view_count_day' => '0');
            $where['view_count_day'] = array('neq', 0);
            $team->where($where)->save($dviews);
            S($dateKey, strtotime(date("Y-m-d")));
        }

        // PC端记录浏览数
        if ($plat == 'pc') {
            if (cookie('countTeamID') != $teamId) {
                $team->where('id=' . $teamId)->setInc('view_count');
                $team->where('id=' . $teamId)->setInc('view_count_day');
                cookie("countTeamID", $teamId, time() + 300);
            }
            return true;
        }

        // 手机端记录浏览数
        $appViewCountKey = 'app_view_count_' . md5($uuid);
        $appViewCount = S($appViewCountKey);
        if (!$appViewCount || $appViewCount != $teamId) {
            $team->where('id=' . $teamId)->setInc('view_count');
            $team->where('id=' . $teamId)->setInc('view_count_day');
            S($appViewCountKey, $teamId, 300);
        }
        return true;
    }

    /**
     * 添加操作日志
     */
    protected function addOperationLogs($content = '', $admin_id = '', $admin_username = '') {
        if (!trim($content)) {
            return false;
        }
        if (!trim($admin_id)) {
            $admin_id = ternary($this->user['id'], '');
        }
        if (!trim($admin_username)) {
            $admin_username = ternary($this->user['username'], '');
        }
        $data = array(
            'admin_id' => $admin_id,
            'admin_username' => $admin_username,
            'content' => $content,
            'controller_name' => strtolower(CONTROLLER_NAME),
            'action_name' => strtolower(ACTION_NAME),
            'model_name' => strtolower(MODULE_NAME),
            'create_time' => time(),
        );
        return M('admin_operation_logs')->add($data);
    }

    /**
     * 获取当前查询的where条件
     * @param $city_id
     * @return mixed
     */
    protected function _getTeamWhere($city_id = null, $prefix = null, $today = false) {
        if ($prefix) {
            if ($city_id)
                $condition[$prefix . 'city_id'] = array('in',array($city_id,'957'));
            $condition[$prefix . '.team_type'] = array('in',array('goods','normal'));
            if ($today === false) {
                $condition[$prefix . '.end_time'] = array('gt', time());
                $condition[$prefix . '.begin_time'] = array('elt', time());
            } else {
                $start_time = strtotime(date('Y-m-d', strtotime("-7 days")));
                $condition[$prefix . '.begin_time'] = array(array('EGT', $start_time), array('LT', time()));
                $condition[$prefix . '.end_time'] = array('gt', time());
            }
            $condition[$prefix . '.team_price'] = array('egt', 0);
        } else {
            if ($city_id)
                $condition['city_id'] = array('in',array($city_id,'957'));
            $condition['team_type'] = array('in',array('goods','normal'));
            if ($today === false) {
                $condition['end_time'] = array('gt', time());
                $condition['begin_time'] = array('elt', time());
            } else {
                $start_time = strtotime(date('Y-m-d', strtotime("-7 days")));
                $condition['begin_time'] = array(array('EGT', $start_time), array('LT', time()));
                $condition['end_time'] = array('gt', time());
            }
            $condition['team_price'] = array('egt', 0);
        }
        return $condition;
    }
    
    /**
     * 获取对应模块的权限组
     * @param type $module_name
     * @param type $uid
     * @return type
     */
    protected function getAuthGroupList($module_name = 'admin', $uid = '') {

        $module_name = strtolower($module_name);
        if (!$module_name) {
            $module_name = 'admin';
        }
        $selectAuthGroup = array();
        if (trim($uid)) {
            $auth_group_access = M('auth_group_access')->where(array('uid' => $uid, 'module_name' => $module_name))->field('group_id')->select();
            if ($auth_group_access) {
                foreach ($auth_group_access as $v) {
                    $selectAuthGroup[] = ternary($v['group_id'], '');
                }
            }
        }
        $auth_group = M('auth_group')->where(array('module_name' => $module_name))->select();
        if ($auth_group) {
            foreach ($auth_group as &$_v) {
                $_v['checked'] = '0';
                if (isset($_v['id']) && in_array($_v['id'], $selectAuthGroup)) {
                    $_v['checked'] = '1';
                }
            }
        }
        return $auth_group;
    }
    
    /**
     * 获取 跳转到 公共操作 的 地址
     */
    protected function get_common_operation_url($uri='',$operation_id=0){
        
        if(!$uri){
            return false;
        }
        
        if(!$operation_id){
            return false;
        }
        
        // 获取access_token
        // $source_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $source_url = $_SERVER['HTTP_REFERER'];
        $data = array(
            'plat'=>strtolower(MODULE_NAME),
            'operation_id'=>$operation_id,
            'source_url'=>$source_url,
        );
        $data_str = @json_encode($data);
        $tokenKey = C('tokenKey');
        $rand = \Org\Util\String::randString(6);
        $token = bin2hex(\Think\Crypt\Driver\Xxtea::encrypt($data_str . '|' . $rand, $tokenKey));
        
        $host = 'http://coperation.'.trim(APP_DOMAIN,'.');
        $url = "{$host}/{$uri}?access_token={$token}";
        return $url;
    }
    
}
