<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-06-08
 * Time: 13:37
 */

namespace Common_operation\Controller;

class TeamController extends CommonController {

    /**
     *   获取活动列表
     */
    public function getActivityList() {
        $city_id = I('post.city_id', 0, 'intval');
        if (!$city_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '城市id不能为空！'));
        }

        $admanage = D('Admanage');
        $data = $admanage->getActivitiesList($city_id, false);
        $this->ajaxReturn(array('code' => 0, 'data' => $data));
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
            if (isset($this->error) && trim($this->error)) {
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
        $this->_getCategoryList('group', array('fid' => 0));
        $this->_getCategoryList('city');
        $this->display();
    }

    /**
     * 新增前置操作
     */
    public function _before_add() {
        $tmpData = array(
            'notice' => '<span style="line-height:1.5;">青团券使用时间：</span><br />
							<span style="line-height:1.5;">预约须知：</span><br />
							<span style="line-height:1.5;">青团券是否可叠加：</span><br />
							<span style="line-height:1.5;">消费后评价获得积分；</span><br />
							<span style="line-height:1.5;">凭青团券到店消费不可同时享受店内其他优惠；</span><br />
						<br />',
            'summary' => '<table style="width:100%;border:#e1e1e1 1px solid" cellpadding="2" cellspacing="0" border="1"  >
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
                $this->redirect_message(U("Team/add"), array('error' => base64_encode('请添加属性')));
                exit();
            }
        }

        // 根据操作员身份 设置 固定数据不可以修改
        $this->set_post_data();

        // 处理邮购类型
        $team_type = I('post.team_type', '', 'trim');
        if ($team_type == 'goods') {
            $_POST['group_id'] = '16';
            $_POST['delivery'] = 'express';
        } else if ($team_type == 'cloud_shopping') {
            $_POST['allowrefund'] = 'N';
            $_POST['product'] = '一元云购';
            $_POST['max_number'] = intval(ternary($_POST['team_price'], 0));
            if (isset($_POST['max_periods_number']) && intval($_POST['max_periods_number']) < 1) {
                $this->redirect_message(U("Team/add"), array('error' => base64_encode('期数必须大于0')));
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
            $this->redirect_message(U("Team/add"), array('error' => base64_encode($error['info'])));
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
                $this->addOperationLogs("操作：新增团单,管理员id:{$this->operation_info['id']},管理员名称:{$this->operation_info['username']},平台来源:{$this->operation_info['plat']}[{$this->operation_info['plat_name']}],团单id:{$rs}",$this->operation_info['id'],$this->operation_info['username']);
                $this->redirect_message(U("Team/add"), array('success' => base64_encode('新建成功！')));
            } else {
                $model->rollback();
                $this->redirect_message(U("Team/add"), array('error' => base64_encode('新建失败!')));
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
        $access_token = I('post.access_token','', 'trim');
        $_POST['user_id'] = $this->_getUserId();
        // unset($_POST['partner_id']);
        $sel3 = I('post.sel3', '', 'trim');

        if (!I('post.is_optional_model')) {
            $_POST['is_optional_model'] = 'N';
        } else {
            if (count(I('post.attr_item')) <= 0) {
                $this->redirect_message(U("Team/edit", array('id' => $id,'access_token'=>$access_token)), array('error' => base64_encode('请添加属性')));
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
                $this->redirect_message(U("Team/edit", array('id' => $id,'access_token'=>$access_token)), array('error' => base64_encode('虚拟购买数不能大于' . ($max_num - $team['now_number']))));
                exit();
            }
            $_POST['now_number'] = $pre_num - $team['pre_number'] + $team['now_number'];
        }

        // 处理邮购类型
        $team_type = I('post.team_type', '', 'trim');
        if ($team_type == 'goods') {
            $_POST['group_id'] = '16';
            $_POST['delivery'] = 'express';
        } else if ($team_type == 'cloud_shopping') {
            $_POST['allowrefund'] = 'N';
            $_POST['product'] = '一元云购';
            unset($_POST['team_price']);
            unset($_POST['ucaii_price']);
            if (isset($_POST['team_price']) && $_POST['team_price'] > 0) {
                $_POST['max_number'] = intval(ternary($_POST['team_price'], 0));
            }

            if (isset($_POST['max_periods_number']) && intval($_POST['max_periods_number']) < intval(ternary($team['now_periods_number'], 1))) {
                $now_periods_number = intval(ternary($team['now_periods_number'], 1));
                $this->error("改项目已经云购到第{$now_periods_number}期，最大期数不能小于{$now_periods_number}");
            }
        }

        $attrList = array();
        if (I('post.is_optional_model') == 'Y') {
            $attrList = $teamModel->getTeamAttrItem(I('post.'));
        }

        // 根据操作员身份 设置 固定数据不可以修改
        $this->set_post_data(true);

        $model = M();
        $model->startTrans();
        $rs = $teamModel->update();
        if ($rs === false) {
            $model->rollback();
            $this->_writeDBErrorLog($rs, $teamModel);
            $error = $teamModel->getErrorInfo();
            $this->redirect_message(U("Team/edit", array('id' => $id,'access_token'=>$access_token)), array('error' => base64_encode($error['info'])));
            exit();
        } else {
            $other_res = $this->_afterTeamEdit($teamModel->create(), $team, $attrList);
            if ($other_res === false) {
                $model->rollback();
                $this->redirect_message(U("Team/edit", array('id' => $id,'access_token'=>$access_token)), array('error' => base64_encode('更新失败!')));
                exit();
            } else {
                $model->commit();
            }
            $this->addOperationLogs("操作：编辑团单,管理员id:{$this->operation_info['id']},管理员名称:{$this->operation_info['username']},平台来源:{$this->operation_info['plat']}[{$this->operation_info['plat_name']}],团单id:{$id}",$this->operation_info['id'],$this->operation_info['username']);
            $this->_cleanTeamCache($id);
            $this->redirect_message(U("Team/edit", array('id' => $id,'access_token'=>$access_token)), array('success' => base64_encode('更新成功！')));
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
        if (!$data) {
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
     * 监测数据合法性
     */
    public function check_team_data() {
        $team_post_data = $_POST;
        if (!isset($this->operation_info['plat']) || !trim($this->operation_info['plat'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '操作者身份不明确，操作失败'));
        }
        $plat = strtolower(trim($this->operation_info['plat']));
        if (!$plat) {
            $this->ajaxReturn(array('code' => -1, 'error' => '操作者身份不明确，操作失败'));
        }

        switch ($plat) {
            // 管理后台
            case 'admin':
                if (!isset($team_post_data['city_id']) || !trim($team_post_data['city_id'])) {
                    $this->ajaxReturn(array('code' => -1, 'error' => '请选择所属分站'));
                }
                if (!isset($team_post_data['id']) || !trim($team_post_data['id'])) {
                    if (!isset($team_post_data['partner_id']) || !trim($team_post_data['partner_id'])) {
                        $this->ajaxReturn(array('code' => -1, 'error' => '请选择所属商家'));
                    }
                }


                break;
            // 代理后台
            case 'manage':
                if (!isset($team_post_data['id']) || !trim($team_post_data['id'])) {
                    if (!isset($team_post_data['partner_id']) || !trim($team_post_data['partner_id'])) {
                        $this->ajaxReturn(array('code' => -1, 'error' => '请选择所属商家'));
                    }
                }
                break;
            // 商家后台
            case 'merchant':

                break;
            default:
                $this->ajaxReturn(array('code' => -1, 'error' => '操作者身份不明确，操作失败'));
                break;
        }

        if (!isset($team_post_data['group_id']) || !trim($team_post_data['group_id'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请选择所属分类'));
        }
        if (!isset($team_post_data['team_type']) || !trim($team_post_data['team_type'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请选择团单类型'));
        }
       
        if (!isset($team_post_data['market_price']) || !trim($team_post_data['market_price'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '团单市场价必须填写'));
        }
        
        // 非云购属性判断
        if (trim($team_post_data['team_type']) != 'cloud_shopping') {
            if (!isset($team_post_data['product']) || !trim($team_post_data['product'])) {
                $this->ajaxReturn(array('code' => -1, 'error' => '团单名称必须填写'));
            }

            if (mb_strlen($team_post_data['product'], 'UTF-8') > 15) {
                $this->ajaxReturn(array('code' => -1, 'error' => '团单名称不能超过15个字'));
            }
            
            if (!isset($team_post_data['team_price']) || trim($team_post_data['team_price']) == '') {
                $this->ajaxReturn(array('code' => -1, 'error' => '团单价格必须填写'));
            }

            if (!isset($team_post_data['ucaii_price']) || trim($team_post_data['ucaii_price']) == '') {
                $this->ajaxReturn(array('code' => -1, 'error' => '团单供货价必须填写'));
            }

            if ($team_post_data['market_price'] <= $team_post_data['team_price']) {
                $this->ajaxReturn(array('code' => -1, 'error' => '市场价必须大于团购价'));
            }
        } else {
            if (isset($team_post_data['max_periods_number']) && intval($team_post_data['max_periods_number']) < 1) {
                $this->ajaxReturn(array('code' => -1, 'error' => '期数必须大于0'));
            }
        }
        //2016.4.29 团购价大于活动价修改
        /*if (isset($team_post_data['team_price']) && isset($team_post_data['ucaii_price']) && floatval($team_post_data['team_price']) < floatval($team_post_data['ucaii_price'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '团购价必须大于供货价'));
        }*/

        if (!isset($team_post_data['expire_time']) || !trim($team_post_data['expire_time'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '青团券有效期必须填写'));
        }

        if (!isset($team_post_data['begin_time']) || !trim($team_post_data['begin_time'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '团单开始时间必须填写'));
        }

        if (!isset($team_post_data['end_time']) || !trim($team_post_data['end_time'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '团单结束时间必须填写'));
        }

        if (!isset($team_post_data['detail']) || !trim($team_post_data['detail'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '团单详情必须填写'));
        }

        if (!isset($team_post_data['title']) || !trim($team_post_data['title'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '团单标题必须填写'));
        }

        if (mb_strlen($team_post_data['title'], 'UTF-8') > 50) {
            $this->ajaxReturn(array('code' => -1, 'error' => '团单标题不能超过50个字'));
        }

        if (isset($team_post_data['is_optional_model']) && trim($team_post_data['is_optional_model']) == 'Y') {
            if (!isset($team_post_data['attr_item']) || !$team_post_data['attr_item']) {
                $this->ajaxReturn(array('code' => -1, 'error' => '请添加邮购团单的属性！'));
            }
        }

        /*if (isset($team_post_data['delivery']) && trim($team_post_data['delivery']) == 'voucher' ) {
        	if (isset($team_post_data['team_price']) && intval(trim($team_post_data['team_price'])) > 0) {
                $this->ajaxReturn(array('code' => -1, 'error' => '你选择的递送方式为商户劵，请将网站价设置为0'));
            }
        }*/

        $this->ajaxReturn(array('code' => 0));
    }

}
