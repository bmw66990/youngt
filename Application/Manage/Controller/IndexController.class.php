<?php

namespace Manage\Controller;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class IndexController extends CommonController {

    /**
     * 当天开始时间戳
     * @var
     */
    protected $_stime;

    /**
     * 当天结束时间戳
     * @var
     */
    protected $_etime;

    public function __construct() {
        parent::__construct();
        $this->_stime = strtotime(date('Y-m-d'));
        $this->_etime = $this->_stime + 86399;
    }

    /**
     * 首页
     */
    public function index() {
        $this->display();
    }
    /**
     * 获取新用户总数
     */
    public function getRegUserNum() {
        $regUser = D('User')->newRegUserCount($this->_getCityId(), $this->_stime, $this->_etime, 'daytotal');
        $this->_writeDBErrorLog($regUser, D('User'));
        $data['html'] = $regUser;
        $this->ajaxReturn($data);
    }

    /**
     * 获取订单总数
     */
    public function getOrderNum() {
        $orderModel = D('Order');
        $orderNum = $orderModel->orderNumCount($this->_getCityId(), $this->_stime, $this->_etime, 'daytotal');
        $this->_writeDBErrorLog($orderNum, $orderModel);
        $data['html'] = $orderNum;
        $this->ajaxReturn($data);
    }

    /**
     * 获取退款申请总数
     */
    public function getRefundNum() {
        $orderModel = D('Order');
        $refundNum = $orderModel->orderRefundCount($this->_getCityId(), $this->_stime, $this->_etime, 'daytotal');
        $this->_writeDBErrorLog($refundNum, $orderModel);
        $data['html'] = $refundNum;
        $this->ajaxReturn($data);
    }
    /**
     * 获取过期团单总数
     */
    public function getOverdueNum() {
        $stime = strtotime(date('Y-m-d', strtotime('+7 days')));
        $statime=time();
        $teamModel = M('team');
        $where['city_id']=$this->_getCityId();
        $where['end_time']=  array(between,array($statime,$stime));
        $refundNum = $teamModel->where($where)->count('id');
        $data['html'] = $refundNum;
        $this->ajaxReturn($data);
    }
    /**
     * 获取接待量总数
     */
    public function getReceptionNum() {
        $mapping = array(
            'p.city_id' => $this->_getCityId(),
            'c.consume' => 'Y',
            'c.consume_time' => array('between', array($this->_stime, $this->_etime)),
        );
        $receptionNum = D('Coupon')->getCount($mapping);
        $this->_writeDBErrorLog($receptionNum, D('Coupon'));
        $receptionNum = ternary($receptionNum, 0);
        $data['html'] = $receptionNum;
        $this->ajaxReturn($data);
    }

    /**
     * 订单数据实时监控
     */
    public function getOrderCount() {
        $city_id = $this->_getCityId();
        $stime = strtotime(date('Y-m-d', strtotime('-1 days')));
        $etime = $stime + 2 * 86400 - 1;
        $data = D('Order')->orderNumCount($city_id, $stime, $etime, 'day');
        $count = array();
        while ($stime < $etime) {
            $row = date('Y-m-d', $stime);
            $svtime = $stime;
            $evtime = $stime + 86400;
            $tmp = array();
            while ($svtime < $evtime) {
                $val = date('Y-m-d H', $svtime);
                $tmp[] = (int) ternary($data['pay'][$val], 0);
                $svtime = strtotime('+1 hours', $svtime);
            }
            $name = date('Y-m-d', $stime);
            if ($name == date('Y-m-d')) {
                $name = '今天';
            } else if ($name = date('Y-m-d', strtotime('-1 day'))) {
                $name = '昨天';
            }
            $dateInfo[] = $name;
            $count[] = array(
                'name' => $name,
                'type' => 'line',
                'stack' => '订单数',
                'data' => $tmp
            );
            $stime = strtotime('+1 day', $stime);
        }

        //获取7天前的数据
        $stime = strtotime(date('Y-m-d', strtotime('-7 days')));
        $etime = $stime + 86399;
        $data = D('Order')->orderNumCount($city_id, $stime, $etime, 'day');
        $tmp = array();
        while ($stime < $etime) {
            $val = date('Y-m-d H', $stime);
            $tmp[] = (int) ternary($data['pay'][$val], 0);
            $stime = strtotime('+1 hours', $stime);
        }
        $dateInfo[] = '7天前';
        $count[] = array(
            'name' => '7天前',
            'type' => 'line',
            'stack' => '订单数',
            'data' => $tmp
        );

        //获取一个月前的数据
        $stime = strtotime(date('Y-m-d', strtotime('-1 month')));
        $etime = $stime + 86399;
        $data = D('Order')->orderNumCount($city_id, $stime, $etime, 'day');
        $tmp = array();
        while ($stime < $etime) {
            $val = date('Y-m-d H', $stime);
            $tmp[] = (int) ternary($data['pay'][$val], 0);
            $stime = strtotime('+1 hours', $stime);
        }
        $dateInfo[] = '1月前';
        $count[] = array(
            'name' => '1月前',
            'type' => 'line',
            'stack' => '订单数',
            'data' => $tmp
        );
        $return = array(
            'dataList' => $count,
            'dateList' => $dateInfo
        );
        $this->ajaxReturn($return);
    }

    /**
     * 订单来源
     */
    public function getOrderSouce() {
        $day = date('Y-m-d', $this->_stime);
        $city_id = $this->_getCityId();
        $stime = $this->_stime;
        $etime = $this->_etime;
        $data = D('Order')->sourceNumCount($city_id, $stime, $etime);
        $count = array();
        if ($data) {
            $pc = ternary($data['pc'][$day], 0);
            $wap = ternary($data['m.youngt.com'][$day], 0);
            $android = ternary($data['android'][$day], 0) + ternary($data['newandroid'][$day], 0);
            $ios = ternary($data['ios'][$day], 0) + ternary($data['newios'][$day], 0);
            $total = $pc + $wap + $android + $ios;
            $count[0]['label'] = 'andriod';
            $count[0]['value'] = sprintf('%.2f', $android / $total * 100);
            $count[1]['label'] = 'IOS';
            $count[1]['value'] = sprintf('%.2f', $ios / $total * 100);
            $count[2]['label'] = 'wap';
            $count[2]['value'] = sprintf('%.2f', $wap / $total * 100);
            $count[3]['label'] = 'pc';
            $count[3]['value'] = sprintf('%.2f', $pc / $total * 100);
        }
        $this->ajaxReturn(array('data' => $count));
    }

    /**
     * 获取接待量排行
     */
    public function getReception() {
        $partnerModel = D('Partner');
        $where = array(
            'p.city_id' => $this->_getCityId(),
            'c.consume_time' => array('between', array($this->_stime, $this->_etime)),
        );
        $reception = $partnerModel->getReceptionList($where, 8);
        $this->_writeDBErrorLog($reception, $partnerModel);
        $partnerId = '';
        foreach ($reception as $row) {
            $partnerId .= $row['partner_id'] . ',';
        }
        if (!empty($partnerId)) {
            $partnerId = substr($partnerId, 0, -1);
            $map = array(
                'partner_id' => array('IN', $partnerId),
                'create_time' => array('between', array($this->_stime, $this->_etime)),
            );
            $totalMoney = $partnerModel->getPartnerMoney($map);
            $this->_writeDBErrorLog($totalMoney, M('PartnerIncome'));

            $con = array(
                'c.partner_id' => array('IN', $partnerId),
                'c.consume' => 'Y',
                'c.consume_time' => array('between', array($this->_stime, $this->_etime))
            );
            $totalProfit = $partnerModel->getPartnerProfit($con, 'c.partner_id');
            $this->_writeDBErrorLog($totalProfit, $partnerModel);
        }

        $str = '';
        foreach ($reception as $row) {
            $str .= '<tr>
                        <td>' . $row['username'] . '</td>
                        <td>' . $row['num'] . '</td>
                        <td>' . ternary($totalMoney[$row['partner_id']], 0) . '</td>
                        <td>' . ternary($totalProfit[$row['partner_id']], 0) . '</td>
                    </tr>';
        }
        $data['html'] = $str;
        $this->ajaxReturn($data);
    }

    /**
     * 获取反馈
     */
    public function getFeedback() {
        $where = array(
            'admin_id' => array('NEQ', 0),
            'answer' => array('NEQ', ''),
            'status' => 'reply'
        );
        $model = D('FeedbackQuestion');
        $list = $model->getList($where, 'id DESC', 6);
        $cityList = $this->_getCategoryList('city');
        $cateList = $this->_getCategoryList('feedback');

        $userId = array();
        foreach ($list as $row) {
            $userId[] = $row['user_id'];
        }
        $userList = array();
        if ($userId) {
            $map = array(
                'id' => array('IN', array_unique($userId))
            );
            $userList = M('User')->where($map)->getField('id,realname', true);
        }
        $str = '';
        foreach ($list as $row) {
//          $str .= '<li class="listyle">
//                      <span class="ask">[' . $cityList[$row['city_id']]['name'] . '-' . $userList[$row['user_id']] . ']' . $row['content'] . '<label>' . $cateList[$row['cid']]['name'] . '</label></span>
//                      <span class="answer">回复：' . $row['answer'] . '</span>
//                  </li>';
			$str .='<p><span class="label label-success">'.$cateList[$row['cid']]['name'].'</span>  
            		<i class="text-warning">[' . $cityList[$row['city_id']]['name'].'-'.$userList[$row['user_id']].' ]</i>'.$row['content'].'</p>
                <div class="alert alert-warning">'. $row['answer'].'</div>';
        }
        $data['html'] = $str;
        $this->ajaxReturn($data);
		
    }

    /**
     * 绑定APP分类
     */
    public function bindCategory() {
        if (IS_POST) {
            $this->_checkblank('catelist');
            $catelist = I('post.catelist');
            $sort = I('post.sort');
            if (count($catelist) == 8 || count($catelist) == 16) {
                // code
            } else {
                $this->error('一定要选择8个或16个分类');
            }
            $sortList = array();
            foreach ($catelist as $k => $v) {
                $sortList[$v] = ternary($sort[$k], 0);
            }
            arsort($sortList);
            $group = $this->_getCategoryList('group');
            $list = array();
            foreach ($sortList as $key => $row) {
                $list[] = array(
                    'id' => $key,
                    'fid' => $group[$key]['fid'],
                    'name' => $group[$key]['name'],
                    'sort' => $row
                );
            }
            $map = array(
                'city_id' => $this->_getCityId()
            );
            // 如果存在,直接更新
            $model = M('app_category');
            if ($model->where($map)->count() > 0) {
                $data = array(
                    'cate' => serialize($list)
                );
                $res = $model->where($map)->save($data);
            } else {
                $data = array(
                    'city_id' => $this->_getCityId(),
                    'cate' => serialize($list)
                );
                $res = $model->add($data);
            }
            if ($res!==false) {
                $this->success('绑定成功');
            } else {
                $this->error('绑定失败');
            }
        } else {
            $map = array(
                'city_id' => $this->_getCityId()
            );
            $vo = M('app_category')->where($map)->find();
            if ($vo) {
                $data = unserialize($vo['cate']);
                $useCate = $sort = array();
                foreach ($data as $row) {
                    $useCate[] = $row['id'];
                    $sort[$row['id']] = $row['sort'];
                }
                $this->assign('useCate', $useCate);
                $this->assign('sort', $sort);
            }
            $imgUrl = 'http://pic.youngt.com/static/cateimg/spring_image/';
            $imgUrl = "http://pic.youngt.com/static/newcate/";
            $list = array(
                array(
                    'id' => 255,
                    'name' => '美食',
                    'url' => $imgUrl . 'meishi.png'
                ),
                array(
                    'id' => 1560,
                    'name' => '代金券',
                    'url' => $imgUrl . 'daijingquan.png'
                ),
                array(
                    'id' => 12,
                    'name' => '娱乐',
                    'url' => $imgUrl . 'xiuxianyule.png'
                ),
                array(
                    'id' => 419,
                    'name' => '生活服务',
                    'url' => $imgUrl . 'shenghuofuw.png'
                ),
                array(
                    'id' => 14,
                    'name' => '美容保健',
                    'url' => $imgUrl . 'meirongbaojian.png'
                ),
                array(
                    'id' => 404,
                    'name' => '旅游酒店',
                    'url' => $imgUrl . 'lvyoujiudian.png'
                ),
                array(
                    'id' => 72,
                    'name' => '其他',
                    'url' => $imgUrl . 'qita.png'
                ),
                array(
                    'id' => 587,
                    'name' => '自助餐',
                    'url' => $imgUrl . 'zizhucan.png'
                ),
                array(
                    'id' => 589,
                    'name' => '烧烤',
                    'url' => $imgUrl . 'shaokao.png'
                ),
                array(
                    'id' => 418,
                    'name' => '火锅',
                    'url' => $imgUrl . 'huoguo.png'
                ),
                array(
                    'id' => 256,
                    'name' => 'KTV',
                    'url' => $imgUrl . 'ktv.png'
                ),
                array(
                    'id' => 412,
                    'name' => '摄影写真',
                    'url' => $imgUrl . 'sheying.png'
                ),
                array(
                    'id' => 417,
                    'name' => '西餐',
                    'url' => $imgUrl . 'xican.png'
                ),
                array(
                    'id' => 608,
                    'name' => '鲜花婚庆',
                    'url' => $imgUrl . 'flower.png'
                ),
                array(
                    'id' => 420,
                    'name' => '电影',
                    'url' => $imgUrl . 'dianying.png'
                ),
                array(
                    'id' => 425,
                    'name' => '足疗洗浴',
                    'url' => $imgUrl . 'zuyu.png'
                ),
                array(
                    'id' => 2060,
                    'name' => '快餐',
                    'url' => $imgUrl . 'kuaican.png'
                ),
                array(
                    'id' => 427,
                    'name' => '小吃',
                    'url' => $imgUrl . 'xiaochi.png'
                )
            );
            $list = array_chunk($list, 4);
            $this->assign('list', $list);
            $this->display();
        }
    }

    /**
     * 消息推送页面
     */
    public function appPush() {
        $pushRedis = S('pushRedis');
        if ($pushRedis) {
            if (isset($pushRedis[$this->_getCityId()]) && $pushRedis[$this->_getCityId()]['date'] == date('Y-m-d')) {
                $this->error('每天仅限推送一次，请明天再来吧！', U('Index/appPushDone'));
            }
        }
        $this->display();
    }

    /**
     * 推送团单
     */
    public function pushTeam() {
        $where = $this->_getTeamWhere($this->_getCityId());
        $id = I('get.id', 0, 'intval');
        $key = I('get.key', '', 'strval');
        if ($id) {
            $where['id'] = $id;
            $searchValue['id'] = $id;
        }
        if ($key) {
            $where['title|product|sel3|sel2|sel1'] = array('like', "%{$key}%");
            $searchValue['key'] = $key;
        }
        if(isset($where['team_type'])){
            unset($where['team_type']);
        }
        $team = D('Team')->getList($where, 'sort_order desc', '', 'id, product, team_price');
        $this->assign('searchValue', $searchValue);
        $this->assign('team', $team);
        $this->display();
    }

    /**
     * 消息推送处理
     */
    public function doAppPush() {
        if (IS_POST) {
            $type = I('post.type', '', 'trim');
            $pushTime = date('Y-m-d H:i:s');
            $time = I('post.time');
            if ($time) {
                $pushTime = date('Y-m-d') . ' ' . $time;
            }
            if ($type) {
                if ($type == 'team') {
                    $team_ids = I('post.team_ids');
                    if (!$team_ids) {
                        $this->error('请选择推送团单后再操作');
                    }
                    $this->_pushTeam($pushTime);
                } else if ($type == 'content') {
                    $content = I('post.content', '', 'trim');
                    if (!$content) {
                        $this->error('请输入推送后再操作');
                    }
                    $this->_pushContent($pushTime);
                } else {
                    $this->error('推送方式不合法');
                }
                $pushRedis = array($this->_getCityId() => array('date' => date('Y-m-d')));
                S('pushRedis', $pushRedis);
                $this->success('推送成功', U('Index/appPush'));
            } else {
                $this->error('请选择推送方式');
            }
        } else {
            $this->error('非法请求');
        }
    }

    /**
     * 团单推送
     */
    protected function _pushTeam($pushTime) {
        $teamArr = explode(',', I('post.team_ids'));
        if ($teamArr) {
            foreach ($teamArr as $val) {
                $team_info = M('team')->field('product,title')->find($val);
                $pushData = array(
                    'title' => $team_info['product'],
                    'content' => $team_info['title'],
                    'tags' => array($this->_getCityId()),
                    'custom' => array('type' => 'team_detail', 'data' => array('id' => $val)),
                    'send_time' => $pushTime,
                );
                foreach (array('android', 'ios') as $v) {
                    $pushData['plat'] = $v;
                    $this->_pushMessageToTags($pushData);
                }
            }
        } else {
            $this->error('推送数据不合法');
        }
    }

    /**
     * 自定义内容推送
     */
    protected function _pushContent($pushTime) {
        $content = I('post.content', '', 'trim');
        $pushData = array(
            'title' => '青团网',
            'content' => $content,
            'tags' => array($this->_getCityId()),
            'send_time' => $pushTime,
            'message_type' => 1,
        );
        foreach (array('android', 'ios') as $v) {
            $pushData['plat'] = $v;
            $this->_pushMessageToTags($pushData);
        }
    }

    /**
     * APP订座开启关闭
     */
    public function ticketShow() {
        $Model = M('city_model_show');
        $where = array('city_id' => $this->_getCityId(), 'model_name' => 'dingzuo');
        $count = $Model->where($where)->count();
        $is_show = I('get.is_show');
        if ($is_show == 'Y' && !$count) {
            $data = array(
                'city_id' => $this->_getCityId(),
                'admin_id' => $this->_getUserId(),
                'model_name' => 'dingzuo',
                'create_time' => time(),
                'is_show' => 'Y',
            );
            $model_id = $Model->add($data);
            if ($model_id) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        } else {
            $up_data = array('is_show' => $is_show);
            $res = $Model->where($where)->save($up_data);
            if ($res) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
    }

}
