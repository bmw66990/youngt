<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-03-18
 * Time: 13:55
 */
namespace Manage\Controller;

/**
 * 商家模型
 * Class PartnerController
 * @package Manage\Controller
 */
class PartnerController extends CommonController {

    /**
     * 商家列表
     */
    public function index() {
        $_GET['city_id'] = $this->_getCityId();
        $searchParam = array(
            array('id', ''),
            array('title', '', 'like'),
            array('city_id', ''),
            array('group_id', ''),
            array('db_id', 0),
        );

        $where = $this->createSearchWhere($searchParam);
        //$where['fid'] = 0;

        $model = D('Partner');
        $count = $model->getTotal($where);
        $this->_writeDBErrorLog($count, $model);
        $page  = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $field = 'id,title,phone,mobile,city_id,group_id,db_id,is_discount';
        $list  = $model->getList($where, 'head DESC,id DESC', $limit, $field);
        $this->_writeDBErrorLog($list, $model);
        //分类列表
        $this->_getCategoryList('partner');
        //BD列表
        $this->_getBdList();
        $searchValue = $this->getSearchParam($searchParam);

        $this->assign('list', $list);
        $this->assign('searchValue', $searchValue);
        $this->assign('pages', $page->show());
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 添加前置操作
     */
    public function _before_add() {
        $this->_getCategoryList('partner');
        $this->_getCategoryList('district', array('fid' => $this->_getCityId()));
    }

    /**
     * insert前置操作
     */
    public function _before_insert() {
        $_POST['city_id'] = $this->_getCityId();
        $point = I('post.longlat');
        if(!empty($point)) {
            $pointArr      = explode(',', $point);
            $_POST['lat']  = $pointArr[0];
            $_POST['long'] = $pointArr[1];
        }
        $branch = I('post.fid', 0, 'intval');
        if(!empty($branch)) {
            $_POST['is_branch'] = 'N';
        }
    }

    /**
     * 添加操作
     */
    public function insert() {
        $model = D('Partner');
        if ($model->create()) {
            if ($model->add()) {
                $jumpUrl = U('Partner/index');
                $fid = I('post.fid', 0, 'intval');
                if(!empty($fid)) {
                    $jumpUrl = U('Partner/branchList', array('id' => $fid));
                }
                $this->success('添加成功', $jumpUrl);
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
     * 编辑操作
     */
    public function editt() {
        $id = $_POST['id'];
        if(trim($_POST['password'])) {
            $_POST['password'] = md5(trim($_POST['password']) . C('PWD_ENCRYPT_STR'));
        }else{
            unset($_POST['password']);
        }
        $Partner = M('Partner');

        // 检测商户名称
        $title = I('post.title','','trim');
        $map = array(
            'title'   => array('eq', $title),
            'id'      => array('neq', $id),
            'city_id' => $this->_getCityId()
        );
        $exist = $Partner->where($map)->find();
        if ($exist) {
            $this->error('商户名已存在');
            exit();
        }
        if ($Partner->save($_POST)) {
            $jumpUrl = U('Partner/index');
            $fid = I('post.fid', 0, 'intval');
            if(!empty($fid)) {
                $jumpUrl = U('Partner/branchList', array('id' => $fid));
            }
            $this->success('修改成功', $jumpUrl);
            exit();
        } else {
            $this->errmsg = $Partner->getDbError();
        }
        $this->error($this->errmsg);
    }
    /**
     * 编辑前置操作
     */
    public function _before_edit() {
        $this->_getCategoryList('partner');
        $this->_getCategoryList('district', array('fid' => $this->_getCityId()));
    }

    /**
     * update前置操作
     */
    public function _before_update() {
        $point = I('post.longlat');
      //  $password = I('post.password', '', 'trim');
        if(!empty($point)) {
            $pointArr      = explode(',', $point);
            $_POST['lat']  = $pointArr[0];
            $_POST['long'] = $pointArr[1];
        }
//        if ($password) {
//            $_POST['password'] = encryptPwd(trim($password));
//        }else{
//            unset($_POST['password']);
//        }
    }

    /**
     * 获取一级商家
     */
    protected function _getTopPartner() {
        $where['city_id']   = $this->_getCityId();
        $where['fid']       = 0;
        $model = D('Partner');
        $list  = $model->getList($where, '', '', 'id,title');
        //记录错误日志
        $this->_writeDBErrorLog($list, $model);
        $this->assign('topPartner', $list);
        return $list;
    }

    /**
     * 获取业务员列表
     */
    protected function _getBdList() {
        $where['city_id'] = $this->_getCityId();
        $model  = D('BdUser');
        $bdList = $model->getList($where);
        //记录错误日志
        $this->_writeDBErrorLog($bdList, $model);
        $newBD  = array();
        foreach($bdList as $row) {
            $newBD[$row['id']] = $row;
        }
        $this->assign('bdList', $newBD);
    }

    /**
     * ajax检查用户已经存在
     */
    public function checkPartnerName() {
        $model = D('Partner');
        if($this->_checkblank('username', 1) !== true) {
            ajaxReturnNew('msg', '请填写用户名', 0);
        }
        $userName = I('post.username');
        $rs       = $model->checkAccount($userName);
        if($rs === false) {
            //记录错误日志
            $this->_writeDBErrorLog($rs, $model);
        }
        if (IS_AJAX) {
            ajaxReturnNew('msg', '用户名已经存在', 1);
        } else {
            ajaxReturnNew('msg', '非法访问！', 0);
        }
    }

    /**
     * 商家详情
     */
    public function detail() {
        $this->_checkblank('id');
        $id    = I('get.id', 0, 'intval');
        $model = D('Partner');
        $info  = $model->getPartnerDetail($id);
        if(empty($info)) {
            //记录错误日志
            $this->_writeDBErrorLog($info, $model);
            $this->error('该商家不存在');
        }
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 删除商家以及商家对应的商品
     */
    public function delete() {
        $this->_checkblank('id');
        $id      = I('get.id', 0, 'intval');
        $partner = D('Partner');
        $rs      = $partner->delete($id);
        if(!$rs) {
            //记录错误日志
            $this->_writeDBErrorLog($rs, $partner);
            $this->error('删除失败');
        } else {
//            $where['partner_id'] = $id;
//            $rs = D('Team')->delTeam($where);
//            if($rs === false) {
//                $this->error('删除失败');
//            } else {
//                $this->success('删除成功', U(cookie('current_url')));
//            }
        }
    }

    /**
     * 商户团单列表
     */
    public function teamList() {
        $pid = I('get.pid', 0, 'intval');
        if ($pid <= 0) {
            $this->error('非法参数！');
        }
        $id = I('get.id', 0, 'intval');
        $title = I('get.title', '', 'trim');

        $map = array(
            'city_id' => $this->_getCityId(),
            'partner_id' => $pid,
        );
        $searchValue['pid'] = $pid;
        if ($id != 0) {
            $map['id'] = $id;
            $searchValue['id'] = $id;
        }
        if ($title != '') {
            $map['title'] = array('LIKE', '%'.$title.'%');
            $searchValue['title'] = $title;
        }

        $city = session(C('CITY_AUTH_KEY'));
        $url_prefix = 'http://'.$city['ename'].'.'.APP_DOMAIN;

        $model = D('Team');
        $count = $model->where($map)->count('id');
        $page  = $this->pages($count, 7);
        $show  = $page->show();
        $list = $model->where($map)->order('sort_order desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign('pid', $pid);
        $this->assign('searchValue', $searchValue);
        $this->assign('count', $count);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('url_prefix', $url_prefix);
        $this->display();
    }

    /**
     * 商家接待
     */
    public function reception() {
        $id = I('get.id', 0 ,'trim');
        $username = I('get.username', '','trim');

        $map = ['p.city_id'=>$this->_getCityId()];
        if ($id > 0) {
            $map['p.id'] = $id;
            $searchValue['id'] = $id;
        }
        if (strlen($username) > 0) {
            $map['p.username'] = array('like', '%'.$username.'%');
            $searchValue['username'] = $username;
        }
        $map['p.display'] = 'Y';

        $time = $this->_getSearchTime();
        if ($time['et'] > 0) {
            $map['t.end_time'] = array('gt', $time['et']);
        }
        $_p_model = D('Partner');
        $partners = $_p_model->alias('p')->join('LEFT JOIN team  t ON t.partner_id = p.id')->where($map)->group('p.id')->getField('p.id,p.title,p.username,count(t.id) AS tnum');
        // echo $_p_model->getLastSql();
        $pids = array_keys($partners);
        $_c_model = M('coupon');
        if (!empty($pids)) {
            $_map['partner_id'] = array('IN', $pids);
        }
        if ($time['st'] > 0 && $time['et'] > 0) {
            $_map['consume_time'] = array('between', array($time['st'], $time['et']));
        }
        $nums = $_c_model->where($_map)->group('partner_id')->getField('partner_id AS id, COUNT(id) AS count, SUM(CASE consume WHEN consume = \'Y\' THEN 1 else 0 END) AS num');

        foreach ($partners as $id => $partner) {
            if (array_key_exists($id, $nums)) {
                $partners[$id] = array_merge($partner, $nums[$id]);
            } else {
                $partners[$id] = array_merge($partner, array('count'=>0,'num'=>0));
            }
        }
        uasort($partners, function($a, $b){
            if ($a['num'] < $b['num']) {return 1;}
            if ($a['num'] > $b['num']) {return -1;}
            return 0;
        });
        
        //获取总金额
        $ajaxParam = array(
            'pid'   => implode(',', array_keys($nums)),
            'stime' => date('Y-m-d', $time['st']),
            'etime' => date('Y-m-d', $time['et'])
        );
        $this->assign('ajaxParam', json_encode($ajaxParam));
        $this->assign('list', $partners);
        $this->assign('searchTime', $time);
        $this->assign('searchValue', $searchValue);
        $this->display();
    }

    /**
     * ajax获取商家接待总额和利润
     */
    public function getReceptionMoney() {
        $this->_checkblank('pid');
        $pid  = I('get.pid');
        $time = $this->_getSearchTime();
        $map  = array(
            'partner_id'  => array('IN', $pid),
            'create_time' => array('between', array($time['st'], $time['et'])),
        );
        $model      = D('Partner');
        $totalMoney = $model->getPartnerMoney($map);
        $this->_writeDBErrorLog($totalMoney, $model);
        $con = array(
            'c.partner_id'   => array('IN', $pid),
            'c.consume'      => 'Y',
            'c.consume_time' => array('between', array($time['st'], $time['et']))
        );
        $totalProfit = $model->getPartnerProfit($con, 'c.partner_id');
        $this->_writeDBErrorLog($totalProfit, $model);

        $data = array(
            'status' => 1,
            'total'  => $totalMoney,
            'profit' => $totalProfit
        );
        $this->ajaxReturn($data);
    }

    /**
     * 接待详情
     */
    public function receptionDetail() {
        $this->_checkblank('id');
        $id   = I('get.id', 0, 'intval'); //商家id
        $time = $this->_getSearchTime();
        $where = array(
            'c.partner_id'    => $id,
            'c.consume'       => 'Y',
            'c.consume_time' => array('between', array($time['st'], $time['et']))
        );
        $model = D('Coupon');
        $state = I('get.state');
        if(empty($state)) {
            $count = $model->getReceptionCount($where);
            $this->_writeDBErrorLog($count, $model);
            $map['id'] = $id;
            $map['st'] = $time['st'];
            $map['et'] = $time['et'];
            $page  = $this->pages($count, 10, $map);
            $limit = $page->firstRow . ',' . $page->listRows;
            $show  = $page->show();
            $list  = $model->getReceptionDetail($where, $limit);
            $this->_writeDBErrorLog($list, $model);
            $this->assign('list', $list);
            $this->assign('pages', $show);
            $this->assign('count', $count);
            $this->display();
        } else {
            $list  = $model->getReceptionDetail($where, '');
            foreach($list as &$row) {
                $row['rate'] = sprintf('%.2f', $row['profit'] / ($row['num'] * $row['team_price']) * 100) . '%';
            }
            $keyMap = array(
                'title'      => '团单',
                'num'        => '验证数量',
                'team_price' => '购买价',
                'profit'     => '利润',
                'rate'       => '利润率'
            );
            download_xls($list, $keyMap, '接待量明细_'.$id.'_'.date('YmdHis'));
        }
    }

    /**
     * 订座信息
     */
    public function dingzuo() {
        $this->_checkblank('id');
        $dzModel      = D('Dingzuo');
        $partnerModel = D('Partner');
        $id           = I('get.id', 0, 'intval');

        $partner = $partnerModel->info($id, 'longlat,title,city_id,zone_id,station_id');
        if(empty($partner)) {
            $this->_writeDBErrorLog($partner, $partnerModel);    //记录错误日志
            $this->error('该商家不存在！');
        }
        $dingzuo = $dzModel->where(array('partner_id' => $id))->find();
        $this->_writeDBErrorLog($dingzuo, $dzModel);
        if(empty($dingzuo)) {
            $dingzuo['title']   = $partner['title'];
            $dingzuo['longlat'] = $partner['longlat'];
        } else {
            $dingzuo['longlat'] = $dingzuo['lat'] . ',' . $dingzuo['long'];
            $dingzuo['week']    = explode(',', $dingzuo['week']);
            $dingzuo['images']  = $dingzuo['image'];
        }
        //获取分类信息
        $this->_getCategoryList('class');
        $this->assign('vo', $dingzuo);
        $this->assign('id', $id);
        $this->display();
    }

    /**
     * 处理订座信息前置操作
     */
    public function _before_doDingzuo() {
        $longlat = I('post.longlat');
        if(!empty($longlat)) {
            $arr = explode(',', $longlat);
            $_POST['lat'] = $arr[0];
            $_POST['long'] = $arr[1];
        }
        $week = I('post.week');
        if(!empty($week)) {
            $_POST['week'] = implode(',', $week);
        }
        $action = I('post.action', '');
        if($action == 'add') {
            //处理默认城市问题
            $model               = D('Partner');
            $pid                 = I('partner_id', 0, 'intval');
            $partner             = $model->info($pid, 'id,zone_id,station_id');
            $this->_writeDBErrorLog($partner, $model);
            $_POST['zone_id']    = $partner['zone_id'];
            $_POST['station_id'] = $partner['station_id'];
            $_POST['city_id']    = $this->_getCityId();
            unset($_POST['id']);
        }
    }

    /**
     * 处理订座信息
     */
    public function doDingzuo() {
        $dzModel = D('Dingzuo');
        $action = I('post.action', '', 'trim');
        if($action == 'add') {
            $rs = $dzModel->insert();
            if(!$rs) {
                $this->_writeDBErrorLog($rs, $dzModel);
                $this->error('订座信息添加失败！');
            } else {
                $this->success('订座信息添加成功！');
            }
            exit;
        } else if ($action == 'edit') {
            $rs = $dzModel->update();
            if(!$rs) {
                $this->_writeDBErrorLog($rs, $dzModel);
                $this->error('订座信息修改失败！');
            } else {
                $this->success('订座信息修改成功！');
            }
            exit;
        }
        $this->error('非法访问！');
    }

    /**
     * 订座订单
     */
    public function dzOrder() {
        $searchParam = array(
            array('id', '', '', 'dz'),
            array('title', '', 'like', 'p'),
            array('username', '', 'like', 'dz'),
            array('mobile', '', '', 'dz'),
        );
        $where = $this->createSearchWhere($searchParam);
        $where['p.city_id']  = $this->_getCityId();

        $dzOrderModel = D('DzOrder');
        $total        = $dzOrderModel->getCount($where);
        $this->_writeDBErrorLog($total, $dzOrderModel);
        $page         = $this->pages($total, $this->reqnum);
        $limit        = $page->firstRow . ',' . $page->listRows;
        $list         = $dzOrderModel->getDzOrderList($where, '', $limit);
        $this->_writeDBErrorLog($list, $dzOrderModel);
        $this->assign('list', $list);

        $searchValue = $this->getSearchParam($searchParam);
        $this->assign('searchValue', $searchValue);
        $this->assign('pages', $page->show());
        $this->assign('countl', $total);
        $this->assign('stateInfo', array('Y' => '是', 'N' => '否'));
        $this->display();
    }

    /**
     * 分店列表
     */
    public function branchList() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model   = D('Partner');
        $curPartner = $model->info($id, 'title,fid,is_branch');
        if(empty($curPartner)) {
            $this->_writeDBErrorLog($curPartner, $model);
            $this->error('此商家不存在');
        }

        $where['city_id'] = $this->_getCityId();
        $where['fid'] = $id;

        $count = $model->getTotal($where);
        $this->_writeDBErrorLog($count, $model);
        $page  = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list  = $model->getList($where, 'id DESC', $limit, 'id,title,phone,mobile,city_id,group_id,db_id');

        $this->_writeDBErrorLog($list, $model);
        //分类列表
        $this->_getCategoryList('partner');
        //BD列表
        $this->_getBdList();
        $this->assign('list', $list);
        $this->assign('id', $id);
        $this->assign('curPartner', $curPartner);
        $this->assign('pages', $page->show());
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 上传图片
     */
    public function uploadImage() {
        $data = $this->upload('img', 'partner');
        if($data) {
            $info['state'] = 'SUCCESS';
            $info['url']   = $data[0]['newpath'] . '/' . $data[0]['savename'];
            $info['msg']   = '上传成功';
        } else {
            $info['state'] = 'ERROR';
            $info['msg'] = '上传失败';
        }
        ob_end_clean();
        $this->ajaxReturn($info);
    }
    /**
     * 监测数据合法性
     */
    public function check_team_data() {

        $Partner_post_data = $_POST;
        if (!isset($Partner_post_data['username']) || !trim($Partner_post_data['username'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '用户名不能为空'));
        }
        if (!isset($Partner_post_data['title']) || !trim($Partner_post_data['title'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '商户名不能为空'));
        }
        $where=array(
            'username'=>$Partner_post_data['username']
        );
        $res=M('partner')->where($where)->field('username')->find();
        if($res){
            $this->ajaxReturn(array('code' => -1, 'error' => '登录名重复！请从新起名'));
        }
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 优惠买单修改
     */
    public function discountEdit() {
        $id = intval(I('get.id'));
        if (empty($id)) {
            $this->error('参数错误');
        }
        $model = D(CONTROLLER_NAME);
        $pk = $model->getPk();
        $partner = $model->field(array('id','title','is_discount'))->where($pk . "=" . $id)->find();
        if (empty($partner)) {
            $this->error('信息不存在或已被删除！');
        }
        $model = D('Discount');
        $discount = $model->where(array('partner_id'=>$partner['id']))->find();
        if (is_array($discount)) {
            unset($discount['id']);
        } else {
            // 默认值
            $discount = array(
                'partner_id' => $partner['id'],
                'ratio'      => 0.95,
                'aratio'     => 0.05,
                'start_time' => strtotime(date('Y-m-d',NOW_TIME).' 09:00'),
                'end_time'   => strtotime(date('Y-m-d',NOW_TIME + 86400 * 180).' 21:00'),
            );
        }
        $discount = array_merge($partner,$discount);
        $this->assign('discount', $discount);
        $this->display();
    }

    /**
     * 优惠买单保存
     */
    public function discountUpdate() {
        $id = I('post.id','','intval');
        if (empty($id)) {
            $this->error('信息不存在或已被删除');
        }

        $is_discount = I('post.is_discount','','intval');
        $model = D('Partner');
        $map['id'] = $id;
        $partner = $model->where($map)->find();
        // 更新优惠买单标识
        if ($partner['is_discount'] != $is_discount) {
            $result = $model->where($map)->setField('is_discount',$is_discount);
            if (!$result) {
                $this->error($model->getDbError());
            }
        }
        if ($is_discount == 0) {
            $this->success('优惠买单已关闭！');
            exit;
        }

        $ratio = sprintf("%.2f", I('post.ratio', '', 'doubleval'));
        if ($ratio > 1.00 || $ratio <= 0.00) {
            $this->error('折扣率请设置为大于0.00小于1.00的值');
        }
        $aratio = sprintf("%.2f", I('post.aratio', '', 'doubleval'));
        if ($aratio > 1.00 || $aratio <= 0.00) {
            $this->error('提成率请设置为大于0.00小于1.00的值');
        }

        $start_time = I('post.start_time', '', 'trim');
        $end_time   = I('post.end_time', '', 'trim');
        $now_time   = date('Y-m-d H:i:s', time());

        list($now_day,$now_hour) = explode(' ',$now_time);
        list($start_day,$start_hour) = explode(' ',$start_time);
        list($end_day,$end_hour) = explode(' ',$end_time);
        if ($end_day < $start_day) {
            $this->error('结束日期不能早于开始日期');
        }
        if ($end_day < $now_day) {
            $this->error('结束日期不能早于当前日期');
        }
        if ($end_hour < $start_hour) {
            $this->error('当天结束时间不能早于当天开始时间');
        }

        $_POST['ratio']  = $ratio;
        $_POST['aratio'] = $aratio;
        $_POST['start_time'] = strtotime($start_time);
        $_POST['end_time']   = strtotime($end_time);
        $_POST['partner_id'] = $id;

        // 更新数据
        $model = D('Discount');
        unset($map['id']);
        $map['partner_id'] = $id;
        $discount = $model->where($map)->find();
        $pk = $model->getPk();
        unset($_POST[$pk]);
        if (!empty($discount) && $discount[$pk]) {
            $_POST[$pk] = $discount[$pk];
        }

        if ($model->create()) {
            if ($_POST[$pk]) {
                $result = $model->save();
            } else {
                $result = $model->add();
            }
            if ($result !== false) {
                $this->success('修改数据成功！');
                exit;
            } else {
                $this->errmsg = $model->getDbError();
            }
        } else {
            $this->errmsg = $model->getError();
        }
        $this->error($this->errmsg);
    }
}