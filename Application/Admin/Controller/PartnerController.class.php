<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/6/10
 * Time: 17:40
 */

namespace Admin\Controller;

/**
 * 商户控制器
 * Class PartnerController
 * @package Admin\Controller
 */
class PartnerController extends CommonController {

    /**
     * 商户列表
     */
    public function index() {
        $searchParam = array(
            array('id', ''),
            array('city_id', 0, ''),
            array('display', ''),
            array('group_id', 0, ''),
        );
        $where = $this->createSearchWhere($searchParam);
        $displayWhere = $this->getSearchParam($searchParam);
        $title = I('get.title', '', 'strval');
        if ($title) {
            $where['username|title'] = array('like', "%{$title}%");
            $displayWhere['title'] = $title;
        }
        
        $fid = I('get.fid', '', 'trim');
        if($fid != ''){
            $where['fid'] = $fid;
        }
        
        $Model = D('Partner');
        $count = $Model->getTotal($where);
        $this->_writeDBErrorLog($count, $Model);
        $page = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $field = 'id,title,phone,bank_user,mobile,city_id,group_id,db_id,username,head,display,contact';
        $data = $Model->getList($where, 'head DESC,id DESC', $limit, $field);
        $this->_writeDBErrorLog($data, $Model);
        //城市列表
        $this->assign('all_city', $this->_getCategoryList('city'));
        //商户分类
        $this->assign('partner_group', $this->_getCategoryList('partner'));
        $this->assign('data', $data);
        $this->assign('displayWhere', $displayWhere);
        $this->assign('pages', $page->show());
        $this->assign('fid', $fid);
        $this->display();
    }

    /**
     * 新建商户
     */
    public function add() {
        //默认经纬度
        $point = '39.915,116.404';
        //总店id
        $fid = I('get.fid', 0, 'intval');
        //城市列表
        $this->assign('all_city', $this->_getCategoryList('city'));
        //商户分类
        $this->assign('partner_group', $this->_getCategoryList('partner'));

        $this->assign('point', $point);

        $this->assign('fid', $fid);

        $this->display();
    }

    /**
     * insert前置操作
     */
    public function _before_doAdd() {
        $point = I('post.longlat');
        if (!empty($point)) {
            $pointArr = explode(',', $point);
            $_POST['lat'] = $pointArr[0];
            $_POST['long'] = $pointArr[1];
        }
        $_POST['user_id'] = $this->user['id'];
        $branch = I('post.fid', 0, 'intval');
        if (!empty($branch)) {
            $_POST['is_branch'] = 'N';
        }
    }

    /**
     * 新疆商户逻辑处理
     */
    public function doAdd() {
        $Model = D('Partner');
        $partner_id = $Model->insert();
        if ($partner_id) {
            $this->redirect_message(U("Partner/add"), array('success' => base64_encode('添加成功!')));
        } else {
            $this->redirect_message(U("Partner/add"), array('error' => base64_encode($Model->getError())));
        }
    }

    /**
     * 编辑商户
     */
    public function edit() {
        $id = I('get.id', 0, 'intval');
        if (!$id) {
            $this->redirect_message(U("Partner/index"), array('error' => base64_encode('非法请求!')));
        }
        $Model = D('Partner');
        $partner_info = $Model->info($id);
        //城市列表
        $this->assign('all_city', $this->_getCategoryList('city'));
        //商户分类
        $this->assign('partner_group', $this->_getCategoryList('partner'));
        //商圈
        $this->assign('district', $this->_getCategoryList('district', array('fid' => $partner_info['city_id'])));
        //子商圈
        $this->assign('station', $this->_getCategoryList('station', array('fid' => $partner_info['zone_id'])));
        //商家信息
        $this->assign('partner_info', $partner_info);
        //商家图片
        $this->assign('image', $partner_info['image'] ? getImagePath($partner_info['image']) : '');

        $this->assign('point', $partner_info['longlat'] ? $partner_info['longlat'] : '39.915,116.404');


        $this->display();
    }

    /**
     * 编辑操作前置操作
     */
    public function _before_doEdit() {
        $point = I('post.longlat');
        $banks = I('post.banks', '', 'trim');
        $sbank = I('post.sbank', '', 'trim');
        $bankx = I('post.bankx', '', 'trim');
        $password = I('post.password', '', 'trim');
        if (!empty($point)) {
            $pointArr = explode(',', $point);
            $_POST['lat'] = $pointArr[0];
            $_POST['long'] = $pointArr[1];
        } else {
            unset($_POST['longlat']);
        }
        if (!$banks) {
            unset($_POST['banks']);
        }
        if (!$sbank) {
            unset($_POST['sbank']);
        }
        if (!$bankx) {
            unset($_POST['banks']);
        }
        if ($password) {
            $_POST['password'] = encryptPwd(trim($password));
        }else{
            unset($_POST['password']);
        }
    }

    /**
     * 编辑商户逻辑处理
     */
    public function doEdit() {
        $Model = D('Partner');
        $login_acccess = M('login_access');
        $id = I('post.id', '', 'trim');
        $username = I('post.username', '', 'trim');
        $password = I('post.password', '', 'trim');

        if (!$username) {
            $this->redirect_message(U("Partner/edit", array('id' => $id)), array('error' => base64_encode('用户名不能为空！')));
        }

        // 判断用户名是否重复
        $partner_count = $Model->where(array('username' => $username, 'id' => array('neq', $id)))->count();
        if ($partner_count && $partner_count > 0) {
            $this->redirect_message(U("Partner/edit", array('id' => $id)), array('error' => base64_encode('用户名已被占用，请更换用户名！')));
        }
        $partner_count = $login_acccess->where(array('username' => $username, 'uid' => array('neq', $id), 'is_super_admin' => array('neq', 'Y')))->count();
        if ($partner_count && $partner_count > 0) {
            $this->redirect_message(U("Partner/edit", array('id' => $id)), array('error' => base64_encode('用户名已被占用，请更换用户名！')));
        }

        $res = $Model->update();
        if (!$res) {
            $this->redirect_message(U("Partner/edit", array('id' => $id)), array('error' => base64_encode($Model->getError())));
        }
        
        // 修改登录账号相关密码
        $login_where = array('uid' => $id, 'is_super_admin' => 'Y');
        $login_count = $login_acccess->where($login_where)->count();
        if(!$login_count || $login_count<=0){
              $this->redirect_message(U("Partner/index"), array('success' => base64_encode('修改成功!')));
        }
        $data = array(
            'username' => $username,
        );
        if ($password) {
            $data['password'] = encryptPwd($password);
        }
        $res = $login_acccess->where($login_where)->save($data);
        if ($res === false) {
            $this->redirect_message(U("Partner/edit", array('id' => $id)), array('error' => base64_encode('修改失败！')));
        }
        $this->redirect_message(U("Partner/index"), array('success' => base64_encode('修改成功!')));
    }
    
    /**
     *   商家添加编辑校验非法字段
     */
    public function checkPartnerValidate(){
        $data = I('post.',array());
        $res = D('Partner')->checkPartnerValidate($data);
        if(isset($res['error']) && trim($res['error'])){
            $this->ajaxReturn(array('code'=>-1,'error'=>$res['error']));
        }
        $this->ajaxReturn(array('code'=>0,'success'=>$res['message']));
    }

    /**
     * 异步获取商圈或子商圈
     */
    public function ajaxChangeData() {
        if (IS_AJAX) {
            $city_id = I('get.city_id', 0, 'intval');
            $type = '';
            if ($city_id) {
                $list = $this->_getCategoryList('district', array('fid' => $city_id));
                $type = 'city';
            }
            $zone_id = I('get.zone_id', 0, 'intval');
            if ($zone_id) {
                $list = $this->_getCategoryList('station', array('fid' => $zone_id));
                $type = 'zone';
            }
            if (isset($list)) {
                $data = array('status' => 1, 'data' => $list, 'type' => $type);
            } else {
                $data = array('status' => -1, 'error' => '请求数据不合法');
            }
        } else {
            $data = array('status' => -1, 'error' => '非法请求');
        }
        $this->ajaxReturn($data);
    }

    public function getMap() {
        $point = I('get.lnglat', '', 'trim');
        $pointArr = explode(',', $point);
        $data = array('long' => $pointArr[1], 'lat' => $pointArr[0]);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 删除商家
     */
    public function delPartner() {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $Model = D('Partner');
            $count = $Model->getTotal(array('id' => $id));
            if ($count) {
                $res = $Model->delete($id);
                if ($res) {
                    $this->addOperationLogs("操作：删除商家,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},商家id:{$id}");
                    $this->redirect_message(U("Partner/index"), array('success' => base64_encode('删除成功!')));
                } else {
                    $this->redirect_message(U("Partner/index"), array('error' => base64_encode($Model->getError())));
                }
            } else {
                $this->redirect_message(U("Partner/index"), array('error' => base64_encode('商家信息不存在!')));
            }
        } else {
            $this->redirect_message(U("Partner/index"), array('error' => base64_encode('非法操作!')));
        }
    }

    /**
     * 上传图片
     */
    public function uploadImg() {
        $type = ternary(I('get.type', '', 'strval'), 'partner');
        $data = $this->upload('img', $type);
        if ($data) {
            $info['state'] = 'SUCCESS';
            $info['url'] = $data[0]['newpath'] . '/' . $data[0]['savename'];
            $info['msg'] = '上传成功';
        } else {	
            $info['state'] = 'ERROR';
            $info['msg'] = '上传失败';
        }
        ob_end_clean();
        $this->ajaxReturn($info);
    }

    /**
     * 获取商户团单列表
     */
    public function partnerTeamList() {
        $city_id = I('get.city_id', '', 'trim');
        $partner_id = I('get.partner_id', '', 'trim');
        $city_list = $this->_getCategoryList('city');

        if (!isset($_GET['city_id']) || !isset($_GET['partner_id'])) {
            $this->assign('city_list', $city_list);
            $this->display();
            exit;
        }

        if (!$city_id) {
            $this->redirect_message(U("Partner/partnerTeamList"), array('error' => base64_encode('请选择城市!')));
        }
        if (!$partner_id) {
            $this->redirect_message(U("Partner/partnerTeamList"), array('error' => base64_encode('请选择商家!')));
        }

        $where = array(
            'team.city_id' => $city_id,
            'team.partner_id' => $partner_id,
        );

        $model = D('Team');
        $total = $model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $field = array(
            'team.id' => 'id',
            'team.sort_order' => 'sort_order',
            'team.image' => 'image',
            'team.product' => 'product',
            'team.view_count_day' => 'view_count_day',
            'team.view_count' => 'view_count',
            'team.partner_id' => 'partner_id',
            'team.city_id' => 'city_id',
            'team.group_id' => 'group_id',
            'team.user_id' => 'user_id',
            'team.begin_time' => 'begin_time',
            'team.end_time' => 'end_time',
            'team.expire_time' => 'expire_time',
            'team.now_number' => 'now_number',
            'team.pre_number' => 'pre_number',
            'team.conduser' => 'conduser',
            'team.ucaii_price' => 'ucaii_price',
            'team.team_price' => 'team_price',
            'partner.title' => 'partner_title',
            'user.username' => 'user_username',
            'user.realname' => 'user_realname',
        );
        $list = $model->where($where)->field($field)->order('team.id desc')
                ->join("left join user on user.id=team.user_id")
                ->join("inner join partner on partner.id=team.partner_id")
                ->limit($limit)
                ->select();

        if ($list) {
            $now_time = time();
            foreach ($list as &$v) {
                $v['is_offine'] = 'N';
                if (isset($v['end_time']) && $v['end_time'] < $now_time) {
                    $v['is_offine'] = 'Y';
                }
            }
        }

        $this->_getCategoryList('group', array('fid' => 0));
        $data = array(
            'list' => $list,
            'pages' => $page->show(),
            'count' => $total,
            'city_list' => $city_list,
            'city_id' => $city_id,
            'partner_id' => $partner_id,
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 根据城市id获取商家列表
     */
    public function getPartnerListByCityId() {
        $city_id = I('post.city_id', '', 'trim');
        if (!$city_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '城市id不能为空！'));
        }
        $where = array(
            'city_id' => $city_id,
            'title' => array('neq', ''),
        );
        $partner_res = M('partner')->where($where)->select();
        $data = array();
        if ($partner_res) {
            foreach ($partner_res as $v) {
                $data[] = array(
                    'partner_id' => ternary($v['id'], ''),
                    'partner_username' => ternary($v['username'], ''),
                    'partner_title' => ternary($v['title'], ''),
                );
            }
        }
        $this->ajaxReturn(array('code' => 0, 'data' => $data));
    }

}
