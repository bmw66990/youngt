<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/6/10
 * Time: 16:38
 */

namespace Admin\Controller;

/**
 * 用户控制器
 * Class UserController
 * @package Admin\Controller
 */
class UserController extends CommonController {

    private $auth_rule_type = array(
        '0' => '禁用',
        '1' => '正常'
    );
    private $auth_rule_name = array(
        'admin/index' => '首页权限',
        'admin/team' => '团单权限',
        'admin/order' => '订单权限',
        'admin/coupon' => '青团券权限',
        'admin/user' => '用户权限',
        'admin/partner' => '商户权限',
        'admin/customerservice' => '客服权限',
        'admin/manage' => '管理权限',
        'admin/financial' => '财务权限',
        'admin/dingzuo' => '订座权限',
        'admin/encyclopedias' => '百科权限',
        'admin/market' => '营销权限',
        'admin/pointsteam' => '积分权限',
        'admin/feedback' => '反馈权限',
        'admin/canvassbusiness' => '招商权限',
        'admin/workbench' => '工作台权限',
        'admin/Circle' => '邻里圈权限',
        
        // 代理后台 权限
        'manage/index' => '首页权限',
        'manage/finance' => '财务权限',
        'manage/chart' => '统计权限',
        'manage/team' => '团单权限',
        'manage/order' => '订单权限',
        'manage/user' => '用户权限',
        'manage/bduser' => '业务权限',
        'manage/partner' => '商家权限',
        'manage/credit' => '积分权限',
        'manage/comment' => '点评权限',
        'manage/news' => '新闻权限',
        'manage/admanage' => '广告权限',
        
        // 商家后台 权限
        'merchant/index' => '首页相关权限',
        'merchant/coupon' => '券号相关权限',
        'merchant/partner' => '商户资料权限',
        'merchant/teamcoupon' => '团单青团券权限',
        'merchant/review' => '用户评分权限',
        'merchant/coupay' => '消费明细权限',
        'merchant/final' => '申请结算权限',
        'merchant/dingzuo' => '订座订单权限',
        'merchant/teammanage' => '项目管理权限',
        'merchant/user' => '账户管理权限',
        'merchant/db' => '业务员权限',
        'merchant/branch' => '分店权限',
        'merchant/order' => '邮购订单管理权限',
    );
    private $auth_moudel_name = array(
        'admin' => '管理后台',
        'manage' => '代理后台',
        'merchant' => '商家后台',
    );

    /**
     * 用户列表
     */
    public function index() {
        $this->_getData('all');
        $this->display();
    }

    /**
     * 管理员列表
     */
    public function manage() {
        $this->_getData('manage');
        $this->display();
    }

    /**
     * 代理列表
     */
    public function agent() {
        $this->_getData('agent');
        $this->display();
    }

    protected function _getData($type) {
        $Model = D('User');
        $paramArray = array(
            array('id', '', ''),
            array('username', '', 'like'),
            array('mobile', '', ''),
            array('email', '', ''),
            array('city_id', '0', '')
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        if ($type == 'manage') {
            $where['manager'] = 'Y';
        } elseif ($type == 'agent') {
            $where['manager'] = 'P';
        }
        $count = $Model->getTotal($where);

        //记录错误日志
        $this->_writeDBErrorLog($count, $Model);

        $page = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $field = 'id,email,username,mobile,create_time,ip,money,city_id,realname,newbie,daily_time,fagent_id';
        $data = $Model->getList($where, 'id DESC', $limit, $field);
        //记录错误日志
        $this->_writeDBErrorLog($data, $Model);
        $city = $this->_getCategoryList('city');
        if ($data) {
            foreach ($data as &$val) {
                $val['city_name'] = ternary($city[$val['city_id']]['name'], '全国');
            }
        }
        $this->_writeDBErrorLog($data, $Model);
        $this->assign('allCity', $city);
        $this->assign('displayWhere', $displayWhere);
        $this->assign('data', $data);
    }

    /**
     * 	获取用户详情
     */
    public function getUserInfo() {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $Model = D('User');
            $field = 'email,username,mobile,create_time,money,score,realname,id,beizhu';
            $where = array('id' => $id);
            $data = $Model->getDetail($where, $field);
            $this->_writeDBErrorLog($data, $Model);
            $order = D('order')->field('count(id) as num,sum(origin) as origin')->where(array('state' => 'pay', 'user_id' => $id))->select();
            $data['num'] = ternary($order[0]['num'], 0);
            $data['origin'] = ternary($order[0]['origin'], 0);
            $this->assign('data', $data);
        } else {
            $this->assign('error', '获取用户详情失败');
        }
        $this->display();
    }

    /**
     *  获取用户交易明细
     */
    public function getUserTransaction() {
        $user_id = I('get.id', 0, 'intval');
        if ($user_id) {
            $Model = D('Order');
            $where['state'] = 'pay';
            $where['user_id'] = $user_id;
            $field = 'team_id,pay_id,quantity,pay_time,money,credit';
            $count = $Model->getTotal($where);
            $this->_writeDBErrorLog($count, $Model);
            $page = $this->pages($count, $this->popup_reqnum, '', 5);
            $limit = $page->firstRow . ',' . $page->listRows;
            $this->assign('pages', $page->show());
            $data = $Model->getUserTransaction($where, $limit, 'pay_time DESC', $field);
            $this->_writeDBErrorLog($data, $Model);
            $this->assign('data', $data);
        } else {
            $this->assign('error', '获取用户交易明细失败');
        }
        $this->display();
    }

    /**
     *  获取用户交易流水
     */
    public function getUserFlow() {
        $user_id = I('get.user_id', 0, 'intval');
        $down = I('get.down','','trim');
        if ($user_id) {
            $Model = D('Flow');
            $where['user_id'] = $user_id;
            $field = 'create_time,direction,money,action,money,team_id,detail_id,marks';
            if($down){
                $limit = '';
            }else{
                $count = $Model->getTotal($where);
                $this->_writeDBErrorLog($count, $Model);
                $page = $this->pages($count, $this->popup_reqnum, '', 5);
                $limit = $page->firstRow . ',' . $page->listRows;
                $this->assign('pages', $page->show());
            }
            $data = $Model->getUserFlow($where, $limit, 'create_time DESC', $field);
            if($down){
                $down_data = array();
                foreach($data as $key=>$val){
                    $down_data[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
                    if($val['action'] == 'buy' || $val['action'] == 'refund'){
                        $down_data[$key]['detail'] = flow_info($val['action']).'-'.$val['product'];
                    }else if($val['action'] == 'paycharge'){
                        $down_data[$key]['detail'] = flow_info($val['action']).'-'.$val['detail_id'];
                    }else{
                        $down_data[$key]['detail'] = flow_info($val['action']);
                    }
                    $down_data[$key]['action'] = flow_direction($val['direction']);
                    $down_data[$key]['money'] = $val['money'];
                }
                $head = array(
                    'create_time' => '时间',
                    'detail' => '详情',
                    'action' => '收支',
                    'money' => '金额',
                );
                $file_name = '用户流水下载表' . date('YmdHis');
                download_xls($down_data, $head, $file_name);
            }
            $this->assign('user_id',$user_id);
            $this->_writeDBErrorLog($data, $Model);
            $this->assign('data', $data);
        } else {
            $this->assign('error', '获取用户交易流水失败！');
        }
        $this->display();
    }

    /**
     *  删除用户
     */
    public function delUser() {
        $user_id = I('get.id', 0, 'intval');
        if ($user_id) {
            $state = M('order')->where(array('user_id' => $user_id, 'state' => 'pay'))->count('id');
            if ($state === false) {
                //TODO 错误日志
                $this->_writeDBErrorLog($state, M('order'));
            }
            if ($state) {
                $param = array('error' => base64_encode('该用户已经产生付款订单不能删除'));
            } else {
                $rs = D('User')->delete($user_id);
                if ($rs === false) {
                    //TODO 错误日志
                    $this->_writeDBErrorLog($rs, D('user'));
                    $param = array('error' => base64_encode('删除失败'));
                } else {
                    $this->addOperationLogs("操作：删除用户,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},用户id:{$user_id}");
                    $param = array('success' => base64_encode('删除成功'));
                }
            }
        } else {
            $param = array('error' => base64_encode('请求参数非法'));
        }
        $this->redirect_message(U("User/index"), $param);
    }

    /**
     * 发送短信模板
     */
    public function smsUser() {
        $mobile = I('get.mobile', '', 'strval');
        if (checkMobile($mobile)) {
            $this->assign('mobile', $mobile);
        } else {
            $this->assign('error', '手机号码不正确');
        }
        $this->display();
    }

    /**
     * 发送短信
     */
    public function smsSend() {
        $mobile = I('post.mobile', '', 'strval');
        $content = I('post.content', '', 'strval');
        if (checkMobile($mobile)) {
            if ($content) {
                $res = $this->_sms($mobile, $content, 'Cytsms', 'admin');
                if ($res['status'] == -1) {
                    $data = getPromptMessage('请输入短信内容后再发送');
                } else {
                    $data = getPromptMessage('发送成功', 'success', 1);
                }
            } else {
                $data = getPromptMessage('请输入短信内容后再发送');
            }
        } else {
            $data = getPromptMessage('手机号码格式不正确');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 开通代理快捷编辑团单权限
     */
    public function openTeam() {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $newbie = M('user')->getFieldById($id, 'newbie');
            if ($newbie == 'N') {
                $save_data = array('id' => $id, 'newbie' => 'Y', 'daily_time' => time());
                $res = M('user')->save($save_data);
                if ($res) {
                    $this->addOperationLogs("操作：开通代理快捷团单权限,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},代理id:{$id}");
                    $this->redirect_message(U("User/agent"), array('success' => base64_encode('开通成功')));
                } else {
                    $this->redirect_message(U("User/agent"), array('error' => base64_encode('开通失败')));
                }
            } else {
                $this->redirect_message(U("User/agent"), array('error' => base64_encode('该代理已开通快捷编辑团单权限')));
            }
        } else {
            $this->redirect_message(U("User/agent"), array('error' => base64_encode('请求参数非法')));
        }
    }

    /**
     * 编辑用户
     */
    public function editUser() {
        $id = I('get.id', 0, 'intval');
        $url_param = I('get.url_param', 'index', 'strval');
        if ($id) {
            $info = D('User')->info($id);
            $city = $this->_getCategoryList('city');
            $userType = array('N' => '普通用户', 'Y' => '管理员', 'P' => '代理');
            $this->assign('all_city', $city);
            $this->assign('userType', $userType);
            $this->assign('user_data', $info);
            $this->assign('url_param', $url_param);
        } else {
            $this->redirect_message(U("User/{$url_param}"), array('error' => base64_encode('请求参数非法!')));
        }
        $this->display();
    }

    /**
     * 编辑用户处理
     */
    public function doEditUser() {
        $url_param = 'index';
        if (IS_POST) {
            $url_param = I('post.url_param', 'index', 'trim');
            if (trim(I('post.password')) == '') {
                unset($_POST['password']);
            } else {
                $_POST['password'] = encryptPwd(trim(I('post.password')));
            }
            
            $res = D('User')->update();
            if ($res) {
                $this->addOperationLogs("操作 编辑用户,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},用户id:" . I('post.id'));
                $param = array('success' => base64_encode('修改成功'));
            } else {
                $param = array('error' => base64_encode('请修改后再进行提交'));
            }
        } else {
            $param = array('error' => base64_encode('非法操作'));
        }
        if (isset($param['success'])) {
            $this->redirect_message(U("User/{$url_param}"), $param);
        } else {
            $this->redirect_message(U("User/editUser/url_param/{$url_param}"), $param);
        }
    }

    /**
     * 账户充值
     */
    public function userPay() {
        $money = I('post.money', 0, 'trim');
        $marks = I('post.marks','','trim');
        $user_id = I('post.user_id', 0, 'intval');
        if (!$user_id) {
            $this->ajaxReturn(array('status' => -1, 'error' => '请求参数非法'));
        }
        if ($money) {
            $model = M();
            $model->startTrans();
            $Model = D('User');
            $user_info = $Model->info($user_id);
            $up_data = array('id' => $user_id, 'money' => $user_info['money'] + $money,'beizhu'=>$marks);
            if ($money > 0) {
                $action = 'store';
                $log = '线下充值';
                $state = 'income';
            } else if ($money < 0) {
                if (abs($money) > $user_info['money']) {
                    $up_data['money'] = 0;
                    $money = $user_info['money'];
                }
                $action = 'withdraw';
                $log = '用户提现';
                $state = 'expense';
            }
            $res = $Model->save($up_data);
            if ($res) {
                $flowRes = $this->_addFlow($action, abs($money), $user_id, $state);
                if ($flowRes) {
                    $model->commit();
                    $this->addOperationLogs("操作：用户-$log,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},用户id:" . $user_id . '-' . $log . '-' . abs($money) . "元成功");
                    $data = array('status' => 1, 'success' => $log . abs($money) . '元成功');
                } else {
                    $model->rollback();
                    $data = array('status' => -1, 'error' => '充值失败');
                }
            } else {
                $model->rollback();
                $data = array('status' => -1, 'error' => '充值失败');
            }
            $this->ajaxReturn($data);
        } else {
            $this->ajaxReturn(array('status' => -1, 'error' => '请求参数不完整'));
        }
    }

    /**
     * 用户充值流水
     * @param $action
     * @param $money
     *
     * @return mixed
     */
    protected function _addFlow($action, $money, $user_id, $state) {
        $data = array();
        $data['user_id'] = $user_id;
        $data['money'] = $money;
        $data['direction'] = $state;
        $data['action'] = $action;
        $data['create_time'] = time();
        $data['marks'] = I('post.marks');
        return M('flow')->add($data);
    }

    /**
     * 权限管理
     */
    public function authorityManager() {
        $name = I('get.name', '', 'trim');
        $status = I('get.status', '', 'trim');
        $module_name = I('get.module_name', '', 'trim');
        $where = array();
        if ($module_name) {
            $where['module_name'] = strtolower($module_name);
        }
        if ($status != '') {
            $where['status'] = $status;
        }
        if ($name) {
            $where['_string'] = "name like '%$name%' OR title like '%$name%'";
        }
        $auth_rule = M('auth_rule');
        $count = $auth_rule->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $list = $auth_rule->order(array('status' => 'desc', 'id' => 'desc'))
                ->where($where)
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        // 数据整理
        if ($list) {
            foreach ($list as &$v) {
                $v['module_name_text'] = ternary($this->auth_moudel_name[$v['module_name']], '未知平台');
            }
        }

        $data = array(
            'status' => $status,
            'module_name' => $module_name,
            'name' => $name,
            'status_type' => $this->auth_rule_type,
            'plat_type' => $this->auth_moudel_name,
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 权限编辑
     */
    public function authorityManagerEdit() {
        $auth_rule_id = I('get.auth_rule_id', '', 'trim');
        $operation_type = I('post.operation_type', '', 'trim');
        if (!$operation_type) {
            if (!$auth_rule_id) {
                $this->redirect_message(U("User/authorityManager"), array('error' => base64_encode('id不能为空!')));
            }
            $authRuleRes = M('auth_rule')->where(array('id' => $auth_rule_id))->find();
            if (!$authRuleRes) {
                $this->redirect_message(U("User/authorityManager"), array('error' => base64_encode('权限信息不存在!')));
            }
            $this->assign($authRuleRes);
            // 获取城市
            $data = array(
                'auth_rule_type' => $this->auth_rule_type,
                'operation_type' => 'authorityManagerEdit',
            );
            $this->assign($data);
            $this->display();
            exit;
        }
        $auth_rule_data = I('post.auth_rule', array(), '');
        if (!$auth_rule_id) {
            $auth_rule_id = ternary($auth_rule_data['id'], '');
        }
        if (!isset($auth_rule_data['title']) || trim($auth_rule_data['title']) == '') {
            $this->redirect_message(U("User/authorityManagerEdit", array('auth_rule_id' => $auth_rule_id)), array('error' => base64_encode('标题不能为空!')));
        }
        if (!isset($auth_rule_data['status']) || trim($auth_rule_data['status']) == '') {
            $this->redirect_message(U("User/authorityManagerEdit", array('auth_rule_id' => $auth_rule_id)), array('error' => base64_encode('请选择状态!')));
        }

        $data = array(
            'title' => ternary($auth_rule_data['title'], ''),
            'status' => ternary($auth_rule_data['status'], '1'),
        );
        $res = M('auth_rule')->where(array('id' => $auth_rule_id))->save($data);
        if ($res === false) {
            $this->redirect_message(U("User/authorityManagerEdit", array('auth_rule_id' => $auth_rule_id)), array('error' => base64_encode('权限更新失败!')));
        }
        $this->addOperationLogs("操作：编辑权限,title:{$data['title']}id:{$auth_rule_id}");
        redirect(U('User/authorityManager'));
    }

//    /**
//     * 权限删除
//     */
//    public function authorityManagerDelete() {
//        $auth_rule_id = I('get.auth_rule_id', '', 'trim');
//        if (!$auth_rule_id) {
//            $this->ajaxReturn(array('code' => -1, 'error' => 'id不能为空！'));
//        }
//        $auth_rule = M('auth_rule');
//        $authRuleCount = $auth_rule->where(array('id' => $auth_rule_id))->count();
//        if (!$authRuleCount || $authRuleCount <= 0) {
//            $this->ajaxReturn(array('code' => -1, 'error' => '你要删除的权限不存在！'));
//        }
//        $res = $auth_rule->where(array('id' => $auth_rule_id))->delete();
//        if (!$res) {
//            $this->ajaxReturn(array('code' => -1, 'error' => '删除失败！'));
//        }
//        $this->ajaxReturn(array('code' => 0));
//    }

    /**
     * 权限组管理
     */
    public function authorityGroupManager() {

        $name = I('get.name', '', 'trim');
        $status = I('get.status', '', 'trim');
        $module_name = I('get.module_name', '', 'trim');
        $where = array();
        if ($module_name) {
            $where['module_name'] = strtolower($module_name);
        }
        if ($status != '') {
            $where['status'] = $status;
        }
        if ($name) {
            $where['_string'] = "title like '%$name%'";
        }
        $auth_group = M('auth_group');
        $count = $auth_group->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $list = $auth_group->order(array('id' => 'desc'))
                ->where($where)
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        // 数据整理
        if ($list) {
            foreach ($list as &$v) {
                $v['module_name_text'] = ternary($this->auth_moudel_name[$v['module_name']], '未知平台');
            }
            unset($v);
        }

        $data = array(
            'status' => $status,
            'module_name' => $module_name,
            'name' => $name,
            'status_type' => $this->auth_rule_type,
            'plat_type' => $this->auth_moudel_name,
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );

        $this->assign($data);
        $this->display();
    }

    private function __getAuthRuleList($module_name = 'admin', $selectAuth = array()) {
        $where = array('status' => '1');
        if ($module_name) {
            $where['module_name'] = $module_name;
        }
        $auth_rule = M('auth_rule');
        $auth_rule_list = $auth_rule->where($where)->select();

        if ($selectAuth && $auth_rule_list) {
            foreach ($auth_rule_list as &$_v) {
                $_v['checked'] = '0';
                if (isset($_v['id']) && in_array($_v['id'], $selectAuth)) {
                    $_v['checked'] = '1';
                }
            }
        }

        // 整理数据
        $data = array();
        if ($auth_rule_list) {
            foreach ($auth_rule_list as $v) {
                if (isset($v['name']) && trim($v['name'])) {
                    $key = explode('/', $v['name']);
                    array_pop($key);
                    $key = implode('/', $key);
                    if (!isset($data[$key])) {
                        $data[$key] = array(
                            'name' => ternary($this->auth_rule_name[$key], '未知权限'),
                            'list' => array()
                        );
                    }
                    $data[$key]['list'][] = $v;
                }
            }
        }
        return $data;
    }

    /**
     * 权限组添加
     */
    public function authorityGroupManagerAdd() {
        $operation_type = I('post.operation_type', '', 'trim');
        $module_name = I('get.module_name', '', 'trim');
        if (!$module_name) {
            $module_name = I('post.module_name', strtolower(MODULE_NAME), 'trim');
        }
        if (!$operation_type) {
            $auth_rule_list = $this->__getAuthRuleList($module_name);
            // 获取城市
            $data = array(
                'auth_rule_type' => $this->auth_rule_type,
                'auth_rule_list' => $auth_rule_list,
                'operation_type' => 'authorityGroupManagerAdd',
                'module_name' => $module_name,
            );
            $this->assign($data);
            $this->display();
            exit;
        }
        $auth_group_data = I('post.auth_group', array(), '');
        if (!isset($auth_group_data['title']) || trim($auth_group_data['title']) == '') {
            $this->redirect_message(U("User/authorityGroupManagerAdd"), array('error' => base64_encode('权限组名称不能为空!')));
        }
        if (!isset($auth_group_data['status']) || trim($auth_group_data['status']) == '') {
            $this->redirect_message(U("User/authorityGroupManagerAdd"), array('error' => base64_encode('请选择全选组状态!')));
        }
        if (!isset($auth_group_data['remark']) || trim($auth_group_data['remark']) == '') {
            $this->redirect_message(U("User/authorityGroupManagerAdd"), array('error' => base64_encode('备注不能为空!')));
        }
        if (!isset($auth_group_data['rule']) || !$auth_group_data['rule']) {
            $this->redirect_message(U("User/authorityGroupManagerAdd"), array('error' => base64_encode('请勾选选权限!')));
        }
        $authGroupCount = M('auth_group')->where(array('title' => trim($auth_group_data['title'])))->count();
        if ($authGroupCount && $authGroupCount > 0) {
            $this->redirect_message(U("User/authorityGroupManagerAdd"), array('error' => base64_encode('该权限组已经存在，请更换权限组名!')));
        }

        $data = array(
            'title' => trim(ternary($auth_group_data['title'], '')),
            'status' => ternary($auth_group_data['status'], '1'),
            'rules' => @implode(',', ternary($auth_group_data['rule'], array())),
            'remark' => ternary($auth_group_data['remark'], '权限备注'),
            'module_name' => $module_name,
        );
        $res = M('auth_group')->add($data);
        if ($res === false) {
            $this->redirect_message(U("User/authorityGroupManagerAdd"), array('error' => base64_encode('权限更新失败!')));
        }
        $this->addOperationLogs("操作：添加权限组,title:{$data['title']},id:{$res},module_name:{$module_name}");
        redirect(U('User/authorityGroupManager'));
    }

    /**
     * 权限组编辑
     */
    public function authorityGroupManagerEdit() {
        $auth_group_id = I('get.auth_group_id', '', 'trim');
        $operation_type = I('post.operation_type', '', 'trim');
        $module_name = I('get.module_name', '', 'trim');
        if (!$module_name) {
            $module_name = I('post.module_name', strtolower(MODULE_NAME), 'trim');
        }
        if (!$operation_type) {
            if (!$auth_group_id) {
                $this->redirect_message(U("User/authorityGroupManager"), array('error' => base64_encode('id不能为空!')));
            }
            $authGroupRes = M('auth_group')->where(array('id' => $auth_group_id))->find();
            if (!$authGroupRes) {
                $this->redirect_message(U("User/authorityGroupManager"), array('error' => base64_encode('权限信息不存在!')));
            }
            $this->assign($authGroupRes);
            $select = array();
            if (isset($authGroupRes['rules']) && trim($authGroupRes['rules'])) {
                $select = explode(',', $authGroupRes['rules']);
            }
            $auth_rule_list = $this->__getAuthRuleList($module_name, $select);
            // 获取城市
            $data = array(
                'auth_rule_type' => $this->auth_rule_type,
                'auth_rule_list' => $auth_rule_list,
                'operation_type' => 'authorityGroupManagerEdit',
                'module_name' => $module_name,
            );
            $this->assign($data);
            $this->display('User/authorityGroupManagerAdd');
            exit;
        }
        $auth_group_data = I('post.auth_group', array(), '');
        if (!$auth_group_id) {
            $auth_group_id = ternary($auth_group_data['id'], '');
        }
        if (!isset($auth_group_data['title']) || trim($auth_group_data['title']) == '') {
            $this->redirect_message(U("User/authorityGroupManagerEdit", array('auth_group_id' => $auth_group_id)), array('error' => base64_encode('权限组名称不能为空!')));
        }
        if (!isset($auth_group_data['status']) || trim($auth_group_data['status']) == '') {
            $this->redirect_message(U("User/authorityGroupManagerEdit", array('auth_group_id' => $auth_group_id)), array('error' => base64_encode('请选择全选组状态!')));
        }
        if (!isset($auth_group_data['remark']) || trim($auth_group_data['remark']) == '') {
            $this->redirect_message(U("User/authorityGroupManagerEdit", array('auth_group_id' => $auth_group_id)), array('error' => base64_encode('备注不能为空!')));
        }
        if (!isset($auth_group_data['rule']) || !$auth_group_data['rule']) {
            $this->redirect_message(U("User/authorityGroupManagerEdit", array('auth_group_id' => $auth_group_id)), array('error' => base64_encode('请勾选选权限!')));
        }
        $authGroupCount = M('auth_group')->where(array('title' => trim($auth_group_data['title']), 'id' => array('neq', $auth_group_id)))->count();
        if ($authGroupCount && $authGroupCount > 0) {
            $this->redirect_message(U("User/authorityGroupManagerEdit", array('auth_group_id' => $auth_group_id)), array('error' => base64_encode('该权限组已经存在，请更换权限组名!')));
        }

        $data = array(
            'title' => ternary($auth_group_data['title'], ''),
            'status' => ternary($auth_group_data['status'], '1'),
            'rules' => @implode(',', ternary($auth_group_data['rule'], array())),
            'remark' => ternary($auth_group_data['remark'], '权限备注'),
        );
        $res = M('auth_group')->where(array('id'=>$auth_group_id))->save($data);
        if ($res === false) {
            $this->redirect_message(U("User/authorityGroupManagerEdit", array('auth_group_id' => $auth_group_id)), array('error' => base64_encode('权限更新失败!')));
        }
        $this->addOperationLogs("操作：编辑权限组,title:{$data['title']}，id:{$auth_group_id}");
        redirect(U('User/authorityGroupManager'));
    }

    /**
     * 权限组删除
     */
    public function authorityGroupManagerDelete() {
        $auth_group_id = I('get.auth_group_id', '', 'trim');
        if (!$auth_group_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => 'id不能为空！'));
        }
        $auth_group = M('auth_group');
        $authGroupCount = $auth_group->where(array('id' => $auth_group_id))->count();
        if (!$authGroupCount || $authGroupCount <= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '你要删除的权限组不存在！'));
        }
        
        $auth_group_access = M('auth_group_access');
        $where = array('group_id' => $auth_group_id);
        $auth_group_access_count = $auth_group_access->where($where)->count();
        
        $model = M();
        $model->startTrans();
        $res = $auth_group->where(array('id' => $auth_group_id))->delete();
        if (!$res) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '删除失败！'));
        }

        if ($auth_group_access_count && $auth_group_access_count > 0) {
            $res = $auth_group_access->where($where)->delete();
            if (!$res) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '删除失败！'));
            }
        }
        $model->commit();

        $this->addOperationLogs("操作：删除权限组,id:{$auth_group_id}");
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 批量禁用权限
     */
    public function authorityBatchDisable() {
        $auth_ids = I('post.auth_id', '', 'trim');
        if (!$auth_ids) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请选择禁用的权限点！'));
        }
        $auth_ids = explode(',', $auth_ids);
        $where = array(
            'id' => array('in', $auth_ids),
            'status' => '1'
        );
        $res = M('auth_rule')->where($where)->save(array('status' => '0'));
        if ($res === false) {
            $this->ajaxReturn(array('code' => -1, 'error' => '操作失败！'));
        }
        $this->addOperationLogs("操作：批量禁用权限,ids:[" . implode(',', $auth_ids) . "]");
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 给管理员授权
     */
    public function doAdminAuth() {
        $user_id = I('get.user_id', '', 'trim');
        $operation_type = I('post.operation_type', '', 'trim');
        if (!$operation_type) {
            if (!$user_id) {
                $this->redirect_message(U("User/manage"), array('error' => base64_encode('用户id不能为空!')));
            }
            $userRes = M('user')->where(array('id' => $user_id))->field('id')->find();
            if (!$userRes) {
                $this->redirect_message(U("User/manage"), array('error' => base64_encode('权限信息不存在!')));
            }
            $this->assign($userRes);
            $auth_group_list = $this->getAuthGroupList('admin', $user_id);
            // 获取城市
            $data = array(
                'auth_group_list' => $auth_group_list,
                'operation_type' => 'doAdminAuth',
                'module_name' => 'admin',
            );
            $this->assign($data);
            $this->display('User/doAuth');
            exit;
        }

        $user = I('post.user', array(), '');
        if (!$user_id) {
            $user_id = ternary($user['id'], '');
        }
        if (!$user_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '用户id不能为空！'));
        }
        if (!isset($user['module_name']) || trim($user['module_name']) == '') {
            $this->ajaxReturn(array('code' => -1, 'error' => '权限组名称不能为空！'));
        }
        if (!isset($user['group_id']) || !$user['group_id']) {
            $user['group_id'] = array();
        }
        if (is_string($user['group_id'])) {
            $user['group_id'] = @explode(',', $user['group_id']);
        }
        $auth_group_access = M('auth_group_access');
        $module_name = strtolower($user['module_name']);
        $data = array();
        foreach ($user['group_id'] as $v) {
            $data[] = array(
                'module_name' => $module_name,
                'uid' => $user_id,
                'group_id' => trim($v),
            );
        }
        // 权限修改开启事务
        $model = M();
        $model->startTrans();
        $where = array(
            'uid' => $user_id,
            'module_name' => $module_name
        );
        $res = $auth_group_access->where($where)->count();
        if ($res && $res > 0) {
            $res = $auth_group_access->where($where)->delete();
            if (!$res) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '授权失败！'));
            }
        }
        if ($data) {
            $res = $auth_group_access->addAll($data);
            if (!$res) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '授权失败！'));
            }
        }
        $model->commit();

        $this->addOperationLogs("操作：给管理员授权,uid:{$user_id},auth_group_ids:[" . implode(',', $user['group_id']) . "]");
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 给代理授权
     */
    public function doManageAuth() {
        
    }

}
