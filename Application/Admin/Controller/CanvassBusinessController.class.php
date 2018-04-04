<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-08-07
 * Time: 09:51
 */

namespace Admin\Controller;

class CanvassBusinessController extends CommonController {

    /**
     * 获取手机号码归属地URL
     */
    const URL = 'https://tcc.taobao.com/cc/json/mobile_tel_segment.htm';

    /**
     * 首页
     */
    public function index() {
        $model = M('canvass_business_visit_log');
        // 总数据统计
        $total = $model->field('visit_db_id,count(*) as visit_num,sum(is_intention="Y") intention_num')->group('visit_db_id')->order('NULL')->select();
        //print_r($total);

        $where = array(
            'create_time' => array('BETWEEN', array(strtotime(date('Y-m-d')), strtotime(date('Y-m-d 23:59:59'))))
        );

        $today = $model->field('visit_db_id,count(*) as visit_num,sum(is_intention="Y") intention_num')->where($where)->group('visit_db_id')->order('NULL')->select();
        $bdList = $this->_getBDList($total);

        // 格式化今日统计数据
        $todayData = array();
        foreach ($today as $row) {
            $todayData[$row['visit_db_id']] = array(
                'visit_num' => $row['visit_num'],
                'intention_num' => $row['intention_num']
            );
        }
        // print_r($todayData);
        $this->assign('total', $total);
        $this->assign('today', $todayData);
        $this->assign('bdList', $bdList);
        $this->display();
    }

    /**
     * 未回访
     */
    public function unvisit() {
        $Model = D('User');
        $allCity = $this->_getCategoryList('city');
        $searchParam = array(
            array('id', ''),
            array('city_id', 0),
            array('username', ''),
            array('mobile', ''),
        );
        $where = $this->createSearchWhere($searchParam);
        $displayWhere = $this->getSearchParam($searchParam);
        $where['visit_db_id'] = $this->_getUserId();
        if($this->getCanvBusUser()){
             $where['visit_db_id'] = array('neq',0);
        }
        $where['canvass_business_user_id'] = 0;
        $count = $Model->getTotal($where);
        $page = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $field = 'id,city_id,username,mobile,create_time';
        $data = $Model->getList($where, 'id desc', $limit, $field);
        $this->_writeDBErrorLog($data, $Model);
        if ($data) {
            foreach ($data as &$row) {
                if($row['city_id'] == 0){
                    $row['city_id'] = ternary(M('order')->where("user_id={$row['id']}")->getField('city_id'),957);
                }
                $orderData = M('order')->field('sum(origin) as totalMoney,count(id) as num')->where(array('user_id' => $row['id'], 'state' => 'pay'))->select();
                $row['num'] = ternary($orderData['num'],0);
                $row['totalMoney'] = ternary($orderData['totalMoney'],0);
                $row['mobile'] = $this->_getMobileFrom($row['mobile']);
            }
        }
        $this->assign('allCity', $allCity);
        $this->assign('data', $data);
        $this->assign('displayWhere', $displayWhere);
        $this->display();
    }

    /**
     * 设置客户状态
     */
    public function setTarget() {
        $id = I('get.user_id', 0, 'intval');
        $status = I('get.status');
        if ($id && $status) {
            $user = M('user')->find($id);
            if(!$user['city_id']){
                $user['city_id'] = ternary(M('order')->where("user_id={$user['id']}")->getField('city_id'),957);
            }
            if ($user) {
                $data = array(
                    'username' => $user['username'],
                    'city_id' => $user['city_id'],
                    'mobile' => $user['mobile'],
                    'remark_content' => '',
                    'visit_db_id' => $this->_getUserId(),
                    'status' => $status,
                    'create_time' => time()
                );
                $this->_addDbTarget($data, $id,$status);
            } else {
                $this->redirect_message(U('CanvassBusiness/unvisit'), array('error' => base64_encode('该用户存在无法添加为意向客户!')));
            }
        } else {
            $this->redirect_message(U('CanvassBusiness/unvisit'), array('error' => base64_encode('缺少客户编号!')));
        }
    }

    /**
     * 获取手机号码归属地
     * @param $mobile
     */
    protected function _getMobileFrom($mobile) {
        if (checkMobile($mobile) === false) {
            return $mobile . '-该手机号码格式不正确';
        }
        $url = self::URL.'?tel='.$mobile;
        $data = file_get_contents($url);
        $str = substr($data,strpos($data,'telString')+11,11);
        $str.= '-'.iconv('GB2312','UTF-8',substr($data,strpos($data,'carrier')+9,8));
        return $str;
    }

    /**
     * 已回访
     */
    public function visited() {
        $Model = M('canvass_business_user');
        $searchParam = array(
            array('id', ''),
            array('city_id', 0),
            array('username', ''),
            array('mobile', ''),
        );
        $where = $this->createSearchWhere($searchParam);
        $displayWhere = $this->getSearchParam($searchParam);
        if(!$this->getCanvBusUser()){
            $where['visit_db_id'] = $this->_getUserId();
        }
        $count = $Model->where($where)->count();
        $page = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $data = $Model->where($where)->order('id desc')->limit($limit)->select();
        if ($data) {
            foreach ($data as &$val) {
                $val['from_mobile'] = $this->_getMobileFrom($val['mobile']);
                $val['visit_name']  = M('user')->getFieldById($val['visit_db_id'],'realname');
            }
        }
        $allCity = $this->_getCategoryList('city');
        $this->assign('allCity', $allCity);
        $this->assign('data', $data);
        $this->assign('displayWhere', $displayWhere);
        $this->_writeDBErrorLog($data, $Model);
        $this->display();
    }

    /**
     * 无意向客户设置为有意向客户
     */
    public function setHaveTarget(){
        $id = I('get.id',0,'intval');
        if($id){
            $Model = M('canvass_business_user');
            $user_info = $Model->find($id);
            if($user_info && $user_info['status'] == 1){
                $up_data = array('id'=>$id,'status'=>2);
                $res = $Model->save($up_data);
                if($res){
                    $this->addOperationLogs("操作：变更意向用户,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},变更id:{$id}");
                    $this->_addVisitLog($id,'Y');
                    $this->redirect_message(U('CanvassBusiness/visited'), array('success' => base64_encode('意向客户设置成功!')));
                }else{
                    $this->redirect_message(U('CanvassBusiness/visited'), array('error' => base64_encode('意向客户设置失败!')));
                }
            }else{
                $this->redirect_message(U('CanvassBusiness/visited'), array('error' => base64_encode('该用户已经当前已经是意向客户了!')));
            }
        }else{
            $this->redirect_message(U('CanvassBusiness/visited'), array('error' => base64_encode('缺少客户编号!')));
        }
    }

    /**
     * 意向客户
     */
    public function target() {
        if(!$this->getCanvBusUser()){
            $_GET['visit_db_id'] = $this->_getUserId();
        }
        $_GET['status'] = 2;
        $searchParam = array(
            array('city_id', ''),
            array('visit_num', ''),
            array('status', ''),
            array('visit_db_id', '')
        );
        $list = $this->_getList(M('canvass_business_user'), $searchParam);
        $this->_writeDBErrorLog($list, M('canvass_business_user'), 'admin');
        $bdList = $this->_getBDList($list);

        $this->assign('bdList', $bdList);
        $this->assign('list', $list);
        $this->assign('displayWhere', $this->getSearchParam($searchParam));
        $cityList = $this->_getCategoryList('city');
        $this->assign('cityList', $cityList);
        $this->display();
    }

    /**
     * 添加意向客户
     */
    public function addTarget() {
        if (IS_POST) {
            $data = array(
                'username' => I('post.username', '', 'trim'),
                'city_id' => I('post.city_id', 0, 'intval'),
                'mobile' => I('post.mobile', '', 'trim'),
                'remark_content' => I('post.remark', '', 'trim'),  
                'visit_db_id' => $this->_getUserId(),
                'status' => 2,
                'visit_num' => 0,
               // 'visit_time' => time(),
                'update_time' => time(),
                'create_time' => time()
            );
            if ($res = M('canvass_business_user')->add($data)) {
                if(trim($data['remark_content'])){
                     $this->_addVisitLog($res,'Y',$data['remark_content']);
                }
                $this->addOperationLogs("操作：添加意向用户,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},用户id:{$res}");
                $this->success('新增成功');
            } else {
                $this->error('新增失败');
            }
        } else {
            $cityList = $this->_getCategoryList('city');
            $this->assign('cityList', $cityList);
            $this->display();
        }
    }

    /**
     * 添加意向客户
     * @param $data
     */
    protected function _addDbTarget($data, $id,$status) {
        if ($res = M('canvass_business_user')->add($data)) {
            M('user')->save(array('canvass_business_user_id' => $res, 'id' => $id));
            $is_intention = $status == 1 ? 'N' : 'Y';
            $this->_addVisitLog($res,$is_intention);
            $this->addOperationLogs("操作：添加意向用户,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},用户id:{$res},用户状态：{$is_intention}");
            $this->redirect_message(U('CanvassBusiness/unvisit'), array('success' => base64_encode('操作成功!')));
        } else {
            $this->redirect_message(U('CanvassBusiness/unvisit'), array('error' => base64_encode('操作失败!')));
        }
    }

    /**
     * 记录日志
     * @param $res
     * @param $is_intention
     */
    protected function _addVisitLog($res,$is_intention,$remark_content=''){
        if(!trim($remark_content)){
            $remark_content = I('post.content');
        }
        $insert_data = array(
            'remark_content' => $remark_content,
            'canvass_business_user_id' => $res,
            'visit_db_id' => $this->_getUserId(),
            'is_intention' => $is_intention,
            'create_time' => time()
        );
         M('canvass_business_visit_log')->add($insert_data);
         M('canvass_business_user')->where('id=' . $res)->setInc('visit_num');
         M('canvass_business_user')->where('id=' . $res)->save(array('visit_time'=>time()));
    }

    /**
     * 记录
     */
    public function score() {
        $model = M('canvass_business_visit_log');
        if (IS_POST) {
            $remark = I('post.remark', 'trim');
            $id = I('post.id', 'trim');
            $data = array(
                'remark_content' => $remark,
                'canvass_business_user_id' => $id,
                'visit_db_id' => $this->_getUserId(),
                'is_intention' => 'Y',
                'create_time' => time()
            );
            if ($res = $model->add($data)) {
                M('canvass_business_user')->where('id=' . $id)->setInc('visit_num');
                M('canvass_business_user')->where('id=' . $id)->save(array('visit_time'=>time()));
                $this->addOperationLogs("操作：招商添加访问记录,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},访问记录id:{$res}");
                $this->success('记录成功');
            } else {
                $this->error('记录失败');
            }
        } else {
            $user_id = I('param.user_id',0,'intval');
            if($user_id){
                $user = M('user')->find($user_id);
                if(isset($user['city_id']) && !$user['city_id']){
                    $user['city_id'] = ternary(M('order')->where("user_id={$user['id']}")->getField('city_id'),957);
                }
                if ($user) {
                    $data = array(
                        'username'       => $user['username'],
                        'city_id'        => $user['city_id'],
                        'mobile'         => $user['mobile'],
                        'remark_content' => '',
                        'visit_db_id'    => $this->_getUserId(),
                        'status'         => 2,
                        'visit_num' => 1,
                        'visit_time' => time(),
                        'update_time' => time(),
                        'create_time'    => time()
                    );
                    $res = M('canvass_business_user')->add($data);
                    if($res){
                        $this->addOperationLogs("操作：添加意向用户,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},用户id:{$res},用户状态：有意向");
                        M('user')->save(array('canvass_business_user_id' => $res, 'id' => $user_id));
                        $id = $res;
                    }else{
                        $this->redirect_message(U('CanvassBusiness/unvisit'), array('error' => base64_encode('添加失败!请重新添加')));
                    }
                }else{
                    $this->redirect_message(U('CanvassBusiness/unvisit'), array('error' => base64_encode('用户不存在!')));
                }
            }else{
                $this->_checkblank('id');
                $id = I('param.id', 0, 'intval');
            }
            $map = array(
                'canvass_business_user_id' => $id
            );
            $list = $model->where($map)->select();
            $bdList = $this->_getBDList($list);
            $this->assign('bdList', $bdList);
            $this->assign('list', $list);
            $this->assign('id', $id);
            $this->display();
        }
    }

    /**
     * 获取bd
     * @param $list
     * @return array|mixed
     */
    protected function _getBDList($list) {
        if ($list) {
            $bdId = array();
            foreach ($list as $row) {
                $bdId[] = (int) $row['visit_db_id'];
            }

            if ($bdId) {
                $map = array(
                    'id' => array('IN', array_unique($bdId))
                );
                $bdList = M('user')->where($map)->getField('id,realname');
                return $bdList;
            }
        }
        return array();
    }

    /**
     * 无意向
     */
    public function unTarget() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $update_data = array('status'=>1,'update_time'=>time(),'visit_time'=>time());
        if (M('canvass_business_user')->where('id=' . $id)->save($update_data)) {
            // 记录无意向日志
            M('canvass_business_user')->where('id=' . $id)->setInc('visit_num');
            $data = array(
                'remark_content' => '',
                'canvass_business_user_id' => $id,
                'visit_db_id' => $this->_getUserId(),
                'is_intention' => 'N',
                'create_time' => time()
            );
            $res = M('canvass_business_visit_log')->add($data);
            $this->addOperationLogs("操作：招商添加无意向记录,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},记录id:{$res}");
            $this->addOperationLogs("操作：招商设置无意向,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},用户id:{$id}");
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }

    /**
     * 用户分配
     */
    public function userDistribution() {

        // 接受参数
        $city_id = I('get.city_id', '', 'trim');
        $pay_sum_min = I('get.pay_sum_min', '0.00', 'doubleval');
        $pay_sum_max = I('get.pay_sum_max', '0.00', 'doubleval');
        $pay_count_min = I('get.pay_count_min', '0', 'intval');
        $pay_count_max = I('get.pay_count_max', '0', 'intval');
        $start_time = I('get.start_time', '', 'trim');
        $end_time = I('get.end_time', '', 'trim');
        $visit_user_id = I('get.visit_user_id', '0', 'trim');

        $where = array(
            '_string' => "((`user`.mobile IS NOT NULL AND `user`.mobile <>'') OR (`user`.mobile <>'' AND `user`.mobile IS NOT NULL )) AND ((`order`.city_id > 0) OR (`user`.city_id > 0))",
            'order.state' => 'pay',
            'user.visit_db_id' => 0,
        );
        $having = '';
        $city_where = '';
        if ($city_id) {
            $city_where = "`user`.city_id='{$city_id}' OR `order`.city_id='{$city_id}'";
        }
        if ($city_where) {
            $where['_string'] = "({$where['_string']}) AND ({$city_where})";
        }

        // 注册时间
        if ($start_time && $end_time) {
            $where['user.create_time'] = array(array('egt', strtotime($start_time)), array('elt' => strtotime($end_time)));
        }

        // 负责人过滤
        if ($visit_user_id) {
            $where['user.visit_db_id'] = $visit_user_id;
        }

        // 购买金额
        $having_pay_sum = '';
        if ($pay_sum_min >= 0 && $pay_sum_max > 0 && $pay_sum_max > $pay_sum_min) {
            $having_pay_sum = "sum(`order`.origin)>={$pay_sum_min} AND sum(`order`.origin)<={$pay_sum_max}";
        }
        if ($having_pay_sum) {
            $having = $having_pay_sum;
        }

        $having_pay_count = '';
        if ($pay_count_min >= 0 && $pay_count_max > 0 && $pay_count_max > $pay_count_min) {
            $having_pay_count = "count(`order`.id)>={$pay_count_min} AND count(`order`.id)<={$pay_count_max}";
        }
        if ($having_pay_count) {
            $having = $having ? "$having AND $having_pay_count" : $having_pay_count;
        }

        $user = M('user');
//        if ($having) {
//            $user->having($having);
//        }

        $count = $user->where($where)->join('left JOIN `order` ON `order`.user_id=`user`.id')->field('COUNT(DISTINCT(`user`.id)) as cb_count')->find();
        $count = ternary($count['cb_count'], 0);
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'user.id' => 'user_id',
            'user.username' => 'user_username',
            'user.city_id' => 'user_city_id',
            'user.mobile' => 'user_mobile',
            'user.create_time' => 'user_create_time',
            'order.city_id' => 'order_city_id',
            'order.mobile' => 'order_mobile',
            'count(`order`.id)' => 'order_pay_count',
            'sum(`order`.origin)' => 'order_pay_sum'
        );
        if ($having) {
            $user->having($having);
        }
        $list = $user->field($field)->order(array('user.id' => 'desc'))->group('`user`.id')
                ->where($where)->join('left JOIN `order` ON `order`.user_id=`user`.id')->limit($page->firstRow . ',' . $page->listRows)
                ->select();

        // 整理数据
        $city_res = $this->_getCategoryList('city');
        if ($list) {
            foreach ($list as &$v) {

                // 处理城市
                if (!isset($v['user_city_id']) || !trim($v['user_city_id'])) {
                    $v['user_city_id'] = ternary($v['order_city_id'], '');
                }
                $v['city_name'] = ternary($city_res[$v['user_city_id']]['name'], '');

                // 处理电话
                if (!isset($v['user_mobile']) || !trim($v['user_mobile'])) {
                    $v['user_mobile'] = ternary($v['order_mobile'], '');
                }
                unset($v);
            }
        }

        // 获取招商人员
        $visit_user = $this->getCanvBusUser(false);

        $data = array(
            'city_res' => $city_res,
            'city_id' => $city_id,
            'pay_sum_min' => $pay_sum_min,
            'pay_sum_max' => $pay_sum_max,
            'pay_count_min' => $pay_count_min,
            'pay_count_max' => $pay_count_max,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'visit_user_id' => $visit_user_id,
            'visit_user' => ternary($visit_user['employee'], array()),
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 执行分配用户
     */
    public function userDistributionExec() {
        $db_user_id = I('post.db_user_id', '', 'trim');
        $user_ids = I('post.user_ids', '', 'trim');
        if (is_string($user_ids)) {
            $user_ids = explode(',', $user_ids);
        }
        if (!$user_ids) {
            $this->ajaxReturn(array('code' => -1, 'error' => '未选择要分配的用户'));
        }
        if (!$db_user_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '未选择要分配DB'));
        }

        $where = array('id' => array('in', $user_ids));
        $user_count = M('user')->where($where)->count();
        if (!$user_count && $user_count <= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '要分配的用户为空！'));
        }
        $res = M('user')->where($where)->save(array('visit_db_id' => $db_user_id));
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '分配失败！'));
        }
        // 添加操作日志
        $this->addOperationLogs("操作：招商批量分配用户,user_ids:[" . implode(',', $user_ids) . "],db_id:{$db_user_id}");
        $this->ajaxReturn(array('code' => 0, 'success' => '分配成功！'));
    }

}
