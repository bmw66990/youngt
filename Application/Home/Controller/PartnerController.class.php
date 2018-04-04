<?php

namespace Home\Controller;

/**
 *  商家中心
 * Class CategoryController
 * @package Home\Controller
 */
class PartnerController extends CommonController {

    private $partner_info = array();
    private $city_info = array();

    /**
     * 构造方法
     */
    public function __construct() {
        parent:: __construct();

        // 获取商户信息
        $this->partner_info = $this->_get_partner_info();

        // 获取城市信息
        $this->city_info = $this->_getCityInfo();
    }

    /**
     * 获取商户信息
     */
    private function _get_partner_info() {

        $partner_id = I('get.partner_id', '', 'trim');
        if (trim(!$partner_id)) {
            return false;
        }

        $where = array(
            'id' => $partner_id,
        );
        $partner_info = M('partner')->where($where)->find();
        if (!$partner_info) {
            return array('error' => '该商户不存在！');
        }
        if (isset($partner_info['image']) && trim($partner_info['image'])) {
            $partner_info['image'] = getImagePath($partner_info['image']);
        }
        $partner_info['is_dingzuo'] = 0;
        $isDingzuo = M('dingzuo')->where(array('partner_id' => $partner_id))->count();
        if ($isDingzuo && $isDingzuo >= 0) {
            $partner_info['is_dingzuo'] = 1;
        }
        $partner_info['mobile']='';
        $this->assign('partner_info', $partner_info);
        return $partner_info;
    }

    /**
     * 商家首页
     */
    public function index() {

        $c_status = I('get.c_status', 0, 'intval');

        // 商家简介
        // 商家咨询
        // 商家相册
        $partner_image = array();
        $now_time = time();
        if (isset($this->partner_info['id']) && trim($this->partner_info['id'])) {
            $where = array(
                'partner_id' => $this->partner_info['id'],
                'begin_time' => array('lt', $now_time),
                'end_time' => array('gt', $now_time),
            );
            $team_res = M('team')->where($where)->field('image')->select();
            if ($team_res) {
                foreach ($team_res as &$v) {
                    if (isset($v['image']) && trim($v['image'])) {
                        $partner_image[] = getImagePath($v['image']);
                    }
                }
                unset($v);
            }
        }
        // 商家评价
        $where = array(
            'comment.partner_id' => $this->partner_info['id'],
            'comment.is_comment' => 'Y',
        );
        if ($c_status == 1) {
            $where['comment.comment_num'] = array('gt', 3);
        } elseif ($c_status == 2) {
            $where['comment.comment_num'] = array(array('elt', 3), array('gt', 1));
        } elseif ($c_status == 3) {
            $where['comment.comment_num'] = 1;
        } elseif ($c_status == 4) {
            $where['comment.is_pic'] = 'Y';
        }
        $comment = D('Comment');
        $count = $comment->where($where)->count();
        $page = $this->pages($count, 10);
        $partner_comment_list = $comment->getCommentList($where, '', $page->firstRow . ',' . $page->listRows);
        $partner_comment_list['page'] = $page->show();
        $data = array(
            'c_status' => $c_status,
            'partner_image' => $partner_image,
            'partner_comment_list' => $partner_comment_list
        );
        $this->_getWebTitle(array('title' => '商家中心-首页'));
        $this->assign($data);
        $this->display();
    }

    /**
     * 商家简介
     */
    public function partner_brief_introduction() {
        
    }

    /**
     * 商家动态
     */
    public function partner_dynamic() {
        
    }

    /**
     * 商家相册
     */
    public function partner_album() {
        // 商家相册
        $partner_image = array();
        $now_time = time();
        if (isset($this->partner_info['id']) && trim($this->partner_info['id'])) {
            $where = array(
                'partner_id' => $this->partner_info['id'],
                'begin_time' => array('lt', $now_time),
                'end_time' => array('gt', $now_time),
            );
            $team_res = M('team')->where($where)->field('image')->select();
            if ($team_res) {
                foreach ($team_res as &$v) {
                    if (isset($v['image']) && trim($v['image'])) {
                        $partner_image[] = getImagePath($v['image']);
                    }
                }
                unset($v);
            }
        }
        $data = array(
            'partner_image' => $partner_image,
        );
        $this->assign($data);
        $this->_getWebTitle(array('title' => '商家中心-商家相册'));
        $this->display();
    }

    /**
     * 商家地图
     */
    public function partner_map() {
        $this->_getWebTitle(array('title' => '商家中心-商家地图'));
        $this->display();
    }

    /**
     * 商家团单
     */
    public function partner_team_list() {
        
    }

    /**
     * 团购活动
     */
    public function group_buying_activity() {
        $partner_team_list = array();
        $now_time = time();
        $data = array();
        if (isset($this->partner_info['id']) && trim($this->partner_info['id'])) {
            $where = array(
                'partner_id' => $this->partner_info['id'],
                'begin_time' => array('lt', $now_time),
                'end_time' => array('gt', $now_time),
                'team_type' => array('in', array('goods', 'normal'))
            );
            $team = M('team');
            $count = $team->where($where)->count();
            $page = $this->pages($count, 10);
            $limit = $page->firstRow . ',' . $page->listRows;
            $partner_team_list = $team->where($where)->limit($limit)->field('id,product,title,now_number,team_price,image,market_price')->select();
            if ($partner_team_list) {
                foreach ($partner_team_list as &$v) {
                    if (isset($v['image']) && trim($v['image'])) {
                        $v['image'] = getImagePath($v['image']);
                    }
                }
                unset($v);
            }
            $data['page'] = $page->show();
        }
        $data['partner_team_list'] = $partner_team_list;
        $this->assign($data);
        $this->_getWebTitle(array('title' => '商家中心-团购活动'));
        $this->display();
    }

    /**
     * 客户服务
     */
    public function customer_service() {
        
    }

    /**
     * 用户点评
     */
    public function user_comments() {

        $c_status = I('get.c_status', 0, 'intval');
        // 商家评价
        $where = array(
            'comment.partner_id' => $this->partner_info['id'],
            'comment.is_comment' => 'Y',
        );
        if ($c_status == 1) {
            $where['comment.comment_num'] = array('gt', 3);
        } elseif ($c_status == 2) {
            $where['comment.comment_num'] = array(array('elt', 3), array('gt', 1));
        } elseif ($c_status == 3) {
            $where['comment.comment_num'] = 1;
        } elseif ($c_status == 4) {
            $where['comment.is_pic'] = 'Y';
        }
        $comment = D('Comment');
        $count = $comment->where($where)->count();
        $page = $this->pages($count, 10);
        $partner_comment_list = $comment->getCommentList($where, '', $page->firstRow . ',' . $page->listRows);
        $partner_comment_list['page'] = $page->show();

        $data = array(
            'c_status' => $c_status,
            'partner_comment_list' => $partner_comment_list
        );
        $this->assign($data);
        $this->_getWebTitle(array('title' => '商家中心-用户点评'));
        $this->display();
    }

    /**
     * 订座列表
     */
    public function reservation_index() {

        $zone_id = I('get.zone_id', '', 'trim');
        $username = I('get.username', '', 'trim');

        $list = $district = array();
        $is_index = 1;
        if (isset($this->city_info['id']) && trim($this->city_info['id'])) {
            $where = array(
                'city_id' => $this->city_info['id']
            );
            if ($zone_id) {
                if (intval($zone_id) > 0) {
                    $where['zone_id'] = $zone_id;
                }
                $is_index = 2;
            }
            if ($username) {
                $where['title'] = array('like', "%{$username}%");
                $is_index = 2;
            }
            $field = array(
                'id' => 'id',
                'partner_id' => 'partner_id',
                'title' => 'title',
                'image' => 'image',
                'is_hall_status' => 'is_hall_status',
                'is_box_status' => 'is_box_status',
            );
            $list = M('dingzuo')->where($where)->field($field)->limit(50)->order('sort_order desc,id desc')->select();
            if ($list) {
                $partner_ids = array();
                foreach ($list as &$v) {
                    if (isset($v['partner_id']) && trim($v['partner_id'])) {
                        $partner_ids[$v['partner_id']] = $v['partner_id'];
                    }
                }
                unset($v);
                $partner_info = $comment_info = array();
                if ($partner_ids) {
                    // 获取商家地址
                    $where = array(
                        'id' => array('in', array_keys($partner_ids))
                    );
                    $partner_info = M('partner')->where($where)->getField('id,address', true);

                    // 获取平均分
                    $where = array(
                        'partner_id' => array('in', array_keys($partner_ids)),
                        'is_comment' => 'Y',
                        'comment_display' => 'Y',
                        '_string' => 'comment_num IS NOT NULL'
                    );
                    $comment_info = M('comment')->where($where)->group('partner_id')->getField('partner_id,AVG(comment_num) as avg_comment_num', true);
                }
                foreach ($list as &$v) {
                    $v['address'] = ternary($partner_info[$v['partner_id']], '');
                    $v['avg_comment_num'] = sprintf("%.1f", ternary($comment_info[$v['partner_id']], '0.0'));
                    $v['image'] = getImagePath($v['image']);
                }
                unset($v);
            }

            // 获取商圈
            $district = $this->_getCategoryList('district', array('fid' => $this->city_info['id']));
        }

        $data = array(
            'zone_id' => $zone_id,
            'is_index' => $is_index,
            'username' => $username,
            'list' => $list,
            'district' => $district
        );
        
        $this->_getWebTitle(array('title' => '订座'));

        $this->assign($data);
        $this->display();
    }

    /**
     *  在线订座
     */
    public function online_reservation() {

        $where = array(
            'partner_id' => $this->partner_info['id'],
        );
        $field = array(
            'id' => 'id',
            'title' => 'title',
            'mobile' => 'mobile',
            'telphone' => 'telphone',
            'is_hall_status' => 'is_hall_status',
            'is_box_status' => 'is_box_status',
        );
        $partner_dingzuo_info = M('dingzuo')->where($where)->field($field)->find();

        if (IS_POST) {
            $time = I('post.time', '', 'trim');
            $num = I('post.num', '', 'trim');
            $username = I('post.username', '', 'trim');
            $mobile = I('post.mobile', '', 'trim');

            if (!$time) {
                $this->ajaxReturn(array('code' => -1, 'error' => '请选择订座时间！'));
            }
            if (!$num || intval($num)<=0) {
                $this->ajaxReturn(array('code' => -1, 'error' => '请输入订座人数！'));
            }
            if (!$username) {
                $this->ajaxReturn(array('code' => -1, 'error' => '请输入姓名！'));
            }
            if (!$mobile) {
                $this->ajaxReturn(array('code' => -1, 'error' => '请输入手机号码！'));
            }
            if (!checkMobile($mobile)){
                $this->ajaxReturn(array('code' => -1, 'error' => '手机号码格式错误！'));
            }
            
            if(!$partner_dingzuo_info){
                $this->ajaxReturn(array('code' => -1, 'error' => '该商家不支持订座！'));
            }
            
            if($partner_dingzuo_info['is_box_status']!=1 && $partner_dingzuo_info['is_hall_status'] !=1){
                $this->ajaxReturn(array('code' => -1, 'error' => '包厢与客厅已满，不能订座！'));
            }
            
            $data = array(
                'dz_id'=>$partner_dingzuo_info['id'],
                'partner_id'=>$this->partner_info['id'],
                'username'=>$username,
                'num'=>$num,
                'create_time'=>$time,
                'state'=>'Y',
                'mobile'=>$mobile,
            );
            $res = M('dz_order')->add($data);
            if(!$res){
                $this->ajaxReturn(array('code' => -1, 'error' => '订座失败！'));
            }
            // 通知商家短信
            if (isset($partner_dingzuo_info['telphone']) && trim($partner_dingzuo_info['telphone']) && checkMobile($partner_dingzuo_info['telphone'])) {
                $send_text     = "尊敬的" . $partner_dingzuo_info['title'] . "商户你好，青团用户：{$username}在青团预定你家{$num}人套餐请及时回复对接，订座人电话：{$mobile}，预定时间：{$time}" ;
                $this->_sms($partner_dingzuo_info['telphone'], $send_text);
            }
            $this->ajaxReturn(array('code' => 0, 'success' => '订座成功！')); 
        }

        $uid = $this->_getUserId();
        $user_info = array();
        if ($uid) {
            $user_info = M('user')->field('id,username,mobile')->where($where)->find();
        }

        $data = array(
            'user_info' => $user_info,
            'partner_dingzuo_info' => $partner_dingzuo_info,
        );
        $this->assign($data);
        
        $this->_getWebTitle(array('title' => '商家中心-在线订座'));

        $this->display();
    }

}
