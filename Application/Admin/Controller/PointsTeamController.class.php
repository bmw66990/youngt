<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-07-22
 * Time: 10:36
 */
namespace Admin\Controller;

use Admin\Controller\CommonController;

class PointsTeamController extends CommonController {

    /**
     * 获取积分商品
     */
    public function index() {
        $searchWhere = array(
            array('name', '', 'like'),
            array('city_id', 0)
        );
        $where       = $this->createSearchWhere($searchWhere);
        $searchValue = $this->getSearchParam($searchWhere);
        $begin_time  = I('get.begin_time');
        if($begin_time && strtotime($begin_time)) {
            $where['begin_time']       = array('EGT', strtotime($begin_time));
            $searchValue['begin_time'] = $begin_time;
        }
        $end_time = I('get.end_time');
        if($end_time && strtotime($end_time)) {
            $where['end_time']       = array('ELT', strtotime($end_time));
            $searchValue['end_time'] = $end_time;
        }
        $model    = D('PointsTeam');
        $count    = $model->where($where)->count();
        $page     = $this->pages($count, $this->reqnum);
        $limit    = $page->firstRow . ',' . $page->listRows;
        $list     = $model->where($where)->order('id DESC')->limit($limit)->select();
        $cityList = $this->_getCategoryList('city');
        $this->assign('searchValue', $searchValue);
        $this->assign('list', $list);
        $this->assign('cityList', $cityList);
        $this->display();
    }

    /**
     * 新增
     */
    public function add() {
        $cityList = $this->_getCategoryList('city');
        $this->assign('cityList', $cityList);
        $this->display();
    }

    /**
     * 写入
     */
    public function insert() {
        if(isset($_POST['is_convert'])) {
            $_POST['convert_num'] = 0;
        }
        if(isset($_POST['is_limit'])) {
            $_POST['limit_num'] = 0;
        }
        $model = D('PointsTeam');
        if($res = $model->insert()) {
            $this->addOperationLogs("操作：新增积分商品,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},商品id:{$res}");
            $this->redirect_message(U('PointsTeam/index'), array('success' => base64_encode('新增成功')));
        } else {
            $this->redirect_message(U('PointsTeam/index'), array('error' => base64_encode('新增失败')));
        }
    }

    /**
     * 编辑
     */
    public function edit() {
        $id            = I('get.id', 0, 'intval');
        $info          = D('PointsTeam')->info($id);
        $partner       = D('Partner')->info($info['partner_id'], 'title');
        $info['image_uri'] = $info['image'];
        $info['image'] = getImagePath($info['image']);

        $cityList = $this->_getCategoryList('city');
        $this->assign('cityList', $cityList);

        $this->assign('partnerInfo', $partner);
        $this->assign('vo', $info);
        $this->display();
    }

    /**
     * 更新
     */
    public function update() {
        if(isset($_POST['is_convert'])) {
            $_POST['convert_num'] = 0;
        }
        if(isset($_POST['is_limit'])) {
            $_POST['limit_num'] = 0;
        }
        $model = D('PointsTeam');
        if($model->update()) {
            $id = I('post.id', 0, 'intval');
            $this->addOperationLogs("操作：编辑积分商品,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},商品id:{$id}");
            $this->redirect_message(U('PointsTeam/index'), array('success' => base64_encode('更新成功')));
        } else {
            $this->redirect_message(U('PointsTeam/index'), array('error' => base64_encode('更新失败')));
        }
    }

    /**
     * 审核
     */
    public function check() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $vo = D('PointsTeam')->info($id);
        if(!$vo) {
            $this->redirect_message(U('PointsTeam/index'), array('error' => base64_encode('信息不存在')));
        }
        $this->assign('vo', $vo);
        $this->display();
    }

    /**
     * 提交审核结果
     */
    public function toCheck() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $vo = D('PointsTeam')->info($id);
        if(!$vo) {
            $this->redirect_message(U('PointsTeam/index'), array('error' => base64_encode('信息不存在')));
        }
        $state = I('get.state');
        if(!in_array($state, array('display', 'blank', 'forbidden'))) {
            $this->redirect_message(U('PointsTeam/index'), array('error' => '参数非法'));
        }
        $res = M('PointsTeam')->where('id=' . $id)->setField('is_display', $state);
        if($res) {
            $this->addOperationLogs("操作：积分商品修改状态为{$state},管理员id:{$this->user['id']},管理员名称:{$this->user['username']},商品id:{$id}");
            $this->redirect_message(U('PointsTeam/index'), array('success' => base64_encode('操作成功')));
        } else {
            $this->redirect_message(U('PointsTeam/index'), array('error' => base64_encode('操作失败')));
        }
    }

    /**
     * 删除
     */
    public function destroy() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $vo = D('PointsTeam')->info($id);
        if(!$vo) {
            $this->redirect_message(U('PointsTeam/index'), array('error' => base64_encode('信息不存在')));
        }
        $count = M('PointsOrder')->where('team_id=' . $id)->count();
        if($count > 0) {
            $this->redirect_message(U('PointsTeam/index'), array('error' => base64_encode('此商品无法删除')));
        }

        $res = D('PointsTeam')->delete($id);
        if($res) {
            $this->addOperationLogs("操作：删除积分商品,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},商品id:{$id}");
            $this->redirect_message(U('PointsTeam/index'), array('success' => base64_encode('删除成功')));
        } else {
            $this->redirect_message(U('PointsTeam/index'), array('error' => base64_encode('删除失败')));
        }
    }

    /**
     * 上传图片
     */
    public function uploadImg(){
        $data = $this->upload('img', 'points');
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
     * 积分记录
     */
    public function scoreList() {
        $paramArray=array(
            array('user_id','','','c'),
            array('action','','','c'),
            array('city_id', 0, '', 'u'),
            array('username', '', 'like', 'u'),
            array('mobile', '', 'like', 'u')
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $Model = D('Credit');
        $count = $Model->getCreditCount($where);    
        $page  = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this ->assign('pages', $page->show());
        $field="c.id,c.action,c.user_id,c.score,u.username,u.email,u.mobile,u.city_id";
        $data = $Model->getCredits($where,$limit,'c.id desc',$field);
        if($data===false){
            //TODO 错误日志
            $this->_writeDBErrorLog($data, $Model);
        }
        // 筛选条件使用
        $option_action = array(
            'buy'      => '购买商品',
            'login'    => '每日登录',
            'pay'      => '支付返积',
            'exchange' => '兑换商品',
            'register' => '注册用户',
            'invite'   => '邀请好友',
            'refund'   => '项目退款',
        );
        $cityList = $this->_getCategoryList('city');
        $this->assign('cityList', $cityList);
        $this->assign('displayWhere',$displayWhere);
        $this->assign('option',$option_action);
        $this->assign('data',$data);
        $this->assign('searchValue', $this->getSearchParam($paramArray));
        $this->display();
    }

    /**
     * 兑换记录
     */
    public function convertRecord() {
        $searchWhere = array(
            array('team_id', '', '', 'po'),
            array('username', '', 'like', 'u'),
            array('mobile', '', 'like', 'u'),
            array('city_id', '', '', 'po'),
            array('code', '', '', 'po'),
        );
        $where = $this->createSearchWhere($searchWhere);
        $count = M('PointsOrder')->alias('po')->join('user u on po.user_id=u.id')->where($where)->count();
        $page  = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list  = M('PointsOrder')->field('po.*,u.username')->alias('po')->join('user u on po.user_id=u.id')->where($where)->limit($limit)->select();

        $teamId = array();
        foreach($list as $row) {
            $teamId[] = $row['team_id'];
        }
        if($teamId) {
            $map = array(
                'id' => array('IN', array_unique($teamId))
            );
            $teamList = M('PointsTeam')->where($map)->getField('id,name', true);
        }

        $this->assign('list', $list);
        $this->assign('teamList', $teamList);
        $this->assign('pages', $page->show());
        $this->assign('searchValue', $this->getSearchParam($searchWhere));
        $this->display();
    }

    /**
     * 积分规则
     */
    public function rule() {
        $model = M('System');
        if(IS_POST) {
            $data = I('post.');
            foreach($data as $key=>$row) {
                $map['keys'] = $key;
                if($model->where($map)->count() > 0) {
                    $model->where($map)->setField('values', $row);
                } else {
                    $model->add(array(
                        'keys' => $key,
                        'values' => $row
                    ));
                }
            }
            $this->addOperationLogs("操作：设置积分规则,管理员id:{$this->user['id']},管理员名称:{$this->user['username']}");
            $this->redirect_message(U('PointsTeam/rule'), array('succcess' => base64_encode('设置成功')));
        } else {
            $data = $model->select();
            $rule = array();
            foreach($data as $row) {
                $rule[$row['keys']] = $row['values'];
            }
            $this->assign('rule', $rule);
            $this->display();
        }
    }

    /**
     * 积分充值
     */
    public function charge() {
        $username = I('post.username', '', 'strval');
        $user_id = I('post.user_id', 0, 'intval');
        $score = I('post.score', 0, 'intval');
        
        if(!$username && !$user_id){
            $this->redirect_message(U('PointsTeam/rule'), array('error' => base64_encode('请输入用户名或用户id')));
        }
        if(!$score || $score<=0){
            $this->redirect_message(U('PointsTeam/rule'), array('error' => base64_encode('充值的积分必须大于0')));
        }
        
        $map = array();
        if($user_id){
            $map['id']=$user_id;
        }elseif($username){
            $map['username|mobile']=trim($username);
        }
        
        if(!$map){
            $this->redirect_message(U('PointsTeam/rule'), array('error' => base64_encode('请输入用户名或用户id')));
        }
 
        $user = M('User')->where($map)->field('id,score')->find();
        if(!$user) {
            $this->redirect_message(U('PointsTeam/rule'), array('error' => base64_encode('用户不存在')));
        }

        $model = M();
        $model->startTrans();
        $res = M('User')->where($map)->setInc('score', $score);
        if(!$res) {
            $model->rollback();
            $this->redirect_message(U('PointsTeam/rule'), array('error' => base64_encode('充值失败')));
        }
        // 积分表写入信息
        $data = array(
            'user_id'     => $user['id'],
            'admin_id'    => 0,
            'score'       => $score,
            'action'      => 'charge',
            'detail_id'   => 0,
            'create_time' => time(),
            'sumscore'    => $user['score'] + $score
        );
        if(!M('Credit')->add($data)) {
            $model->rollback();
            $this->redirect_message(U('PointsTeam/rule'), array('error' => base64_encode('充值失败')));
        }

        $model->commit();
        $this->addOperationLogs("操作：积分充值,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},用户id:{$user_id},用户名:{$username},积分:{$score}");
        $this->redirect_message(U('PointsTeam/rule'), array('success' => base64_encode('充值成功')));
    }
    
    /**
     * 撤销兑换
     */
    public function pointsOrderRefund(){
        $points_order_id = I('get.points_order_id', '', 'trim');
        if(!$points_order_id){
            $this->ajaxReturn(array('code' => -1, 'error' => '兑换记录ID不能为空！'));
        }
        
        $points_order_info = M('points_order')->where(array('id'=>$points_order_id))->find();
        if(!$points_order_info){
            $this->ajaxReturn(array('code' => -1, 'error' => '要撤销兑换记录不存在！'));
        }
        
        if(!isset($points_order_info['user_id']) || !trim($points_order_info['user_id'])){
            $this->ajaxReturn(array('code' => -1, 'error' => '兑换记录对应用户信息异常！'));
        }
        
        $user_info = M('user')->where(array('id'=>$points_order_info['user_id']))->field('score')->find();
        if(!$user_info){
            $this->ajaxReturn(array('code' => -1, 'error' => '兑换记录对应用户信息异常！'));
        }
        
        // 撤销退款
        $model = M();
        $model->startTrans();
        
        // 删除兑换记录
        $res = M('points_order')->where(array('id'=>$points_order_id))->delete();
        if(!$res){
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '撤销兑换记录失败！'));
        }
        
        // 给用户添加积分
        if(isset($points_order_info['total_score']) && $points_order_info['total_score'] > 0){
            $update_user_data = array(
                'score'=>intval($points_order_info['total_score']+$user_info['score'])
            );
            $res = M('user')->where(array('id'=>$points_order_info['user_id']))->save($update_user_data);
            if(!$res){
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '退还用户积分失败！'));
            }
            $add_credit_data = array(
                'user_id'     => $points_order_info['user_id'],
                'admin_id'    => 0,
                'score'       => $points_order_info['total_score'],
                'action'      => 'charge',
                'detail_id'   => 0,
                'create_time' => time(),
                'sumscore'    => $update_user_data['score']
            );
            $res = M('credit')->add($add_credit_data);
            if(!$res){
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '退还用户积分失败！'));
            }
        }
        if(isset($points_order_info['team_id']) && $points_order_info['team_id']){//consume_num
            $res = M('points_team')->where(array('id' => $points_order_info['team_id']))->setDec('consume_num',$points_order_info['num']);
            if ($res===false) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '调整商品兑换份数失败！'));
            }
        }
        
        $model->commit();
        $this->addOperationLogs("操作：积分撤销兑换,兑换码:{$points_order_info['code']},用户id:{$points_order_info['user_id']},退还积分:{$points_order_info['total_score']}");
        $this->ajaxReturn(array('code' => 0)); 
    }

}