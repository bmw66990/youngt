<?php

/**
 * Created by PhpStorm.
 * User:
 * Date: 2015/6/12
 * Time: 15:18
 */

namespace Admin\Controller;

use Think\Model;

/**
 * 邻里圈管理
 * Class DingZuoController
 * @package Admin\Controller
 */
class CircleController extends CommonController {

	protected $_searchWhere;

    /**
     * 邻里圈圈子管理
     */
    public function index(){
        $this->_getSearchWhere();
        $this->_getCircleList($this->_searchWhere);
        $this->display();
    }

    /**
     * 邻里圈举报管理
     */
    public function report(){
        $this->_getSearchWhereReport();
        $this->_getReportList($this->_searchWhereReport);
        $this->display();
    }



/**
     * 邻里圈黑名单管理
     */
    public function black_user(){
        $this->_getSearchWhere();
        $this->_getBlackList($this->_searchWhere);
        $this->display();
    }

	/**
     * 邻里圈添加圈子
     */
    public function add(){
        $this->_getCategoryList('city');
        $this->display();
    }

	/**
     * 邻里圈我的举报
     */
    public function userall() {
        $userid = I('get.id');
        $this->_getSearchWhere();
        $this->_getUserReportList($this->_searchWhere,$userid);
        $this->display();
    }

	/**
     * 获取搜索条件
     */
    protected function _getSearchWhere() {
        $where = array(
            array('city_id', ''),
            array('catname', '', 'like'),
            array('status', ''),
        );

        $map = $this->createSearchWhere($where);
        $searchValue = $this->getSearchParam($where);

        $sctime = I('get.screate_time');
        if (!empty($sctime)) {
            $searchValue['screate_time'] = $sctime;
            $map['create_time'][] = array('EGT', strtotime($sctime));
        }
        $ectime = I('get.ecreate_time');
        if (!empty($ectime)) {
            $searchValue['ecreate_time'] = $ectime;
            $map['create_time'][] = array('ELT', strtotime($ectime));
        }

        $sptime = I('get.spay_time');
        if (!empty($sptime)) {
            $searchValue['spay_time'] = $sptime;
            $map['pay_time'][] = array('EGT', strtotime($sptime));
        }
        $eptime = I('get.epay_time');
        if (!empty($eptime)) {
            $searchValue['epay_time'] = $eptime;
            $map['pay_time'][] = array('ELT', strtotime($eptime));
        }

        $user = I('get.username');
        if (trim($user)) {
            $where = array(
                'username|email' => array('like', '%' . trim($user) . '%')
            );
            $user_id = D('User')->where($where)->getField('id', true);
            if ($user_id) {
                $map['user_id'] = array('IN', $user_id);
            }
            $searchValue['username'] = urldecode($user);
        }

        $this->assign('searchValue', $searchValue);
        $this->_searchWhere = $map;
    }

    /**
     * 获取搜索条件
     */
    protected function _getSearchWhereReport() {
        $where = array(
            array('city_id', ''),
            array('status', ''),
        );

        $map = $this->createSearchWhere($where);
        $searchValue = $this->getSearchParam($where);

        $sptime = I('get.spay_time');
        if (!empty($sptime)) {
            $searchValue['spay_time'] = $sptime;
            $map['pay_time'][] = array('EGT', strtotime($sptime));
        }
        $eptime = I('get.epay_time');
        if (!empty($eptime)) {
            $searchValue['epay_time'] = $eptime;
            $map['pay_time'][] = array('ELT', strtotime($eptime));
        }

        $user = I('get.username');
        if (trim($user)) {
            $where = array(
                'username|email' => array('like', '%' . trim($user) . '%')
            );
            $user_id = D('User')->where($where)->getField('id', true);
            if ($user_id) {
                $map['user_id'] = array('IN', $user_id);
            }
            $searchValue['username'] = urldecode($user);
        }

        $this->assign('searchValue', $searchValue);
        $this->_searchWhereReport = $map;
    }

	/**
     * 获取圈子列表
     * @param $where
     */
    protected function _getCircleList($where) {
        $model = D('Firend_zone');

        $total = self::getTotalCity($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;

        $list = $model->getList($where, 'catid DESC', $limit);
        $this->assign('circle', $list);
        $this->assign('pages', $page->show());
        $this->_getCategoryList('city');
    }

    /**
     * 获取举报内容列表
     * @param $where
     */
    protected function _getReportList($where) {
        $model = D('Firend_report');
        $where['black']='N';
        $total = $model->getTotalReport($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = $model->getListReport($where, 'id DESC', $limit);
        $this->assign('report', $list);
        $this->assign('pages', $page->show());
        $this->_getCategoryList('city');
    }

	/**
     * 获取举报内容列表
     * @param $where
     */
    protected function _getUserReportList($where,$id) {
        $model = D('Firend_report');
        $where['black']='N';
        $where['art_id']=$id;
        $total = $model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = $model->getUserListReport($where, 'id DESC', $limit);
        $this->assign('report_my', $list);
        $this->assign('pages', $page->show());
        $this->_getCategoryList('city');
    }

    /**
     * 获取黑名单列表
     * @param $where
     */
    protected function _getBlackList($where) {
        $model = D('Firend_report');
        $where['black']='Y';
        $total = $model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;

        $list = $model->getList($where, 'id DESC', $limit);

        $this->assign('black', $list);
        $this->assign('pages', $page->show());
        $this->_getCategoryList('city');
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
     * 写入操作
     */
    public function insert() {

        $catname = trim(I('post.catname',''));
        $city_id = trim(I('post.city_id',''));
        if (!empty($_FILES)) {
            $res = $this->upload('img', 'points', '', array('maxSize' => 1024 * 1024 * 5));
            ob_end_clean(); //之前有输出,清空掉
            if ($res) {
                $picture = array();
                foreach ($res as $k => $row) {
                    $picture[] = getImagePath($row['newpath'] . '/' . $row['savename']);
                }
                $image['image'] = $picture;
            }
        }
        if($picture[0]){
            $cat_picture = $picture[0];
        }else{
            $cat_picture = '';
        }

		$mappoint = trim(I('post.mappoint',''));
		$address = trim(I('post.address',''));
		$status = trim(I('post.status',''));
        $options=explode(",",$mappoint);
        $lng =  $options[1];
        $lat =  $options[0];
        $data = array(
            'catname' => $catname,
            'status' => $status,
            'city_id' => $city_id,
            'address' => $address,
            'lng' => $lng,
            'lat' => $lat,
            'cat_picture' => $cat_picture,
        );

        $teaminfo = M('Firend_zone')->add($data);

        if($teaminfo){
            $this->success('新建成功！', U('Circle/index'));
        }else{
            $this->error('新建失败！', U('Circle/index'));
        }
    }

    /**
     * 编辑圈子
     */
    public function edit() {
        $this->_checkblank('catid');
        $id = I('get.catid', 0, 'intval');

        $model = D('Firend_zone');
        $info = $model->info($id);
        if (empty($info)) {
            $this->redirect_message(U("Circle/index"), array('error' => base64_encode('该圈子不存在！')));
            exit();
        }
        if ($info['cat_picture']) {
            $info['cat_picture'] = getImagePath($info['cat_picture']);
        }
        $this->_getCategoryList('city');
        $this->assign('vo', $info);
        $this->display();
    }

	/**
     * 更新圈子
     */
    public function update() {

		$catid = trim(I('post.catid',''));
		$catname = trim(I('post.catname',''));
		$city_id = trim(I('post.city_id',''));

		$address = trim(I('post.address',''));
		$mappoint = trim(I('post.mappoint',''));
		$status = trim(I('post.status',''));

		$options=explode(",",$mappoint);
		$lng =  $options[1];
        $lat =  $options[0];


		if (!empty($_FILES)) {
			$res = $this->upload('img', 'points', '', array('maxSize' => 1024 * 1024 * 5));
			ob_end_clean(); //之前有输出,清空掉
			if ($res) {
				$picture = array();
				foreach ($res as $k => $row) {
					$picture[] = getImagePath($row['newpath'] . '/' . $row['savename']);
				}
				$image['image'] = $picture;
			}
		}



        $data['catid'] = $catid;
        $data['catname'] = $catname;
		$data['address'] = $address;
		$data['city_id'] = $city_id;
		$data['status'] = $status;
		$data['lng'] = $lng;
		$data['lat'] = $lat;
		$data['cat_picture'] = $picture[0];
		$rs = M('Firend_zone')->where('catid='.$catid)->save($data);

		$circleModel = D('Firend_zone');
        if ($rs === false) {

            $this->_writeDBErrorLog($rs, $circleModel);
            $error = $circleModel->getErrorInfo();
            $this->redirect_message(U("Circle/edit", array('catid' => $catid)), array('error' => base64_encode($error['info'])));
            exit();
        } else {

            $this->redirect_message(U("Circle/index"), array('success' => base64_encode('更新成功！')));
        }
    }

	protected function _cleanCircleCache($id) {
        S('circle_' . $id, null);
        @unlink('./Html/circle-' . $id . '.html');
    }

    /**
     * 删除圈子
     */
    public function del() {
        $this->_checkblank('catid');
        $catid = I('get.catid', 0, 'intval');
        $model = D('Firend_zone');
        $vo = $model->info($catid, 'catid,status');
        if (empty($vo)) {
            $this->_writeDBErrorLog($vo, $model, 'admin');
            $this->error('该圈子不存在');
        }
        $map = array(
            'catid' => $catid,
        );
        $circle = M('Firend')->where($map)->find();
        $circlenumber = count($circle);

        if($circlenumber == '0'){
            M('Firend_zone')->where($map)->delete();
            $this->success('删除成功');
        }else{
            $this->error('该圈子中有用户，不能删除');
        }
    }

    /**
     * 删除举报的某一个举报
     */
    public function reportdelall() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $del_report  = M('Firend_report')->where('id='.$id)->delete();
        if($del_report){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 删除举报
     */
    public function reportdel() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $del_report  = M('Firend_report')->where('art_id='.$id)->delete();
        var_dump($del_report);
        if($del_report){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 拉黑举报
     */
    public function black() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $result = M('Firend_report')->where('id='.$id)->setField('black','Y');
        if($result){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }

    /**
     * 移除黑名单
     */
    public function remove_black() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $result = M('Firend_report')->where('id='.$id)->setField('black','N');
        if($result){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }
    /**
     * 获取数据总条数
     * @param $where
     * @return mixed
     */
    public function getTotalCity($where = '') {
        $count = M('Firend_zone')->where($where)->count('catid');
        if ($count === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $count;
    }

    /**
     * 广告管理
     */
    public function adCircle() {
        $paramArray = array(            
            array('title', '', 'like'),            
            array('catid', 0, '')
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $Model = D('Circle_picture');
        $count = $Model->getTotal($where);
        $this->_writeDBErrorLog($count, $Model);
        $all_city  = M('Firend_zone')->select();
        $this->assign('all_city', $all_city);
        $page = $this->pages($count, $this->popup_reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $data = $Model->getList($where, 'id desc', $limit);
        $this->_writeDBErrorLog($data, $Model);
        $this->assign('data', $data);
        $this->assign('displayWhere', $displayWhere);
        $this->display();
    }

    /**
     * 添加广告模板
     */
    public function addAdCircle() {
        $this->_getAllCate();
        $this->display();
    }

    /**
     * 添加广告逻辑处理
     */
    public function doAddAdCircle() {
        if (IS_POST) {
            $Model = D('Circle_picture');
            $res = $Model->insert();
            if ($res) {
                $this->addOperationLogs("操作：添加广告,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},广告id:" . $res);
                $this->redirect_message(U("Circle/adCircle"), array('success' => base64_encode('添加成功!')));
            } else {
                $this->redirect_message(U("Circle/adCircle"), array('error' => base64_encode($Model->getError())));
            }
        } else {
            $this->redirect_message(U("Circle/adCircle"), array('error' => base64_encode('非法操作!')));
        }
    }

    /**
     * 编辑广告模板
     */
    public function editAdCircle() {
        $id = I('get.id', 0, 'intval');
        if (!$id) {
            $this->redirect_message(U("Circle/adCircle"), array('error' => base64_encode('非法操作!')));
        }
        $Model = D('Circle_picture');
        $ad_info = $Model->info($id);
        $this->assign('image', $ad_info['picture'] ? getImagePath($ad_info['picture']) : '');
        $this->assign('ad_info', $ad_info);
        $this->_getAllCate();
        $this->display();
    }

    /**
     * 编辑广告逻辑处理
     */
    public function doEditAdCircle() {
        if (IS_POST) {
            $Model = D('Circle_picture');
            $id = I('post.id', '', 'trim');
            $res = $Model->update();
            if ($res) {
                $this->addOperationLogs("操作：编辑广告,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},广告id:" . I('post.id'));
                $this->redirect_message(U("Circle/adCircle"), array('success' => base64_encode('编辑成功!')));
            } else {
                $this->redirect_message(U("Circle/editAdCircle", array('id' => $id)), array('error' => base64_encode($Model->getError())));
            }
        } else {
            $this->redirect_message(U("Circle/adCircle"), array('error' => base64_encode('非法操作')));
        }
    }

    /**
     * 删除广告
     */
    public function delAdCircle() {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $Model = D('Circle_picture');
            $count = $Model->getTotal(array('id' => $id));
            if ($count) {
                $res = $Model->delete($id);
                if ($res) {
                    $this->addOperationLogs("操作：删除广告,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},广告id:{$id}");
                    $this->redirect_message(U("Circle/adCircle"), array('success' => base64_encode('删除成功')));
                } else {
                    $this->redirect_message(U("Circle/adCircle"), array('error' => base64_encode($Model->getError())));
                }
            } else {
                $this->redirect_message(U("Circle/adCircle"), array('error' => base64_encode('信息不存在')));
            }
        } else {
            $this->redirect_message(U("Circle/adCircle"), array('error' => base64_encode('非法操作')));
        }
    }

    /**
     * 获取关高广告分类城市列表
     */
    public function _getAllCate($val = '') {
        $all_city  = M('Firend_zone')->select();
        $this->assign('all_city', $all_city);    
    }

}
