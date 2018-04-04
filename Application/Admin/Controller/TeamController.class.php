<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-06-08
 * Time: 13:37
 */

namespace Admin\Controller;

class TeamController extends CommonController {

    protected $_searchWhere;

    /**
     * 在线团单
     */
    public function index() {
        $this->_getSearchWhere();
        $this->_searchWhere['team_type'][] = array('NEQ', 'unaudited');
        $this->_searchWhere['end_time'][] = array('GT', strtotime(date('Y-m-d')));
        $this->_getTeamList($this->_searchWhere);
        $this->display();
    }

    /**
     * 即将下线
     */
    public function beOffline() {
        $this->_getSearchWhere();
        $this->_searchWhere['team_type'][] = array('NEQ', 'unaudited');
        $this->_searchWhere['end_time'][] = array('GT', strtotime(date('Y-m-d')));
        $this->_searchWhere['end_time'][] = array('ELT', strtotime(date('Y-m-d', strtotime('+7 days'))));
        $this->_getTeamList($this->_searchWhere);
        $this->display();
    }

    /**
     * 待审核
     */
    public function beCheck() {
        $this->_getSearchWhere();
        $this->_searchWhere['team_type'] = 'unaudited';
        $this->_searchWhere['end_time'] = array('GT', time());
        $this->_getTeamList($this->_searchWhere);
        $this->display();
    }

    /**
     * 过期
     */
    public function expired() {
        $this->_getSearchWhere();
        $this->_searchWhere['team_type'][] = array('NEQ', 'unaudited');
        //TODO 在expire_time上面添加索引
        $this->_searchWhere['expire_time'] = array('LT', time());
        $this->_getTeamList($this->_searchWhere);
        $this->display();
    }

    /**
     * 下线
     */
    public function offLine() {
        $this->_getSearchWhere();
        $this->_searchWhere['team_type'][] = array('NEQ', 'unaudited');
        $act = I('get.act');
        if ($act == 'today') {
            $this->_searchWhere['end_time'][] = strtotime(date('Y-m-d'));
        } else {
            $this->_searchWhere['end_time'][] = array('LT', time());
        }
        $this->_getTeamList($this->_searchWhere, 'end_time desc, expire_time desc');
        $this->display();
    }

    /**
     *   获取活动列表
     */
    public function getActivityList() {
        $city_id = I('post.city_id', 0, 'intval');
        if (!$city_id){
            $this->ajaxReturn(array('code'=>-1,'error'=>'城市id不能为空！'));
        }
           
        $admanage = D('Admanage');
        $data = $admanage->getActivitiesList($city_id,false);
        $this->ajaxReturn(array('code'=>0,'data'=>$data));
    }

    /**
     * 获取团单列表
     * @param $where
     */
    protected function _getTeamList($where, $order = 'id DESC') {
        $model = D('Team');
        $total = $model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = $model->getList($where, $order, $limit);

        $orders=M('order');
        //$buy = M('order')->field("count(id) as buy_count,sum(quantity) as buy_num,sum(money) as money,sum(credit) as credit, sum(origin) as origin")->where(array('team_id' => $info['id'],'_string'=>"state='pay' or rstate='berefund'"))->find();
        foreach ($list as &$row) {
            $buy = $orders->field("count(id) as buy_count,sum(quantity) as buy_num,sum(money) as money,sum(credit) as credit, sum(origin) as origin")->where(array('team_id' => $row['id'],'_string'=>"state='pay' or rstate='berefund'"))->find();
            $row['now_number']=isset($buy['buy_num'])?$buy['buy_num']:0;
        }
        $partner = $this->_getTeamPartner($list);
        $user = $this->_getTeamUser($list);
        $this->assign('list', $list);
        $this->assign('partnerList', $partner);
        $this->assign('userList', $user);
        $this->assign('pages', $page->show());
        $this->_getCategoryList('city');
        $this->_getCategoryList('group');
    }

    /**
     * 获取搜索条件
     */
    protected function _getSearchWhere() {
        $where = array(
            array('id', ''),
            array('group_id', ''),
            array('city_id', ''),
            array('product', '', 'like')
        );
        $map = $this->createSearchWhere($where);
        $searchValue = $this->getSearchParam($where);
        $stime = I('get.begin_time');
        if (!empty($stime)) {
            $map['begin_time'][] = array('EGT', strtotime($stime));
            $searchValue['begin_time'] = $stime;
        }
        $etime = I('get.end_time');
        if (!empty($etime)) {
            $map['end_time'][] = array('ELT', strtotime($etime));
            $searchValue['end_time'] = $etime;
        }
        $teamType = I('get.team_type');
        if (!empty($teamType)) {
            $map['team_type'][] = array('eq', $teamType);
            $searchValue['team_type'] = $teamType;
        }
        $sub_id = I('get.sub_id','','trim');
        if (trim($sub_id)) {
            $map['sub_id'] = $sub_id ;
        }
        $this->assign('searchValue', $searchValue);
        $this->_searchWhere = $map;
    }

    /**
     * 获取团单对应商家
     * @param $list
     * @return mixed
     */
    protected function _getTeamPartner($list) {
        if (empty($list))
            return array();
        $partnerId = array();
        foreach ($list as $row) {
            $partnerId[] = $row['partner_id'];
        }
        $map = array(
            'id' => array('IN', array_unique($partnerId))
        );
        $data = M('Partner')->where($map)->getField('id,title', true);
        return $data;
    }

    /**
     * 获取团单对应编辑
     * @param $list
     * @return mixed
     */
    protected function _getTeamUser($list) {
        if (empty($list))
            return array();
        $userId = array();
        foreach ($list as $row) {
            $userId[] = $row['user_id'];
        }
        $map = array(
            'id' => array('IN', array_unique($userId))
        );
        $data = M('User')->where($map)->getField('id,realname', true);
        return $data;
    }

    /**
     * 团单详情
     */
    public function detail() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model = D('Team');
        $team = $model->getTeamDetail($id);
        if (empty($team)) {
            $this->error('团单不存在');
        }
        $team['attr'] = $model->getTeamAttrItem($team);
        $team['cout']=$team['buy_num']+$team['pre_number'];//2016.4.13加
        $this->assign('data', $team);
        $this->_getCategoryList('city');
        $this->display();
    }

    /**
     * 上线
     */
    public function upLine() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model = D('Team');
        $team = $model->info($id, 'id,begin_time,expire_time');
        if (empty($team)) {
            $this->error('团单不存在');
        }
        $begin_time = $team['begin_time'];
        $end_time = (time() + 86400 * 2) < $team['expire_time'] ? (time() + 86400 * 2) : $team['expire_time'];

        $data = array(
            'begin_time' => $begin_time,
            'end_time' => $end_time
        );
        $res = $model->where('id=' . $id)->save($data);
        if ($res) {
            $this->addOperationLogs("操作：团单上线,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},团单id:{$id}");
            $this->success('上线成功');
        } else {
            $this->error('上线失败');
        }
    }

    /**
     * 下线
     */
    public function downLine() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model = D('Team');
        $team = $model->info($id, 'id,now_number');
        $data = array(
            'end_time' => strtotime(date('Y-m-d')),
                //'min_number' => $team['now_number'] + 1
        );
        $res = $model->where('id=' . $id)->save($data);
        if ($res) {
            $opData = array(
                'team_id' => $id,
                'admin_id' => $this->user['id'],
                'downtime' => time()
            );
            M('TeamDown')->add($opData);
            $this->addOperationLogs("操作：团单下线,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},团单id:{$id}");
            $this->success('下线成功');
        } else {
            $this->error('下线失败');
        }
    }

    /**
     * 设为主推
     */
    public function toMain() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model = D('Team');
        $res = $model->where('id=' . $id)->setField('conduser', 'Y');
        if ($res) {
            $this->addOperationLogs("操作：团单主推设置,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},团单id:{$id}");
            $this->success('主推设置成功');
        } else {
            $this->error('主推设置失败');
        }
    }

    /**
     * 取消主推
     */
    public function unMain() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model = D('Team');
        $res = $model->where('id=' . $id)->setField('conduser', 'N');
        if ($res) {
            $this->addOperationLogs("操作：团单主推取消,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},团单id:{$id}");
            $this->success('主推取消成功');
        } else {
            $this->error('主推取消失败');
        }
    }

    /**
     * 审核
     */
    public function toCheck() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model = D('Team');
        $vo = $model->info($id, 'id,team_type');
        if (empty($vo)) {
            $this->error('该团单不存在');
        }
        $res = $model->where('id=' . $id)->setField('team_type', 'normal');
        if ($res) {
            $this->addOperationLogs("操作：团单审核,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},团单id:{$id}");
            $this->success('审核成功');
        } else {
            $this->error('审核失败');
        }
    }

    /**
     * 下载
     */
    public function downloadXls() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $coupon = M('Coupon')->field('id,consume,user_id,order_id,consume_time')->where('team_id=' . $id)->order('create_time ASC')->select();
        if (empty($coupon)) {
            $this->error('该团单暂无青团券');
        }
        $userId = $orderId = array();
        foreach ($coupon as $row) {
            $userId[] = $row['user_id'];
            $orderId[] = $row['order_id'];
        }
        if (!empty($orderId)) {
            $map = array(
                'id' => array('IN', array_unique($orderId))
            );
            $order = D('Order')->where($map)->getField('id,mobile,buy_id,remark');
        }
        if (!empty($userId)) {
            $map = array(
                'id' => array('IN', array_unique($userId))
            );
            $user = D('User')->where($map)->getField('id,mobile,username,realname,email');
        }
        $key = array(
            'buy_id' => '支付序号',
            'username' => '用户名',
            'email' => '用户邮箱',
            'realname' => '姓名',
            'mobile' => '手机号码',
            'condbuy' => '选项',
            'id' => "青团券编号",
            'consume' => "是否消费",
            'cmobile' => '消费手机',
            'date' => '生成时间',
            'consumedate' => '消费时间',
            'remark' => '备注',
        );

        $data = array();
        foreach ($coupon as $row) {
            $o = $order[$row['order_id']];
            $u = $user[$row['user_id']];
            $mobile = $o['mobile'] ? $o['mobile'] : $u['mobile'];
            $consumeDate = $row['consume'] == 'Y' ? date('Y-m-d', $row['consume_time']) : '';
            $date = $row['create_time'] ? date('Y-m-d', $row['create_time']) : '';
            $data[] = array(
                'buy_id' => $o['buy_id'],
                'username' => $u['username'],
                'email' => $u['email'],
                'realname' => $u['realname'],
                'mobile' => $mobile,
                'condbuy' => $o['condbuy'],
                'id' => (string) $row['id'],
                'consume' => $row['consume'],
                'date' => $date,
                'consumedate' => $consumeDate,
                'remark' => $o['remark'],
            );
        }
        download_xls($data, $key, 'team-' . $id);
    }

    /**
     * 获取子分类
     */
    public function getSubCate() {
        $id = I('get.id', 0, 'intval');
        $tid = I('get.tid', 0, 'intval');
        if ($id) {
            if ($tid) {
                $team = D('Team')->info($tid);
            }
            $data = $this->_getCategoryList('group', array('fid' => $id));
            $str = '';
            foreach ($data as $row) {
                if (isset($team) && $team['sub_id'] == $row['id']) {
                    $str .= '<option value="' . $row['id'] . '" selected>' . $row['name'] . '</option>';
                } else {
                    $str .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                }
            }
            $this->ajaxReturn(array('html' => $str));
        } else {
            $this->ajaxReturn(array('html' => ''));
        }
    }

    /**
     * 上传项目图片
     */
    public function uploadImg() {
        $type = I('get.type', 'team', 'strval');
        if ($type == 'teaminfo') {
            $url = C('IMG_PREFIX') . '/static/';
        } elseif ($type == 'team') {
            $url = '';
        } elseif ($type == 'Admanage') {
            $url = '/static/';
        } else {
            $url = C('IMG_PREFIX') . '/static/';
        }
        $data = $this->upload('img', $type);        
        if ($data) {
            $res = array(
                "state" => "SUCCESS",
                "url" => $url . $data[0]['newpath'] . '/' . $data[0]['savename'],
                "title" => $data[0]['savename'],
                "error" => 0,
            );
        } else {
            $error = '上传失败';
            if(isset($this->error) && trim($this->error)){
                $error = $this->error;
            }
            $res = array(
                "state" => $error,
                "error" => 1,
                "message" => $error,
            );
        }
        ob_clean();
        die(json_encode($res));
    }

    /**
     * 商家列表
     */
    public function partner() {
        $searchParam = array(
            array('id', ''),
            array('title', '', 'like')
        );
        $where = $this->createSearchWhere($searchParam);
        $where['city_id'] = I('get.city_id');
        $model = D('Partner');
        $count = $model->getTotal($where);
        $this->_writeDBErrorLog($count, $model);
        $page = $this->pages($count, 15);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = $model->getList($where, 'id DESC', $limit, 'id,title');
        $this->_writeDBErrorLog($list, $model);
        $searchValue = $this->getSearchParam($searchParam);
        $this->assign('list', $list);
        $this->assign('searchValue', $searchValue);
        $this->assign('pages', $page->show());
        $this->assign('cityId', I('get.city_id'));
        $this->display();
    }

    /**
     * 新增团单
     */
    public function add() {
        
        $is_open_common_operation = C('IS_OPEN_COMMON_OPERATION');
        if($is_open_common_operation){
            $url = $this->get_common_operation_url('Team/add.html',$this->uid);
            if($url){
                redirect($url);
                exit;
            }
        }
        
        $this->_getCategoryList('group', array('fid' => 0));
        $this->_getCategoryList('city');
        $this->display();
    }

    /**
     * 新增前置操作
     */
    public function _before_add() {
        $tmpData = array(
            'notice' => '<ul>
							<li><span style="line-height:1.5;">青团券使用时间：</span></li>
							<li><span style="line-height:1.5;">预约须知：</span></li>
							<li><span style="line-height:1.5;">青团券是否可叠加：</span></li>
							<li><span style="line-height:1.5;">消费后评价获得积分；</span></li>
							<li><span style="line-height:1.5;">凭青团券到店消费不可同时享受店内其他优惠；</span></li>
						</ul>',
            'summary' => '<table style="width:100%;" cellpadding="2" cellspacing="0" border="1" bordercolor="#999999">
				<tbody>
					<tr>
						<td style="text-align:center;background-color:#f0f0f0;"><strong>套餐内容</strong></td>
						<td style="text-align:center;background-color:#f0f0f0;"><strong>单价</strong></td>
						<td style="text-align:center;background-color:#f0f0f0;"><strong>数量</strong></td>
						<td style="text-align:center;background-color:#f0f0f0;"><strong>小计</strong></td>
					</tr>
					<tr>
						<td><br /></td><td><br /></td><td><br /></td><td><br /></td>
					</tr>
					<tr>
						<td><br /></td><td><br /></td><td><br /></td><td><br /></td>
					</tr>
					<tr>
						<td colspan="4" align="right" valign="middle">
								<div style="text-align:right;font-size:14px;" height="35"><span style="line-height:1.5;"></span>价值：<strong>***元</strong>&nbsp;
								青团价：<span style="line-height:1.5;"><strong>***元</strong></span><span style="line-height:1.5;"></span><span style="line-height:1.5;"></span>
								</div>
						</td>
					</tr>
				</tbody>
			</table>',
            'begin_time' => time() + 86400,
            'end_time' => time() + 10 * 86400,
            'expire_time' => time() + 30 * 86400,
        );
        $this->assign('tmpData', $tmpData);
    }

    /**
     * 写入操作
     */
    public function insert() {
        $_POST['user_id'] = $this->_getUserId();
        $_POST['now_number'] = I('post.pre_number') ? I('post.pre_number') : 0;
        $_POST['view_count_day'] = 0;
        $_POST['view_time'] = 0;
        $_POST['ch360_id'] = 0;
        $sel3 = I('post.sel3', '', 'trim');
        if ($sel3) {
            $pinyin = new \Common\Org\pinyin();
            $_POST['sel1'] = $pinyin->str2py($sel3, 'all');
            $_POST['sel2'] = $pinyin->str2py($sel3, 'other');
        } else {
            $_POST['sel1'] = '';
            $_POST['sel2'] = '';
        }

        if (I('post.is_optional_model') == 'Y') {
            if (count(I('post.attr_item')) <= 0) {
                $this->error('请添加属性');
                exit();
            }
        }

        // 处理邮购类型
        $team_type = I('post.team_type', '', 'trim');
        if ($team_type == 'goods') {
            $_POST['group_id'] = '16';
            $_POST['delivery'] = 'express';
        }else if($team_type == 'cloud_shopping'){
            $_POST['allowrefund'] = 'N';
            $_POST['product'] = '一元云购';
            $_POST['max_number'] = intval(ternary($_POST['team_price'], 0));
            if(isset($_POST['max_periods_number']) && intval($_POST['max_periods_number'])<1){
                $this->error('期数必须大于0');
            }
        }

        $Model = D('Team');
        $model = M();
        $model->startTrans();
        $rs = $Model->insert();
        if ($rs === false) {
            //TODO 记录错误日志
            $model->rollback();
            $this->_writeDBErrorLog($rs, $Model, 'admin');
            $error = $Model->getErrorInfo();
            $this->error($error['info']);
        } else {
            $data = $Model->create();
            $data['team_id'] = $rs;
            $data = $this->_checkData($data);
            //TODO 新表结构操作
            $teamInfo_res = $this->_addTeamInfo($data);
            //TODO 处理邮购商品属性
            $goods_res = $this->_addGoodsTeamAttr($rs);
            if ($teamInfo_res && $goods_res) {
                $model->commit();
                $this->addOperationLogs("操作：新增团单,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},团单id:{$rs}");
                $this->success('新建成功！', U('Team/index'));
            } else {
                $model->rollback();
                //$this->redirect_message(U("Team/add"), array('error' => base64_encode('新建失败!')));
                $this->error('新建失败！');
            }
        }
    }

    /**
     * 新增新表team_info数据
     * @param $data
     * @return bool|mixed
     */
    protected function _addTeamInfo($data) {
        //TODO 待添加数据库后完善
        $Model = M('team_info');
        $data['state'] = 'none';
        $res = $Model->add($data);
        if ($res === false) {
            $this->_writeDBErrorLog($res, $Model);
            return false;
        }
        return $res;
    }

    /**
     * 处理邮购产品的属性
     * @param $id
     * @return bool
     */
    protected function _addGoodsTeamAttr($id) {
        $isAttr = I('post.is_optional_model', 'N', 'trim');
        $attrItem = I('post.attr_item');
        if ($isAttr == 'Y' && count($attrItem) > 0) {
            $attrItem = I('post.attr_item');
            $attrNum = I('post.attr_num');
            if (D('Team')->addTeamAttrItem($attrItem, $attrNum, $id)) {
                $total = array_sum($attrNum);
                if ($total != I('post.max_number', 0, 'intval')) {
                    M('team')->where('id=' . $id)->setField('max_number', $total);
                }
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * 编辑团单
     */
    public function edit() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        
        // 是否跳转到公共操作部分
        $is_open_common_operation = C('IS_OPEN_COMMON_OPERATION');
        if($is_open_common_operation){
            $url = $this->get_common_operation_url("Team/edit/id/{$id}.html",$this->uid);
            if($url){
                redirect($url);
                exit;
            }
        }
        
        $model = D('Team');
        $info = $model->info($id);
        if (empty($info)) {
            $this->redirect_message(U("Team/index"), array('error' => base64_encode('该团单不存在！')));
            exit();
        }
        if ($info['image']) {
            $info['image'] = getImagePath($info['image']);
        }
        $info['begin_time'] = $info['begin_time'] ? date('Y-m-d H:i:s', $info['begin_time']) : '';
        $info['end_time'] = $info['end_time'] ? date('Y-m-d H:i:s', $info['end_time']) : '';
        $info['expire_time'] = $info['expire_time'] ? date('Y-m-d H:i:s', $info['expire_time']) : '';
        $info['promotion'] = unserialize($info['promotion']);
        $info['partner_name'] = M('Partner')->where('id=' . $info['partner_id'])->getField('title');
        $info['attr'] = $model->getTeamAttrItem($info);
        $this->_getCategoryList('group', array('fid' => 0));
        $this->_getCategoryList('city');
        $this->assign('vo', $info);
        $this->display();
    }

    /**
     * 更新团单
     */
    public function update() {
        $id = I('post.id', 0, 'intval');
        $_POST['user_id'] = $this->_getUserId();
        unset($_POST['partner_id']);
        $sel3 = I('post.sel3', '', 'trim');

        if (!I('post.is_optional_model')) {
            $_POST['is_optional_model'] = 'N';
        } else {
            if (count(I('post.attr_item')) <= 0) {
                $this->redirect_message(U("Team/edit", array('id' => $id)), array('error' => base64_encode('请添加属性')));
                exit();
            }
        }

        if ($sel3) {
            $pinyin = new \Common\Org\pinyin();
            $_POST['sel1'] = $pinyin->str2py($sel3, 'all');
            $_POST['sel2'] = $pinyin->str2py($sel3, 'other');
        } else {
            $_POST['sel1'] = '';
            $_POST['sel2'] = '';
        }

        $teamModel = D('Team');
        $team = $teamModel->info($id);
        $pre_num = I('post.pre_number');
        $max_num = I('post.max_number');
        if ($pre_num) {
            if ($team['pre_number'] > $pre_num) {
                $this->redirect_message(U("Team/edit", array('id' => $id)), array('error' => base64_encode('虚拟购买数必须大于' . $team['pre_number'])));
                exit();
            }
            if ($max_num > 0 && $max_num < ($pre_num - $team['pre_number'] + $team['now_number'])) {
                $this->redirect_message(U("Team/edit", array('id' => $id)), array('error' => base64_encode('虚拟购买数不能大于' . ($max_num - $team['now_number']))));
                exit();
            }
            $_POST['now_number'] = $pre_num - $team['pre_number'] + $team['now_number'];
        }

        // 处理邮购类型
        $team_type = I('post.team_type', '', 'trim');
        if ($team_type == 'goods') {
            $_POST['group_id'] = '16';
            $_POST['delivery'] = 'express';
        }else if($team_type == 'cloud_shopping'){
            $_POST['allowrefund'] = 'N';
            $_POST['product'] = '一元云购';
            unset($_POST['team_price']);
            unset($_POST['ucaii_price']);
            if(isset($_POST['team_price']) && $_POST['team_price']>0){
                $_POST['max_number'] = intval(ternary($_POST['team_price'], 0));
            }
            
            if(isset($_POST['max_periods_number']) && intval($_POST['max_periods_number'])< intval(ternary($team['now_periods_number'],1))){
                $now_periods_number = intval(ternary($team['now_periods_number'],1));
                $this->error("改项目已经云购到第{$now_periods_number}期，最大期数不能小于{$now_periods_number}");
            }
        }

        $attrList = array();
        if(I('post.is_optional_model') == 'Y') {
            $attrList = $teamModel->getTeamAttrItem(I('post.'));
        }

        $model = M();
        $model->startTrans();
        $rs = $teamModel->update();
        if ($rs === false) {
            $model->rollback();
            $this->_writeDBErrorLog($rs, $teamModel);
            $error = $teamModel->getErrorInfo();
            $this->redirect_message(U("Team/edit", array('id' => $id)), array('error' => base64_encode($error['info'])));
            exit();
        } else {
            $other_res = $this->_afterTeamEdit($teamModel->create(), $team, $attrList);
            if ($other_res === false) {
                $model->rollback();
                $this->redirect_message(U("Team/edit", array('id' => $id)), array('error' => base64_encode('更新失败!')));
                exit();
            } else {
                $model->commit();
            }
            $this->addOperationLogs("操作：编辑团单,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},团单id:{$rs}");
            $this->_cleanTeamCache($id);
            $this->redirect_message(U("Team/index"), array('success' => base64_encode('更新成功！')));
        }
    }

    /**
     * 清除团单缓存
     * @param $id
     */
    protected function _cleanTeamCache($id) {
        S('team_' . $id, null);
        @unlink('./Html/team-' . $id . '.html');
    }

    /**
     * 编辑团单后置操作
     * @param $data
     * @param $team
     * @return bool
     */
    protected function _afterTeamEdit($data, $team, $attrList = array()) {
        $Model = D('Team');
        $id = $data['id'];
        if (isset($data['id'])) {
            unset($data['id']);
        }
        $this->_updateTeamInfo($id, $data);
        if ($data['expire_time'] != $team['expire_time']) {
            $res = $this->_updateCoupon($data, $id);
            if ($res === false) {
                return false;
            }
        }
        if ($data['allowrefund'] != $team['allowrefund']) {
            $order_data['allowrefund'] = $data['allowrefund'];
            $order_res = $this->_updateOrder($order_data, $id);
            if ($order_res === false) {
                return false;
            }
        }
        if (!$this->_updateGoodsTeamAttr($id, $attrList)) {
            return false;
        }
        return true;
    }

    /**
     *  新增新表team_info数据
     */
    protected function _updateTeamInfo($id, $data = array()) {
        //TODO 待添加数据库后完善
        if(!$data) {
            $data = I('post.');
        }
        $Model = M('team_info');
        if ($id) {
            $data['address'] = ternary($data['address'], '');
            $res = $Model->where("team_id={$id}")->save($data);
            if ($res === false) {
                $this->_writeDBErrorLog($res, $Model);
            }
        }
    }

    /**
     * 修改Coupon的过期时间以及商家
     * @param $data
     * @param $id
     * @return bool
     */
    protected function _updateCoupon($data, $id) {
        $Model = M('coupon');
        $where['team_id'] = $id;
        $res = $Model->where($where)->save(array('expire_time' => $data['expire_time']));
        if ($res === false) {
            $this->_writeDBErrorLog($res, $Model);
        }
        return $res;
    }

    /**
     * 修改Order是否可退款
     * @param $data
     * @param $id
     * @return bool
     */
    protected function _updateOrder($data, $id) {
        $Model = M('order');
        $where['team_id'] = $id;
        $res = $Model->where($where)->save(array('allowrefund' => $data['allowrefund']));
        if ($res === false) {
            $this->_writeDBErrorLog($res, $Model);
        }
        return $res;
    }

    /**
     * 更新团单属性
     * @param $id
     * @return boolean
     */
    protected function _updateGoodsTeamAttr($id, $attrList) {
        $data = I('post.');
        $model = D('Team');
        if ($data['is_optional_model'] == 'Y') {
            $attrId = I('post.attr_id');
            $attrItem = I('post.attr_item');
            $attrNum = I('post.attr_num');
            $res = $model->updateTeamAttrItem($data, $attrId, $attrItem, $attrNum, $attrList);
            $total = array_sum($attrNum);
            if ($total != I('post.max_number', 0, 'intval')) {
                $model->where('id=' . $id)->setField('max_number', $total);
            }
        } else {
            $res = true;
            if ($data['old_is_optional_model'] == 'Y') {
                $res = $model->delTeamAttrItem($id);
            }
        }
        return $res;
    }

    /**
     * 删除团单
     */
    public function del() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model = D('Team');
        $vo = $model->info($id, 'id,is_optional_model');
        if (empty($vo)) {
            $this->_writeDBErrorLog($vo, $model, 'admin');
            $this->error('该团单不存在');
        }
        $map = array(
            'team_id' => $id
        );
        $map['_string'] = "(state='pay' OR (state='unpay' && rstate='berefund'))";
        $num = D('Order')->getTotal($map);
        if (empty($num)) {
            //删除团单team/team_info/order表中的信息
            M('Team')->where('id=' . $id)->delete();
            M('TeamInfo')->where('team_id=' . $id)->delete();
            M('Order')->where('team_id=' . $id)->delete();
            if ($vo['is_optional_model'] == 'Y') {
                //删除attr信息
                M('team_attribute')->where('team_id=' . $id)->delete();
            }
            $this->addOperationLogs("操作：删除团单,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},团单id:{$id}");
            $this->success('删除成功');
        } else {
            $this->error('本团购包含付款或退款订单，不能删除');
        }
    }

}
