<?php

/**
 * Created by JetBrains PhpStorm.
 * User: runtoad
 * Date: 15-3-9
 * Time: 上午11:42
 * To change this template use File | Settings | File Templates.
 */

namespace Manage\Controller;

use Common\Controller\CommonBusinessController;

class CommonController extends CommonBusinessController {

    protected $user = null;
    protected $uid = null;
    protected $city = null;
    private $auth = null;
    private $auth_config = null;

    /**
     * 普通分页大小
     */
    protected $reqnum = 20;

    /**
     * 弹出层分页大小
     */
    protected $popup_reqnum = 10;

    public function __construct() {
        parent::__construct();
        header('Content-type: text/html; charset=utf-8');

        $this->auth = new \Common\Org\Auth(strtolower(MODULE_NAME));
        $this->auth_config = C('AUTH_CONFIG');
        // 登录权限认证
        $this->__auth();
        //获取订座状态
        $this->_ticket();
        // 权限点自动注册
        $this->_register($this);
    }

    /**
     * @return bool  用户权限验证
     */
    private function __auth() {
        $module_name = strtolower(MODULE_NAME);
        $controller_name = strtolower(CONTROLLER_NAME);
        $action_name = strtolower(ACTION_NAME);
        $uri = "$module_name/$controller_name/$action_name";
        // 无权限 无登录r认证的url
        if(isset($this->auth_config['NO_AUTH_NO_LOGIN_URI'][$uri])){
            return true;
        }
        $this->user = session(C('USER_AUTH_KEY'));
        if (is_null($this->user)) {
            //跳转到认证网关
           redirect(U(C('USER_AUTH_GATEWAY')));
        }
        $this->assign('user_info', $this->user);
        $this->city = session(C('CITY_AUTH_KEY'));
        if (is_null($this->city)) {
            //跳转到认证网关
          redirect(U(C('USER_AUTH_GATEWAY')));
        }
        $this->assign('city_info', $this->city);
        
        if(isset($this->user['fagent_id']) && $this->user['fagent_id']=='0'){
            return true;
        }
        //var_dump($this->user);
        // 权限判断
        $res = $this->auth->auth_check_access($this->user['id'], $this->auth_config);
        //exit;
        if (!$res) {
            if (IS_AJAX){
                $this->ajaxReturn(array('error' => '无权限该操作', 'info' => '无权限该操作', 'code' => -1, 'status' => -1));
            }else{
                echo '无权限该操作';//$this->ajaxReturn(array('error' => '无权限该操作', 'info' =>  $uri, 'code' => -1, 'status' => -1));
                exit;
            }
           redirect(U('Index/index'));
        }
    }


    protected function _register($class_name) {
        if (isset($this->auth_config['OPEN_AUTH_RULE_REGISTER']) && $this->auth_config['OPEN_AUTH_RULE_REGISTER']) {
            $this->auth->register($class_name);
        }
    }

    /**
     * 首页
     */
    public function index() {
        $this->getList();
        $this->display();
    }

    /**
     * 添加数据
     */
    public function add() {
        $this->display();
    }

    /**
     * 插入数据
     */
    public function insert() {

        $model = D(CONTROLLER_NAME);
        if ($model->create()) {
            if ($model->add()) {
                $this->success('添加成功', U(CONTROLLER_NAME . '/index'));
                exit();
            } else {
                $this->errmsg = $model->getDbError();
            }
        } else {
            $this->errmsg = $model->getError();
        }
        $this->error($this->errmsg);
    }

    /**
     * 编辑数据
     */
    public function edit() {
        $id = intval(I('get.id'));
        if (!empty($id)) {
            $model = D(CONTROLLER_NAME);
            $pk = $model->getPk();
            $list = $model->where($pk . "=" . $id)->find();
            if ($list) {
                $this->assign('vo', $list); // 赋值分页输出
                $this->display();
                exit;
            } else {
                $this->assign('msg', '信息不存在或已被删除！');
            }
        } else {
            $this->assign('msg', '信息不存在或已被删除！');
        }
        $this->display();
    }

    /**
     * 修改数据
     */
    public function update() {
        $password = I('post.password', '', 'trim');
        if($password){
            if(!checkPwd($password)){
                $this->error('密码必须是6~20位数字和字母组成');
                die();
            }
            $_POST['password'] = encryptPwd(trim($password)); 
        }else{
            unset($_POST['password']);
        }
        
        $model = D(CONTROLLER_NAME);
        $pk = $model->getPk();
        if ($_POST[$pk]) {
            if ($model->create()) {
                $result = $model->where("$pk='" . I('post.' . $pk) . "'")->save();
                if ($result !== false) {
                    $this->success('修改数据成功！', U(CONTROLLER_NAME . '/index'));
                    exit;
                } else {
                    $this->errmsg = $model->getDbError();
                }
            } else {
                $this->errmsg = $model->getError();
            }
        } else {
            $this->errmsg = '修改数据不存在！';
        }
        $this->error($this->errmsg);
    }

    /**
     * 删除数据
     */
    public function delete() {
        $model = D(CONTROLLER_NAME);
        $id = I('param.id');
        $id = explode(',', $id);
        if (!is_array($id)) {
            $id = array($id);
        }
        $pk = $model->getPk();
        if (!empty($id)) {
            $condition = array($pk => array('in', $id));
            if ($model->where($condition)->delete()) {
                ajaxReturnNew('', '删除成功', 1);
            } else {
                ajaxReturnNew('', '删除失败', 0);
            }
        } else {
            ajaxReturnNew('', '删除失败', 0);
        }
    }

    /**
     * 判断时间
     */
    public function checktime() {
        $sy = !empty($_GET['sy']) ? htmlspecialchars($_GET['sy']) : '';
        $ey = !empty($_GET['ey']) ? htmlspecialchars($_GET['ey']) : '';
        $sm = !empty($_GET['sm']) ? htmlspecialchars($_GET['sm']) : '';
        $em = !empty($_GET['em']) ? htmlspecialchars($_GET['em']) : '';
        if (empty($sy) || empty($sm)) {
            $sm = date('m', time());
            $sy = date('Y', time()) - 1;
            if ($sm == 12) {
                $sy = $sy + 1;
                $sm = 1;
            } else {
                $sm = $sm + 1;
            }
        }
        if (empty($ey) || empty($em)) {
            $ey = date('Y', time());
            $em = date('m', time());
        }

        if ($em == 12) {
            $ey = $ey + 1;
            $em = 1;
        } else {
            $em = $em + 1;
        }

        if (strtotime($sy . '-' . $sm) >= strtotime($ey . '-' . $em)) {
            $this->error('开始时间不能大于结束时间！');
        }
        $timeInfo = array(
            'sy' => $sy,
            'ey' => !empty($_GET['ey']) ? intval($_GET['ey']) : date('Y', time()),
            'sm' => $sm,
            'em' => !empty($_GET['em']) ? intval($_GET['em']) : date('m', time())
        );
        $eyTo = !empty($_GET['ey']) ? intval($_GET['ey']) : date('Y', time());
        $emTo = !empty($_GET['em']) ? intval($_GET['em']) : date('m', time());
        $time = $this->timeOut(strtotime($sy . '-' . $sm), strtotime($eyTo . '-' . $emTo));
        if ($time > 12) {
            $this->error('开始时间与结束时间相差不能超过12个月！');
        }

        return array($sy, $sm, $ey, $em, $timeInfo, $time);
    }

    /**
     * 计算两个时间戳之间的月份
     */
    public function timeOut($start, $end) {
        $m1 = date("n", $start);
        $m2 = date("n", $end);
        $y1 = date("Y", $start);
        $y2 = date("Y", $end);
        $a = ($y2 - $y1) * 12 + ($m2 - $m1) + 1;

        return $a;
    }

    /**
     * 验证是否为空
     * @param      $array
     * @param bool $isAjax
     *
     * @return bool|string
     */
    protected function _checkblank($array, $isAjax = false) {
        $errMsg = '';
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (I('param.' . $value) === "") {
                    $errMsg .= $value . ',';
                }
            }
        } else {
            if (I('param.' . $array) === "") {
                $errMsg .= $array . ',';
            }
        }

        $errMsg = $errMsg == "" ? '' : substr($errMsg, 0, strlen($errMsg) - 1);

        if (!empty($errMsg)) {
            if (!$isAjax) {
                $this->error($errMsg);
                exit;
            } else {
                return $errMsg;
            }
        } else {
            return true;
        }
    }

    /**
     * 验证用户是否登录
     * @return error
     */
    protected function check() {
        if (!session(C('USER_AUTH_KEY'))) {
            cookie('http_referer', $_SERVER['HTTP_REFERER']);
            $this->redirect('User/login');
        }
        $this->uid = session(C('USER_AUTH_KEY'));
    }

    /**
     * 获取搜索时间
     */
    protected function _getSearchTime($limit = 400) {
        $data['stime'] = I('get.stime', date('Y-m-d'));
        $data['etime'] = I('get.etime', date('Y-m-d'));
        $data['st'] = strtotime($data['stime'] . ' 00:00:00');
        $data['et'] = strtotime($data['etime'] . ' 23:59:59');
        if ($data['et'] < $data['st']) {
            $this->error('结束时间必须大于开始时间');
        }
        if ($limit > 0) {
            if ($data['et'] - $data['st'] > $limit * 24 * 3600) {
                $this->error('时间相差不能大于'.$limit.'天');
            }
        }
        return $data;
    }

    /**
     * 获取城市id
     */
    protected function _getCityId() {
         return ternary($this->user['city_id'], '');   //TODO 获取城市id
    }

    /**
     * 获取登陆用户id
     */
    protected function _getUserId() {
        return ternary($this->user['id'], '');   //TODO 获取城市id
    }

    /**
     * 获取代理快捷编辑团单权限
     */
    protected function _getNewbie(){
        $user=session('userManage');
        return $user['newbie'];
    }

    /**
     * 获取app订座状态
     */
    protected function _ticket(){
        $city_id = $this->_getCityId();
        if($city_id){
            $where = array('city_id'=>$city_id,'model_name'=>'dingzuo');
            $info = M('city_model_show')->where($where)->find();
            if(!$info || $info['is_show'] == 'N'){
                $is_show = 'N';
            }else{
                $is_show = 'Y';
            }
            $this->assign('ticket_show',$is_show);
        }
    }
}

?>
