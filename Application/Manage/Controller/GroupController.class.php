<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-05-04
 * Time: 10:21
 */
namespace Manage\Controller;

class GroupController extends CommonController {

    /**
     * 用户组列表
     */
    public function index() {
        $where = array(
            array('title', '', 'like')
        );

        $model = D('Group');
        $list  = $this->_getList($model, $where);
        $this->assign('list', $list);
        $this->assign('searchValue', $this->getSearchParam($where));
        $this->display();
    }

    /**
     * 授权
     */
    public function authorize() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $vo = D('Group')->info($id);

        if(empty($vo)) {
            $this->error('此用户组不存在');
        }
        $map = array(
            'module' => $vo['module']
        );
        $ruleList = D('Rule')->getList($map);

        $this->assign('ruleList', $ruleList);
        $this->assign('vo', $vo);
        $this->assign('auth', explode(',', $vo['rules']));
        $this->display();
    }

    /**
     * 添加授权信息
     */
    public function doAuthorize() {
        $id    = I('post.id', 0, 'intval');
        $rules = I('post.rules');
        $data  = array(
            'rules' => implode(',', $rules)
        );
        $res = D('Group')->where('id=' . $id)->save($data);
        if($res) {
            $this->success('授权成功');
        } else {
            $this->error('授权失败');
        }
    }

    /**
     * 已绑定用户列表
     */
    public function user() {
        $id    = I('get.id', 0, 'intval');
        $group = D('Group')->info($id);
        $map = array(
            'ga.group_id' => $id,
            'g.module' => $group['module']
        );

        $total = D('Authorize')->getCount($map);
        $page  = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $data  = D('Authorize')->getAuthList($id, $group['module'], $limit);
        $uid   = array();
        foreach($data as $row) {
            $uid[] = $row['uid'];
        }
        $userModel = $this->_getUserModel($group['module']); //TODO 根据不同模块设置表名
        if(!empty($uid)) {
            $where = array(
                'id' => array('IN', $uid)
            );
            $list = $userModel->where($where)->select();
        }
        $this->assign('list', $list);
        $this->assign('id', $id);
        $this->display();
    }

    /**
     * 根君模块名称获取对应的操作表名
     * @param $module
     * @return \Model|\Think\Model
     */
    protected function _getUserModel($module) {
        $relation = C('AUTH_USER_RELATION');
        $table = ternary($relation[strtolower($module)], '');
        if($table == '') {
            $this->error('模块对应数据表未配置');
        }
        return D($table);
    }

    /**
     * 待绑定用户列表
     */
    public function bindUser() {
        $this->_checkblank('id');
        $id          = I('get.id', 0, 'intval');
        $searchParam = array(
            array('username', '', 'like'),
            array('realname', '', 'like')
        );
        $group = D('Group')->info($id);
        $where = $this->createSearchWhere($searchParam);

        $map = array(
            'group_id' => $id,
            'module'   => $group['module']
        );
        $uidList     = D('Authorize')->where($map)->getField('uid', TRUE);

        if($uidList) {
            $where['id'] = array('notin', $uidList);
        }

        $model = $this->_getUserModel($group['module']); //TODO 根据module读取设置用户表
        $total = $model->getTotal($where);
        $page  = $this->pages($total, 10);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list  = $model->getList($where, 'id DESC', $limit, 'id,username,realname');
        $this->assign('list', $list);
        $this->assign('id', $id);
        $this->assign('pages', $page->show());
        $this->assign('searchValue', $this->getSearchParam($searchParam));
        $this->display();
    }

    /**
     * 处理绑定用户
     */
    public function doBindUser() {
        $this->_checkblank(array('id', 'user_id'));
        $id      = I('post.id', 0, 'intval');
        $user_id = I('post.user_id');
        $group   = D('Group')->info($id);
        $data    = array();
        foreach($user_id as $row) {
            $data[] = array(
                'uid'      => $row,
                'group_id' => $id,
                'module'   => $group['module']
            );
        }
        $res = D('Authorize')->addAll($data);
        if($res) {
            $this->success('用户绑定成功');
        } else {
            $this->error('用户绑定失败');
        }
    }

    /**
     * 删除权限组用户
     */
    public function delUser() {
        $this->_checkblank(array('uid', 'gid'));
        $uid = I('get.uid', 0, 'intval');
        $gid = I('get.gid', 0, 'intval');
        $map = array(
            'uid'      => $uid,
            'group_id' => $gid
        );
        $res = D('Authorize')->where($map)->delete();
        if($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}