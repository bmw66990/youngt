<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-03-19
 * Time: 16:10
 */
namespace Manage\Controller;
/**
 * Bd控制器
 * Class BdUserController
 * @package Manage\Controller
 */
class BdUserController extends CommonController {

    /**
     * BD 列表
     */
    public function index() {
        $_GET['city_id'] = $this->_getCityId();
        $searchParam = array(
            array('db_username', '', 'like'),
            array('db_name', '', 'like'),
            array('db_phone', ''),
            array('city_id', 0),
        );
        $list = $this->_getList(M('BdUser'), $searchParam, 'id,db_username,db_name,db_phone');
        $this->assign('list', $list);
        $ids = '';
        foreach($list as $row) {
            $ids .= $row['id'] . ',';
        }

        $where = array();

        // 获取商家
        if(!empty($ids)) {
            $where['db_id'] = array('in', substr($ids, 0, -1));
        }
        $plip = D('Partner')->getBdUserPartner($where);

        // 获取签单量
        if(!empty($ids)) {
            $where['p.db_id'] = array('in', substr($ids, 0, -1));
        }
        $slip = D('Team')->getBdUserTeam($where);

        //记录错误日志
        $this->_writeDBErrorLog($slip, D('Team'));
        $searchValue = $this->getSearchParam($searchParam);

        $this->assign('searchValue', $searchValue);
        $this->assign('plip', $plip);
        $this->assign('slip', $slip);
        $this->display();
    }

    /**
     * insert前置操作
     */
    public function _before_insert() {
        $_POST['city_id'] = $this->_getCityId();
        if (isset($_POST['password'])) {
            $password = trim($_POST['password']);
            if (strlen($password) > 0) {
                $_POST['password'] = encryptPwd($password);
            } else {
                unset($_POST['password']);
            }
        }
    }

    /**
     * 绑定商家
     */
    public function bindPartner() {
        if (IS_POST) {
            // 绑定
            $this->_checkblank(array('id' ,'pids'));
            $id   = I('post.id', 0, 'intval');
            $pids = I('post.pids');

            $where['id']      = array('in', $pids);
            $where['city_id'] = $this->_getCityId();
            $model = D('Partner');
            $rs = $model->where($where)->setField('db_id', $id);
            if($rs) {
                $this->ajaxReturn(array('code' => 0, 'error' => '', 'num' => $rs));
            } else {
                $this->_writeDBErrorLog($rs, $model);
                $this->ajaxReturn(array('code' => 1, 'error' => '商家绑定失败！'));
            }
        } else {
            $this->_checkblank('id');
            $id = I('get.id', 0, 'intval');
            //获取未绑定BD的商家列表
            $where['city_id'] = $this->_getCityId();
            $where['db_id']   = 0;
            $title = I('get.title', '');
            if(!empty($title)) {
                $where['title'] = array('like', '%' . $title . '%');
                $this->assign('title', $title);
            }
            $partnerModel = D('Partner');
            $count        = $partnerModel->getTotal($where);
            $this->_writeDBErrorLog($count, $partnerModel);
            $page         = $this->pages($count, 7, $where);
            $limit        = $page->firstRow . ',' . $page->listRows;
            $show         = $page->show();
            $partner      = $partnerModel->getList($where, '', $limit, 'id,title,mobile');
            $this->_writeDBErrorLog($partner, $partnerModel);
            $this->assign('count', $count);
            $this->assign('partner', $partner);
            $this->assign('id', $id);
            $this->assign('pages', $show);
            $this->display();
        }
    }

    /**
     * 绑定商家列表
     */
    public function unbindPartner() {
        if (IS_POST) {
            // 解绑
            $this->_checkblank('id', 'pids');
            $id   = I('get.id', 0, 'intval');
            $pids = I('post.pids');

            $where['id']      = array('in', $pids);
            $where['city_id'] = $this->_getCityId();
            $model = D('Partner');
            $rs = $model->where($where)->setField('db_id', 0);
            if($rs) {
                $this->ajaxReturn(array('code' => 0, 'error' => '', 'num' => $rs));
            } else {
                $this->_writeDBErrorLog($rs, $model);
                $this->ajaxReturn(array('code' => 1, 'error' => '商家解绑失败！'));
            }
        } else {
            $this->_checkblank('id');
            $id = I('get.id', 0, 'intval');
            //获取已绑定BD的商家列表
            $where['city_id'] = $this->_getCityId();
            $where['db_id']   = $id;
            $title = I('get.title', '');
            if(!empty($title)) {
                $where['title'] = array('like', '%' . $title . '%');
                $this->assign('title', $title);
            }
            $partnerModel = D('Partner');
            $count        = $partnerModel->getTotal($where);
            $this->_writeDBErrorLog($count, $partnerModel);
            $page         = $this->pages($count, 7, $where);
            $limit        = $page->firstRow . ',' . $page->listRows;
            $show         = $page->show();
            $partner      = $partnerModel->getList($where, '', $limit, 'id,title,mobile');
            $this->_writeDBErrorLog($partner, $partnerModel);
            $this->assign('partner', $partner);
            $this->assign('id', $id);
            $this->assign('pages', $show);
            $this->assign('count', $count);
            $this->display();
        }
    }

    /**
     * 更新业务员信息
     */
    function update() {
        $password = I('post.password', '', 'trim');
        if($password){
            if(!checkPwd($password)){
                $data = array('errno' => 1, 'msg' => '密码必须是6~20位数字和字母组成!');
            }
            $_POST['password'] = encryptPwd(trim($password)); 
        }else{
            unset($_POST['password']);
        }
        $model = D('BdUser');
        $pk = $model->getPk();
        $pkval = I('post.'.$pk);
        if (!$pkval) {
            $data = array('errno' => 1, 'msg' => '非法操作！');
        } else {
            if (!$model->create()) {
                $data = array('errno' => 1, 'msg' => $model->getDbError());
            } else {
                $res = $model->where(array($pk => $pkval))->save();
                if ($res === false) {
                    $data = array('errno' => 1, 'msg' => $model->getDbError());
                } else if ($res === 0) {
                    $data = array('errno' => 1, 'msg' => '数据未更改！');
                } else {
                    $data = array('errno' => 0, 'msg' => '数据已更新！');
                }
            }
        }
        $this->ajaxReturn($data);
    }

    /**
     * 下载商家资料
     */
    public function downPartners() {
        $id = I('get.id', 0, 'intval');
        $db_user = M('bd_user')->where(array('id'=>$id))->find();
        if (!$db_user) {
            # code...
        } else {
            $where['city_id'] = $this->_getCityId();
            $where['db_id']   = $id;
            $model = D('Partner');
            $data = $model->field('id,title,address,phone,contact,mobile')->where($where)->order('id desc')->select();
            $keyname = array(
                'id'      => '编号',
                'title'   => '名称',
                'address' => '地址',
                'phone'   => '电话',
                'contact' => '联系人',
                'mobile'  => '手机号'
            );
            download_xls($data, $keyname, $db_user['db_name'].'_'.date('Y-m-d').'_'.'商户信息');
        }
    }

    /**
     * 财务
     */
    public function finance() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $time = $this->_getSearchTime();

        // 获取商家列表
        $db_user = M('bd_user')->where(array('id'=>$id))->find();
        $where['city_id'] = $this->_getCityId();
        $where['db_id']   = $id;
        $model = D('Partner');
        $count = $model->where($where)->count('id');
        $page  = $this->pages($count, 10, $where);
        $limit = $page->firstRow . ',' . $page->listRows;
        $show  = $page->show();
        $list  = $model->field('id,title')->where($where)->order('id desc')->limit($limit)->select();
        $list  = array_map(function($partner) use($model,$time) {
            $data = $model->getProfit($partner['id'], $time['st'], $time['et']);
            $info = array('num' => 0, 'profit' => 0, 'income' => 0);
            foreach ($data as $i => $row) {
                $info['num']    += $row['num'];
                $info['profit'] += $row['profit'];
                $info['income'] += $row['team_price'] * $row['num'];
            }
            $info['margin'] = $info['income'] == 0 ? 0 : round(100 * ($info['profit']/$info['income']), 2);
            return array_merge($partner, $info);
        },$list);
        if (I('get.down')) {
            $keynames = ['id'=>'编号','title'=>'商家名称','num'=>'验证量','income'=>'入账金额','profit'=>'毛利润','margin'=>'毛利率'];
            download_xls($list,$keynames,$id.'_'.$time['st'].'_'.$time['et']);
        } else {
            $this->assign('searchTime', $time);
            $this->assign('list', $list);
            $this->assign('pages', $show);
            $this->assign('count', $count);
            $this->assign('id', $id);
            $this->display();
        }
    }

    // 查询明细
    public function flow($id,$stime,$etime) {
        $model = D('Partner');
        $list = $model->getProfit($id, $stime, $etime);
        $this->assign('list', $list);
        $this->display();
    }

}
