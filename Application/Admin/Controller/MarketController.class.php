<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/6/16
 * Time: 9:56
 */

namespace Admin\Controller;

class MarketController extends CommonController {

    /**
     * 广告类型
     * @var array
     */
    private $admanageType = array(
        array('val' => 'pc', 'name' => '电脑首页轮播图'),
        array('val' => 'app', 'name' => 'APP广告图片'),
        array('val' => 'timelimit', 'name' => 'APP秒杀图片'),
        array('val' => 'limited', 'name' => 'APP限量图片'),
        array('val' => 'special_selling', 'name' => 'APP特卖图片'),
    );
    private $activities_show_type = array(
        array('val' => 'rectangle', 'name' => '长方形'),
        array('val' => 'square', 'name' => '正方形'),
        array('val' => 'ball', 'name' => '球型'),
    );
    public $typeshow=array(
        'rectangle'=>'长方形',
        'square'=>'正方形',
        'ball'=>'球形',
    );
    /**
     * 代金券管理
     */
    public function index() {
        $paramArray = array(
            array('team_id', '', '', 'c'),
            array('order_id', '', 'like', 'c'),
            array('code', '', '', 'c'),
            array('city_id', 0, '', 'c')
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);

        if (I('get.spay_time')) {
            $where['c.pay_time'][] = array('gt', strtotime(I('get.spay_time')));
            $displayWhere['spay_time'] = I('get.spay_time');
        }

        if (I('get.epay_time')) {
            $where['c.pay_time'][] = array('lt', strtotime(I('get.epay_time') . ' 23:59:59'));
            $displayWhere['epay_time'] = I('get.epay_time');
        }

        $consume = I('get.consume', '', 'strval');
        if ($consume) {
            $consume = strtoupper($consume);
            if ($consume == 'E') {
                $where['c.consume'] = 'N';
                $where['c.end_time'] = array('lt', time());
            } else {
                $where['c.consume'] = $consume;
                if ($consume == 'N') {
                    $where['c.end_time'] = array('gt', time());
                }
            }
            $displayWhere['consume'] = $consume;
        }
        $Model = D('Card');
        $count_where = $this->_createCountWhere($where);
        $count = $Model->getTotal($count_where);
        $sum_money = $Model->where($count_where)->sum('credit');
        $this->_writeDBErrorLog($count, $Model);
        $page = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $field = 'c.*,t.product';
        $data = $Model->getList($where, 'id desc', $limit, $field);
        $this->_writeDBErrorLog($data, $Model);
        $this->_getAllCate();
        $this->_getCardType();
        $this->assign('data', $data);
        $this->assign('sum_money', $sum_money);
        $this->assign('displayWhere', $displayWhere);
        $this->display();
    }

    /**
     * 删除代金券
     */
    public function delCard() {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $Model = D('Card');
            $count = $Model->getTotal(array('id' => $id));
            if ($count) {
                $res = $Model->delete($id);
                if ($res) {
                    $this->addOperationLogs("操作：删除代金券,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},代金券id:{$id}");
                    $this->redirect_message(U("Market/index"), array('success' => base64_encode('删除成功！')));
                } else {
                    $this->redirect_message(U("Market/index"), array('error' => base64_encode($Model->getError())));
                }
            } else {
                $this->redirect_message(U("Market/index"), array('error' => base64_encode('信息不存在!')));
            }
        } else {
            $this->redirect_message(U("Market/index"), array('error' => base64_encode('非法操作!')));
        }
    }

    /**
     * 广告管理
     */
    public function adManage() {
        $paramArray = array(
            array('id'),
            array('textarr', '', 'like'),
            array('type', '', ''),
            array('cityid', 0, '')
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $Model = D('Admanage');
        $count = $Model->getTotal($where);
        $this->_writeDBErrorLog($count, $Model);
        $page = $this->pages($count, $this->popup_reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $data = $Model->getList($where, 'sort_order desc,id desc', $limit);
        $this->_writeDBErrorLog($data, $Model);
        $this->_getAllCate('city');
        $this->assign('adManageType', $this->admanageType);
        $this->assign('data', $data);
        $this->assign('displayWhere', $displayWhere);
        $this->display();
    }

    /**
     * 添加广告模板
     */
    public function addAdManage() {
        $this->_getAllCate();
        $this->display();
    }

    /**
     * 添加广告逻辑处理
     */
    public function doAddAdManage() {
        if (IS_POST) {
            $Model = D('Admanage');
            $res = $Model->insert();
            if ($res) {
                $this->addOperationLogs("操作：添加广告,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},广告id:" . $res);
                $this->redirect_message(U("Market/adManage"), array('success' => base64_encode('添加成功!')));
            } else {
                $this->redirect_message(U("Market/adManage"), array('error' => base64_encode($Model->getError())));
            }
        } else {
            $this->redirect_message(U("Market/adManage"), array('error' => base64_encode('非法操作!')));
        }
    }

    /**
     * 编辑广告模板
     */
    public function editAdManage() {
        $id = I('get.id', 0, 'intval');
        if (!$id) {
            $this->redirect_message(U("Market/adManage"), array('error' => base64_encode('非法操作!')));
        }
        $Model = D('Admanage');
        $ad_info = $Model->info($id);
        $this->assign('image', $ad_info['picarr'] ? getImagePath($ad_info['picarr']) : '');
        $this->assign('ad_info', $ad_info);
        $this->_getAllCate();
        $this->display();
    }

    /**
     * 编辑广告逻辑处理
     */
    public function doEditAdManage() {
        if (IS_POST) {
            $Model = D('Admanage');
            $id = I('post.id', '', 'trim');
            $res = $Model->update();
            if ($res) {
                $this->addOperationLogs("操作：编辑广告,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},广告id:" . I('post.id'));
                $this->redirect_message(U("Market/adManage"), array('success' => base64_encode('编辑成功!')));
            } else {
                $this->redirect_message(U("Market/editAdManage", array('id' => $id)), array('error' => base64_encode($Model->getError())));
            }
        } else {
            $this->redirect_message(U("Market/adManage"), array('error' => base64_encode('非法操作')));
        }
    }

    /**
     * 删除广告
     */
    public function delAdManage() {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $Model = D('Admanage');
            $count = $Model->getTotal(array('id' => $id));
            if ($count) {
                $res = $Model->delete($id);
                if ($res) {
                    $this->addOperationLogs("操作：删除广告,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},广告id:{$id}");
                    $this->redirect_message(U("Market/adManage"), array('success' => base64_encode('删除成功')));
                } else {
                    $this->redirect_message(U("Market/adManage"), array('error' => base64_encode($Model->getError())));
                }
            } else {
                $this->redirect_message(U("Market/adManage"), array('error' => base64_encode('信息不存在')));
            }
        } else {
            $this->redirect_message(U("Market/adManage"), array('error' => base64_encode('非法操作')));
        }
    }

    /**
     * 获取关高广告分类城市列表
     */
    public function _getAllCate($val = '') {
        $all_city = $this->_getCategoryList('city');
        $this->assign('all_city', $all_city);
        $adManageType = $this->admanageType;
        $this->assign('adManageType', $adManageType);
        $activities_show_type = $this->activities_show_type;
        $this->assign('activities_show_type', $activities_show_type);
        
    }

    /**
     * 活动管理
     */
    public function activities() {
        $paramArray = array(
            array('id'),
            array('textarr', '', 'like'),
            array('cityid', 0, ''),
            array('show_type', '', '')
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $Model = D('Admanage');
        $where['type'] = 'activities';
        $count = $Model->getTotal($where);
        $this->_writeDBErrorLog($count, $Model);
        $page = $this->pages($count, $this->popup_reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $data = $Model->getList($where, 'sort_order desc,id desc', $limit);

        foreach ($data as &$val) {
            $val['show_type']=$this->typeshow[$val['show_type']];
        }
        $this->_writeDBErrorLog($data, $Model);
        $this->_getAllCate('city');
        $this->assign('data', $data);
        $this->assign('displayWhere', $displayWhere);
        $this->display();
    }

    /**
     * 添加活动模板
     */
    public function addActivities() {
        $this->_getAllCate();
        $this->display();
    }

    /**
     * 添加活动逻辑处理
     */
    public function doAddActivities() {
        if (IS_POST) {
            $Model = D('Admanage');
            $_POST['type'] = 'activities';
            $res = $Model->insert();
            if ($res) {
                $this->addOperationLogs("操作：添加广告,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},广告id:" . $res);
                $this->redirect_message(U("Market/activities"), array('success' => base64_encode('添加成功!')));
            } else {
                $this->redirect_message(U("Market/addActivities"), array('error' => base64_encode($Model->getError())));
            }
        } else {
            $this->redirect_message(U("Market/activities"), array('error' => base64_encode('非法操作!')));
        }
    }

    /**
     * 编辑活动模板
     */
    public function editActivities() {
        $id = I('get.id', 0, 'intval');
        if (!$id) {
            $this->redirect_message(U("Market/activities"), array('error' => base64_encode('非法操作!')));
        }
        $Model = D('Admanage');
        $ad_info = $Model->info($id);
        $this->assign('image', $ad_info['picarr'] ? getImagePath($ad_info['picarr']) : '');
        $this->assign('image1', $ad_info['pic'] ? getImagePath($ad_info['pic']) : '');
        $this->assign('ad_info', $ad_info);
        $this->_getAllCate();
        $this->display();
    }

    /**
     * 编辑活动逻辑处理
     */
    public function doEditActivities() {
        if (IS_POST) {
            $Model = D('Admanage');
            $_POST['type'] = 'activities';
            $id = I('post.id', '', 'trim');
            $res = $Model->update();
            if ($res) {
                $this->addOperationLogs("操作：编辑广告,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},广告id:" . I('post.id'));
                $this->redirect_message(U("Market/activities"), array('success' => base64_encode('编辑成功!')));
            } else {
                $this->redirect_message(U("Market/editActivities", array('id' => $id)), array('error' => base64_encode($Model->getError())));
            }
        } else {
            $this->redirect_message(U("Market/activities"), array('error' => base64_encode('非法操作')));
        }
    }

    /**
     * 删除活动
     */
    public function delActivities() {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $Model = D('Admanage');
            $count = $Model->getTotal(array('id' => $id));
            if ($count) {
                $res = $Model->delete($id);
                if ($res) {
                    $this->addOperationLogs("操作：删除广告,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},广告id:{$id}");
                    $this->redirect_message(U("Market/activities"), array('success' => base64_encode('删除成功')));
                } else {
                    $this->redirect_message(U("Market/activities"), array('error' => base64_encode($Model->getError())));
                }
            } else {
                $this->redirect_message(U("Market/activities"), array('error' => base64_encode('信息不存在')));
            }
        } else {
            $this->redirect_message(U("Market/activities"), array('error' => base64_encode('非法操作')));
        }
    }

    /**
     * 代金券状态
     */
    public function _getCardType() {
        $state = array('N' => '未使用', 'Y' => '已使用', 'E' => '已过期');
        $this->assign('state', $state);
    }

    /**
     * 发送短信
     */
    public function smsUser() {
        $this->display();
    }

    /**
     * 发送短信处理
     */
    public function doSmsUser() {
        $mobile = I('post.mobile', '', 'trim');
        $content = I('post.content', '', 'trim');
        //检测手机号码
        if ($mobile) {
            $mobile_arr = explode(',', $mobile);
            foreach ($mobile_arr as $val) {
                if (checkMobile($val) === false) {
                    $this->redirect_message(U('Market/smsUser'), array('error' => base64_encode($val . '--手机号码格式有误')));
                }
            }
        } else {
            $this->redirect_message(U('Market/smsUser'), array('error' => base64_encode('请输入手机号码')));
        }
        //检测短信内容
        if ($content == '') {
            $this->redirect_message(U('Market/smsUser'), array('error' => base64_encode('短信内容不能为空')));
        }
        $sendSms = new \Common\Org\sendSms();
        //发送短信
        $res = $sendSms->sendMsg($mobile, $content, 'Ymsms', 'admin');
        if ($res['status'] == -1) {
            $this->redirect_message(U('Market/smsUser'), array('error' => base64_encode($res['data'])));
        } else {
            $this->redirect_message(U('Market/smsUser'), array('success' => base64_encode('发送成功')));
        }
    }

    /**
     * 分销订单列表
     */
    public function shareOrder(){
        $Model = M('order');
        $id = I('get.id','','int');
        $where =  array('o.openid'=>array('neq',''),'o.state'=>'pay');
        $whereCount = array('openid'=>array('neq',''),'state'=>'pay');
        if($id){
            $where['o.id'] = $id;
            $whereCount['id'] = $id;
        }
        $count = $Model->where($whereCount)->count('id');
        $page = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $field = 'o.id,t.product,u.username,o.quantity,o.origin,o.money,o.laiyuan,o.yuming,wx.weixinname';
        $join = array(
            'left join team t ON o.team_id=t.id',
            'left join user u ON o.user_id=u.id',
            'left join weixin_sy wx ON o.openid=wx.openid ',
        ) ;
        $data = $Model->alias('o')->join($join)->field($field)->where($where)->order('id desc')->limit($limit)->select();
        $this->assign('data',$data);
        $this->assign('where_id',$id);
        $this->display();
    }

    /**
     * 分销统计
     */
    public function shareCount(){
        $Model = M('wx_share_packet');
        $username = I('get.username','','trim');
        $product = I('get.product','','trim');
        $start_time = I('get.start_time','','trim');
        $end_time = I('get.end_time','','trim');
        $where = $displayWhere = array();
        if($username){
            $where['u.username'] = $username;
            $displayWhere['username'] = $username;
        }
        if($product){
            $where['t.product'] = $product;
            $displayWhere['product'] = $product;
        }
        if($start_time && $end_time){
            $where['w.add_time'] = array('between',array(strtotime($start_time),strtotime($end_time)));
            $displayWhere['start_time'] = $start_time;
            $displayWhere['end_time'] = $end_time;
        }
        $field = 'w.*,u.username,t.product';
        $join = array(
            'left join user u ON u.id = w.user_id',
            'left join team t ON t.id = w.team_id',
            );

        $count['teams'] = $Model->alias('w')->join($join)->where($where)->group('w.team_id')->count('w.team_id');
        $count['nums']  = $Model->alias('w')->join($join)->where($where)->count('w.id');
        $count['money'] = $Model->alias('w')->join($join)->where($where)->sum('w.packet_money');

        $page = $this->pages($count['nums'], $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = $Model->alias('w')->field($field)->join($join)->where($where)->order('w.id desc')->limit($limit)->select();
        $this->assign('pages', $page->show());

        $this->assign(array(
            'display_where'=>$displayWhere,
            'count'=>$count,
            'data'=>$data
        ));

        $this->display();
    }

}
