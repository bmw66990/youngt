<?php

/**
 * Created by JetBrains PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 15-3-21
 * Time: 下午14:11
 * To change this template use File | Settings | File Templates.
 */

namespace Manage\Controller;

use Manage\Controller\CommonController;

/**
 * 团单控制器
 * Class TeamController
 * @package Manage\Controller
 */
class TeamController extends CommonController {

    /**
     * 	获取团单列表
     */
    public function index() {
        //构造 where 条件
        $paramArray = array(
            array('id', '', '', 't'),
            array('product', '', 'like', 't'),
            array('group_id', '', '', 't'),
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $where['t.city_id'] = $this->_getCityId();
        //筛选出当前在线项目
        $where['t.end_time'] = array('egt', time());
        // 筛选开团时间在 {$start_time} 至 {$end_time} 之间的用户
        $start_time = I('get.start_time', 0, 'strval');
        $end_time = I('get.end_time', 0, 'strval');
        if ($start_time && $end_time) {
            $where['t.begin_time'] = array('between', array(strtotime($start_time), strtotime($end_time)));
            $displayWhere['start_time'] = $start_time;
            $displayWhere['end_time'] = $end_time;
        }
        $this->_getTeamList($where);
        $this->assign('displayWhere', $displayWhere);
        $this->display();
    }

    /***
     * 过期团单
     */
    public function dateOverTeam(){

        $stime = strtotime(date('Y-m-d', strtotime('+7 days')));
        $statime=time();
        $teamModel = M('team');
        $where['city_id']=$this->_getCityId();
        $where['end_time']=  array(between,array($statime,$stime));
        $refundNum = $teamModel->where($where)->select();
        $this->assign('data', $refundNum);
        $this->display();
    }
    /**
     *    下线团单列表
     */
    public function closeTeam() {
        $paramArray = array(
            array('id', '', '', 't'),
            array('product', '', 'like', 't'),
            array('group_id', '', '', 't'),
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $where['t.city_id'] = $this->_getCityId();
        //筛选出已下线的项目
        $where['t.end_time'] = array('elt', time());
        // 筛选开团时间在 {$start_time} 至 {$end_time} 之间的用户
        $start_time = I('get.start_time', 0, 'strval');
        $end_time = I('get.end_time', 0, 'strval');
        if ($start_time && $end_time) {
            $displayWhere['start_time'] = $start_time;
            $displayWhere['end_time'] = $end_time;
            $where['t.end_time'] = array('between', array(strtotime($start_time), strtotime($end_time)));
        }
        $this->assign('displayWhere', $displayWhere);
        $this->_getTeamList($where);
        $this->display();
    }

    /**
     *    通过 where 获取符合条件的团单
     * @param $where 查询条件
     */
    protected function _getTeamList($where) {
        //获取城市信息
        $city_list = $this->_getCategoryList('city');
        $newCity_list = $this->_createNewArr($city_list);
        //获取分类信息
        $group_where['fid'] = 0;
        $group_list = $this->_getCategoryList('group', $group_where);
        $newGroup_list = $this->_createNewArr($group_list);
        //构造 where 条件
        $Model = D('Team');
        $where_count = $this->_createCountWhere($where);
        $count = $Model->getTotal($where_count);
        $this->_writeDBErrorLog($count, $Model);
        $page = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $this->assign('count', $count);
        $field = "t.id,t.product,t.now_number,t.city_id,t.view_count_day,t.team_price,t.ucaii_price,t.partner_id,t.sort_order,t.group_id,t.city_id,u.username,t.begin_time,t.end_time,t.expire_time,p.username as pusername";
        $data = $Model->getTeam($where, $limit, 'id DESC,sort_order DESC', $field);
        if ($data === false) {
            //TODO 记录错误日志
            $this->_writeDBErrorLog($data, $Model);
        }
        foreach ($data as &$val) {
            $val['city_name'] = $newCity_list[$val['city_id']]['name'];
            $val['cate_name'] = $newGroup_list[$val['group_id']]['name'];
            $bd_id = M('partner')->getFieldById($val['partner_id'], 'db_id');
            $bd_name = M('bd_user')->getFieldById($bd_id, 'db_username');
            $val['bd_name'] = $bd_name ? $bd_name : '该商家没有绑定业务员';
        }
        $this->assign('city_list', $city_list);
        $this->assign('group_list', $group_list);
        $this->assign('data', $data);
    }

    /**
     * 	新建团单
     */
    public function add() {
        if (IS_POST) {
            $_POST['city_id'] = $this->_getCityId();
            $_POST['user_id'] = $this->_getUserId();
            $_POST['now_number'] = I('post.pre_number') ? I('post.pre_number') : 0;
            $team_type = $this->_getNewbie() == 'Y' ? I('post.team_type') : 'unaudited';
            if ($team_type == 'goods') {
                $_POST['group_id'] = 16;
                $_POST['delivery'] = 'express';
            }
            $_POST['team_type'] = $team_type;
            $sel3 = I('post.sel3', '', 'trim');
            if ($sel3) {
                $pinyin = new \Common\Org\pinyin();
                $_POST['sel1'] = $pinyin->str2py($sel3, 'all');
                $_POST['sel2'] = $pinyin->str2py($sel3, 'other');
            } else {
                $_POST['sel1'] = '';
                $_POST['sel2'] = '';
            }
            $Model = D('Team');
            //开启事物处理
            $model = M();
            $model->startTrans();
            $rs = $Model->insert();
            if ($rs === false) {
                //TODO 记录错误日志
                $model->rollback();
                $this->_writeDBErrorLog($rs, $Model);
                $error = $Model->getErrorInfo();
                $this->error($error['info']);
            } else {
                //TODO 信息写入search服务
                $data = $Model->create();
                $data['team_id'] = $rs;
                $data = $this->_checkData($data);
                //TODO 新表结构操作
                $teamInfo_res = $this->_addTeamInfo($data);
                //TODO 处理邮购商品属性
                $goods_res = $this->_addGoodsTeamAttr($rs);
                if ($teamInfo_res === false || $goods_res === false) {
                    //不成功，则回滚
                    $model->rollback();
                    $this->error('新建失败');
                } else {
                    //向OTS数据
                    $Ots_res = $this->_putRowDataToOTS('team', array('id' => $data['id']), $data);
                    if (!$Ots_res) {
                        $this->_writeLog("team团单编号为{$rs}这条数据向OTS添加失败", 'ALERT', 'manage');
                    }
                    $model->commit();
                    $this->success('新建成功！', U('Team/index'));
                }
            }
        } else {
            
            $is_open_common_operation = C('IS_OPEN_COMMON_OPERATION');
            if($is_open_common_operation){
                $url = $this->get_common_operation_url('Team/add.html',$this->user['id']);
                if($url){
                    redirect($url);
                    exit;
                }
            }
            
            //项目分类配置
            $team_type = array(
                'normal' => '团购项目',
                'newuser' => '新用户独享',
                'limited' => '限量抢购',
                'timelimit' => '秒杀专区',
                'goods' => '邮购'
            );
            $is_createTeamType = $this->_getNewbie();
            $this->assign('is_createTeamType', $is_createTeamType);
            $this->assign('team_type', $team_type);
            //获取城市信息
            $city_list = $this->_getCategoryList('city');
            $this->assign('city_list', $city_list);
            //获取分类信息
            $group_where['fid'] = 0;
            $group_list = $this->_getCategoryList('group', $group_where);
            $this->assign('group_list', $group_list);
            $this->_getTeamView();
            $this->display();
        }
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
     *  编辑新表team_info数据
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
     * 项目更新
     */
    public function edit() {
        $Model = D('Team');
        if (IS_POST) {
            unset($_POST['ucaii_price']); //不能修改供货价
            unset($_POST['team_price']); //不能修改团购价
            unset($_POST['lottery_price']); //不能修改活动价
            $_POST['city_id'] = $this->_getCityId();
            $_POST['user_id'] = $this->_getUserId();
            $id = I('post.id', 0, 'intval');
            $team_type = $this->_getNewbie() == 'Y' ? I('post.team_type') : 'unaudited';
            if ($team_type == 'goods') {
                $_POST['group_id'] = 16;
                $_POST['delivery'] = 'express';
            }
            $_POST['team_type'] = $team_type;
            $_POST['per_number'] = I('post.per_number', 0, 'intval');
            if(!$_POST['per_number']){
                $_POST['per_number'] = 0;
            }
            $sel3 = I('post.sel3', '', 'trim');
            if ($sel3) {
                $pinyin = new \Common\Org\pinyin();
                $_POST['sel1'] = $pinyin->str2py($sel3, 'all');
                $_POST['sel2'] = $pinyin->str2py($sel3, 'other');
            } else {
                $_POST['sel1'] = '';
                $_POST['sel2'] = '';
            }
            if (!I('post.is_optional_model')) {
                $_POST['is_optional_model'] = 'N';
            } else {
                if (count(I('post.attr_item')) <= 0) {
                    $this->error('请添加商品属性');
                    exit();
                }
            }

            $attrList = array();
            if (I('post.is_optional_model') == 'Y') {
                $attrList = $Model->getTeamAttrItem(I('post.'));
            }
            
            $team = $Model->info($id);
            
            $model = M();
            $model->startTrans();
            $pre_num = I('post.pre_number');
            $max_num = I('post.max_number');
            if ($pre_num) {
                if ($team['pre_number'] > $pre_num) {
                    $this->error('虚拟购买数必须大于' . $team['pre_number']);
                    exit();
                }
                if ($max_num && $max_num < ($pre_num - $team['pre_number'] + $team['now_number'])) {
                    $this->error('虚拟购买数不能大于' . ($max_num - $team['now_number']));
                    exit();
                }
                $_POST['now_number'] = $pre_num - $team['pre_number'] + $team['now_number'];
            }
            $rs = $Model->update();
            if ($rs === false) {
                //TODO 记录日志
                $model->rollback();
                $this->_writeDBErrorLog($rs, $Model);
                $error = $Model->getErrorInfo();
                $this->error('更新失败！' . $error['info']);
            } else {
                $other_res = $this->_afterTeamEdit($Model->create(), $team, $attrList);
                if ($other_res === false) {
                    $model->rollback();
                    $this->error('更新失败！');
                } else {
                    $model->commit();
                }
                //向OTS更新数据
                $Ots_res = $this->_updateRowDataToOTS('team', array('id' => $team['id']), array_filter($team));
                if (!$Ots_res) {
                    $this->_writeLog("team团单编号为{$id}这条数据向OTS更新失败", 'ALERT', 'manage');
                }
                $this->_cleanTeamCache($id);
                $this->_cleanTeamCache($id);
                $this->success('更新成功！', U('Team/index'));
            }
        } else {
            $this->_checkblank('id');
            $id = I('get.id', 0, 'intval');
            
            $is_open_common_operation = C('IS_OPEN_COMMON_OPERATION');
            if($is_open_common_operation){
                $url = $this->get_common_operation_url("Team/edit/id/{$id}.html",$this->user['id']);
                if($url){
                    redirect($url);
                    exit;
                }
            }
            
            $info = $Model->info($id);
            if (empty($info)) {
                $this->error('该团单不存在！');
            }
            if ($info['image']) {
                $image = getImagePath($info['image']);
                $this->assign('image', $image);
            }
            $info['promotion'] = unserialize($info['promotion']);
            $info['attr'] = $Model->getTeamAttrItem($info);
            //项目分类配置
            $team_type = array(
                'normal' => '团购项目',
                'newuser' => '新用户独享',
                'limited' => '限量抢购',
                'timelimit' => '秒杀专区',
                'goods' => '邮购'
            );
            $is_createTeamType = $this->_getNewbie();
            $this->assign('is_createTeamType', $is_createTeamType);
            $this->assign('team_type', $team_type);
            //获取分类信息
            $group_where['fid'] = 0;
            $group_list = $this->_getCategoryList('group', $group_where);
            $newGroup_list = $this->_createNewArr($group_list);
            $this->assign('group_list', $newGroup_list);
            $this->assign('team', $info);
            $this->display();
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
     */
    public function _afterTeamEdit($data, $team, $attrList = array()) {
        $id = $data['id'];
        if (isset($data['id'])) {
            unset($data['id']);
        }
        $this->_updateTeamInfo($id, $data);
        if ($data['expire_time'] != $team['expire_time']) {
            $up_data = array('expire_time' => $data['expire_time']);
            $res = $this->_updateCoupon($up_data, $id);
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

        if ($team['team_type'] == 'goods' && $team['is_optional_model'] == 'Y') {
            if (!$this->_updateGoodsTeamAttr($id, $attrList)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 修改Coupon的过期时间以及商家
     */
    protected function _updateCoupon($data, $id) {
        $Model = M('coupon');
        $where['team_id'] = $id;
        $res = $Model->where($where)->save($data);
        if ($res === false) {
            $this->_writeDBErrorLog($res, $Model);
        }
        return $res;
    }

    /**
     * 修改Order是否可退款
     */
    protected function _updateOrder($data, $id) {
        $Model = M('order');
        $where['team_id'] = $id;
        $res = $Model->where($where)->save($data);
        if ($res === false) {
            $this->_writeDBErrorLog($res, $Model);
        }
        return $res;
    }

    /**
     *  新增新表team_info数据
     */
    protected function _updateTeamInfo($id, $data = array()) {
        //TODO 待添加数据库后完善
        $Model = M('team_info');
        if (!$data) {
            $data = I('post.');
        }
        if ($id) {
            $res = $Model->where("team_id={$id}")->save($data);
            if ($res === false) {
                $this->_writeDBErrorLog($res, $Model);
            }
        }
    }

    /**
     * 更新团单属性
     * @param $id
     * @return boolean
     */
    protected function _updateGoodsTeamAttr($id, $attrList = array()) {
        $data = I('post.');
        $model = D('Team');
        if ($data['is_optional_model'] == 'Y') {
            $attrId = I('post.attr_id');
            $attrItem = I('post.attr_item');
            $attrNum = I('post.attr_num');
            $res = $model->updateTeamAttrItem($data, $attrId, $attrItem, $attrNum, $attrList);
            $total = array_sum($attrNum);
            if ($total != I('post.max_number', 0, 'intval')) {
                M('team')->where('id=' . $id)->setField('max_number', $total);
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
     *    获取订单详情
     * @param int id 团单id
     */
    public function getTeamDetail() {
        $id = intval(I('get.id'));
        if (!empty($id)) {
            $Model = D('Team');
            $pk = $Model->getPk();
            $count = $Model->where($pk . '=' . $id)->count();
            if ($count) {
                $data = $Model->getTeamDetail($id);
                $this->assign('data', $data);
            } else {
                $this->assign('error', '团单不存在或已被删除');
            }
        } else {
            $this->assign('error', '访问不合法');
        }
        $this->display();
    }

    /**
     *    获取新建团单默认数据
     */
    protected function _getTeamView() {
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
						<td colspan="4">
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
     * 获取子分类
     */
    public function getSubName() {
        $group_id = I('post.group_id', 0, 'intval');
        if ($group_id) {
            $where['fid'] = $group_id;
            $data = D('Category')->getlist($where, '', '', 'id,name');
            ajaxReturnNew('msg', $data, 1);
        } else {
            ajaxReturnNew('msg', '获取失败', 0);
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
     * ajax 异步修改团单权重
     */
    public function ajaxSetSortOrder() {
        if (IS_AJAX && IS_POST) {
            $id = I('post.id', 0, 'intval');
            $sort_order = I('post.sort_order', 0, 'intval');
            if ($id && $sort_order) {
                $upData = array('id' => $id, 'sort_order' => $sort_order);
                $res = M('team')->save($upData);
                if ($res) {
                    $Ots_res = $this->_updateRowDataToOTS('team', array('id' => $id), array('sort_order' => $sort_order));
                    if (!$Ots_res) {
                        $this->_writeLog("team团单编号为{$id}这条数据向OTS更新失败", 'ALERT', 'manage');
                    }
                    $data = array('status' => 1, 'message' => '修改成功');
                } else {
                    $data = array('status' => -1, 'error' => '同一团单不能重复修改，请刷新后再修改');
                }
            } else {
                $data = array('status' => -1, 'error' => '缺少请求参数');
            }
        } else {
            $data = array('status' => -1, 'error' => '非法操作');
        }
        $this->ajaxReturn($data);
    }

}
