<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-07-22
 * Time: 09:03
 */
namespace Manage\Controller;

class PointsTeamController extends CommonController {

    /**
     * 获取积分商品
     */
    public function index() {
        $searchWhere = array(
            array('name', '', 'like')
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
        $where['city_id'] = $this->_getCityId();
        $model    = D('PointsTeam');
        $count    = $model->where($where)->count();
        $page     = $this->pages($count, $this->reqnum);
        $limit    = $page->firstRow . ',' . $page->listRows;
        $list     = $model->where($where)->order('id DESC')->limit($limit)->select();
        $cityList = $this->_getCategoryList('city');
        $this->assign('searchValue', $searchValue);
        $this->assign('list', $list);
        $this->assign('cityList', $cityList);
        $this->assign('pages', $page->show());
        $this->assign('count', $count);
        $this->display();
    }

    public function _before_insert() {
        if(isset($_POST['is_convert']) && $_POST['is_convert'] == 'N') {
            $_POST['convert_num'] = 0;
        }
        if(isset($_POST['is_limit']) && $_POST['is_limit'] == 'N') {
            $_POST['limit_num'] = 0;
        }
        $_POST['city_id'] = $this->_getCityId();
        $_POST['is_display'] = 'display';
    }

    public function _before_edit() {
        $id = I('get.id', 0, 'intval');
        $info = D('PointsTeam')->info($id, 'id,partner_id');
        $partner = D('Partner')->info($info['partner_id'], 'title');
        $this->assign('partnerInfo', $partner);
    }

    public function _before_update() {
        if(isset($_POST['is_convert']) && $_POST['is_convert'] == 'N') {
            $_POST['convert_num'] = 0;
        }
        if(isset($_POST['is_limit']) && $_POST['is_limit'] == 'N') {
            $_POST['limit_num'] = 0;
        }
        $_POST['city_id'] = $this->_getCityId();
        $_POST['is_display'] = 'display';
    }

    /**
     * 删除
     */
    public function destroy() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $vo = D('PointsTeam')->info($id);
        if(!$vo) {
            $this->error('信息不存在');
        }
        $count = M('PointsOrder')->where('team_id=' . $id)->count();
        if($count > 0) {
            $res = D('PointsTeam')->where('id=' . $id)->setField('is_display', 'forbidden');
        } else {
            $res = D('PointsTeam')->delete($id);
        }

        if($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
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
     * 兑换记录
     */
    public function record() {
        $searchWhere = array(
            array('team_id', '', '', 'po'),
            array('username', '', '', 'u'),
            array('mobile', '', '', 'u')
        );
        $where = $this->createSearchWhere($searchWhere);
        $where['po.city_id'] = $this->_getCityId();
        $count = M('PointsOrder')->alias('po')->join('user u on po.user_id=u.id')->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = M('PointsOrder')->field('po.*,u.username')->alias('po')->join('user u on po.user_id=u.id')->where($where)->limit($limit)->select();

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
        $this->assign('pages', $page->show());
        $this->assign('count', $count);
        $this->assign('teamList', $teamList);
        $this->assign('searchValue', $this->getSearchParam($searchWhere));
        $this->display();
    }

    /**
     * 积分记录
     */
    public function credit1() {
        $paramArray=array(
            array('user_id','','','c'),
            array('action','','','c'),
        );
        $where=$this->createSearchWhere($paramArray);
        $displayWhere=$this->getSearchParam($paramArray);
        $where['u.city_id']=$this->_getCityId();
        $Model=D('Credit');
        $count = $Model->Distinct(true)->getCreditCount($where);
        if($count === false){
            //TODO 错误日志
            $this->_writeDBErrorLog($count, $Model);
        }
        $page  = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this ->assign('pages', $page->show());
        $field="c.id,c.user_id,c.score,u.username,u.email,u.mobile";//c.action,
        $data = $Model->Distinct(true)->getCredits($where,$limit,'c.score desc',$field);
        if($data===false){
            //TODO 错误日志
            $this->_writeDBErrorLog($data, $Model);
        }
        // 筛选条件使用
        $option_action = array(
            'buy' => '购买商品',
            'login' => '每日登录',
            'pay' => '支付返积',
            'exchange' => '兑换商品',
            'register' => '注册用户',
            'invite' => '邀请好友',
            'refund' => '项目退款',
        );
        $this->assign('displayWhere',$displayWhere);
        $this->assign('option',$option_action);
        $this->assign('data',$data);
        $this->assign('pages',$page->show());
        $this->assign('count',$count);
        $this->display();
    }
    /**
     * 积分记录
     */
    public function credit() {
        $where['city_id']=$this->_getCityId();
        $Model=M('user');
        $count = $Model->where($where)->count('id');
        if($count === false){
            //TODO 错误日志
            $this->_writeDBErrorLog($count, $Model);
        }
        $page  = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this ->assign('pages', $page->show());
        $field="id,score,username,email,mobile";//c.action,
        $data = $Model->where($where)->order('score desc')->field($field)->limit($limit)->select();
        if($data===false){
            //TODO 错误日志
            $this->_writeDBErrorLog($data, $Model);
        }
        // 筛选条件使用
        $option_action = array(
            'buy' => '购买商品',
            'login' => '每日登录',
            'pay' => '支付返积',
            'exchange' => '兑换商品',
            'register' => '注册用户',
            'invite' => '邀请好友',
            'refund' => '项目退款',
        );
        //$this->assign('displayWhere',$displayWhere);
        $this->assign('option',$option_action);
        $this->assign('data',$data);
        $this->assign('pages',$page->show());
        $this->assign('count',$count);
        $this->display();
    }

        /**
     * 商家列表
     */
    public function partnerList() {
        $searchParam = array(
            array('id', ''),
            array('title', '', 'like')
        );
        $where = $this->createSearchWhere($searchParam);
        $where['city_id'] = $this->_getCityId();
        $model = D('Partner');
        $count = $model->getTotal($where);
        $this->_writeDBErrorLog($count, $model);
        $page = $this->pages($count, 9);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = $model->getList($where, 'id DESC', $limit, 'id,title,mobile');
        $this->_writeDBErrorLog($list, $model);
        $searchValue = $this->getSearchParam($searchParam);
        $this->assign('count', $count);
        $this->assign('list', $list);
        $this->assign('searchValue', $searchValue);
        $this->assign('page', $page->show());
        $this->display();
    }

}