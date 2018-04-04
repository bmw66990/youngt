<?php

namespace Home\Controller;

/**
 * 首页数据
 * Class IndexController
 * @package Home\Controller
 */
class IndexController extends CommonController {

    /**
     * 是否启用新模板
     * @var bool
     */
    protected $isNewView = true;

    /**
     * @var array
     */
    private $cateTeamType = array('delicacy' => 255, 'amusement' => 12, 'movie' => array('gid' => 12, 'sid' => 420), 'hotel' => 404,'sales' => 1618 );
    private $cateTeamPage = array('delicacy' => 8, 'amusement' => 8, 'movie' => 4, 'hotel' => 4,'sales' => 5);
    private $cateTeamTypeyl = array('delicacy' => 255, 'amusement' => 12, 'movie' => array('gid' => 12, 'sid' => 420), 'hotel' => 404,'beauty' => 14,'sales' => 1618 );
    private $cateTeamPageyl = array('delicacy' => 8, 'amusement' => 8, 'movie' => 4, 'hotel' => 4,'beauty' =>4,'sales' => 4 );

    /**
     * 首页
     */
    public function index() {
        $this->_getWebTitle(array('title' => '首页'));
        $this->display();
    }

    /**
     * 异步换取首页显示方式
     */
    public function ajaxIndexTpl() {
        $city = $this->_getCityInfo();
        $config = C('INDEX_VIEW');
        if ($this->isNewView && in_array($city['id'], $config)) {
            $view = 1;
        } else {
            $view = 0;
        }
        $this->ajaxReturn($view);
    }

    /**
     * 异步获取分页参数
     */
    public function ajaxIndexTeamTotal() {
        $city = $this->_getCityInfo();
        $team_where = $this->_getTeamWhere($city['id']);
        $Model = D('Team');
        // 获取记录总数
        $teamTotal = $Model->getTotal($team_where);
        $pageSize = 120;
        if ($teamTotal > $pageSize) {
            $teamTotal = $pageSize;
        }
        $this->ajaxReturn($teamTotal);
    }

    /**
     * ajax获取当前所在城市名
     */
    public function ajaxCityName() {
        $city = $this->_getCityInfo();
        $this->ajaxReturn(array('id' => $city['id'], 'name' => $city['name']));
    }

    /**
     * 异步获取附近城市
     */
    public function ajaxAroundCity() {
        if (IS_AJAX) {
            $cityInfo = $this->_getCityInfo();
            $city = $this->_getCategoryList('city');
            $aroundCity = array();
            foreach ($city as $val) {
                if ($val['czone'] == $cityInfo['czone'] && $val['id'] != $cityInfo['id']) {
                    $aroundCity[] = $val;
                }
            }
            if (count($aroundCity) > 10) {
                $num = count($aroundCity) - 10;
                $start_num = rand(0, $num);
                $end_num = $start_num + 10;
                for ($i = $start_num; $i <= $end_num; $i++) {
                    $newData[] = $aroundCity[$i];
                }
                $data = array('status' => 1, 'data' => $newData);
            } else {
                $data = array('status' => 1, 'data' => $aroundCity);
            }
        } else {
            $data = array('status' => -1, 'error' => '非法操作');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 获取周边城市
     */
    public function ajaxPeripheryCity() {
        if (IS_AJAX) {
            $city_info = cookie('city');
            $this->assign('cityInfo', $city_info);

            // 首页热门城市
            $periphery_city = $this->_peripheryCity($city_info['czone'], $city_info['id']);
            $this->ajaxReturn(array('code' => 0, 'data' => $periphery_city));
        }
        $this->ajaxReturn(array('code' => -1, 'error' => '非法操作'));
    }

    /**
     * 异步获取商圈
     */
    public function ajaxDistrict() {
        if (IS_AJAX) {
            $district = $this->_getCategoryList('district', array('fid' => $this->_getCityInfo()['id']));
            $data = array('status' => 1, 'data' => $district, 'count' => count($district));
        } else {
            $data = array('status' => -1, 'error' => '非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * ajax获取首页轮播图数据
     * @param $city
     */
    public function ajaxAdManage() {
        if (IS_AJAX) {
            // 获取当前所在城市信息
            $city = $this->_getCityInfo();
            if ($city['id']) {
                $adManage = S('admanage');
                if (isset($adManage[$city['id']])) {
                    $slides = $adManage[$city['id']];
                } else {
                    $Model = D('Admanage');
                    $slides = $Model->getAdManageList($city['id']);
                    foreach ($slides as &$val) {
                        $val['picarr'] = getImagePath($val['picarr']);
                    }
                    S("admanage", array($city['id'] => $slides));
                }
                $data = array('status' => 1, 'data' => array_values($slides));
            } else {
                $data = array('status' => -1, 'error' => '参数错误');
            }
        } else {
            $data = array('status' => -1, 'error' => '非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 异步获取团单
     */
    public function ajaxTeam() {
        if (IS_AJAX) {
            $list = $this->_getSearchTeam();
            if ($list === false) {
                $list = $this->_getDbTeam();
            }
            $data = array('status' => 1, 'data' => array_chunk($list, 4));
        } else {
            $data = array('status' => -1, 'error' => '非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 异步获取分类团单
     */
    public function ajaxCateTeam() {
        if (IS_AJAX) {
            $city = $this->_getCityInfo();
            if($city['id']==1){
                $cateTeam = $this->cateTeamTypeyl;
            }else{
                $cateTeam = $this->cateTeamType;
            }
            foreach ($cateTeam as $key => $val) {
                $list[$key] = $this->_getSearchTeam($key, $val);
                if ($list[$key] === false) {
                    $list[$key] = $this->_getDbTeam($key, $val);
                }
                if (!empty($list[$key])) {
                    $list[$key]['count'] = count($list[$key]);
                }
            }
            $data = array('status' => 1, 'data' => $list);
        } else {
            $data = array('status' => -1, 'error' => '非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 从搜索服务中获取团单
     * @param null $key
     * @param null $val
     */
    protected function _getSearchTeam($key = null, $val = null) {
        // 获取当前所在城市信息
        $city = $this->_getCityInfo();
        // 当前时间戳
        $nowTime = time();
        $query = "team_type:'normal'";
        $search_where = "city_id = {$city['id']} AND begin_time < '{$nowTime}' AND end_time >= '{$nowTime}' ";
        $search_orderBy = array('sort_order' => '-', 'id' => '-');
        if($city['id']==1){
            $catePage = $this->cateTeamPageyl;
        }else{
            $catePage = $this->cateTeamPage;
        }

        $first = I('get.offset', 0, 'intval');
        $pageSize = I('get.num', 10, 'intval');
        if ($key) {
            if ($key == 'movie') {
                $search_where .= "AND group_id = {$val['gid']} AND sub_id = {$val['sid']}";
            } else {
                $search_where .= "AND group_id = $val";
                if ($key == 'amusement') {
                    $search_where .= "AND sub_id != 420";
                }
            }
            $first = 0;
            $pageSize = $catePage[$key];
        }
        $data = $this->_Search($query, $search_where, $search_orderBy, $first, $pageSize);
        if ($data) {
            foreach ($data as $key => &$val) {
                $val['image'] = getImagePath($val['image']);
                $promotion = unserialize($val['promotion']);
                $val['not_time'] = 0;
                $val['all_type'] = 0;
                $val['today'] = 0;
                $val['muslim'] = 0;
                if ($promotion && in_array('Q', $promotion)) {
                    $val['muslim'] = 1;
                }
                if ($promotion && in_array('M', $promotion)) {
                    $val['not_time'] = 1;
                }
                if ($promotion && in_array('D', $promotion)) {
                    $val['all_type'] = 1;
                }

                if (date('Y-m-d', $val['begin_time']) == date('Y-m-d')) {
                    $val['today'] = 1;
                }
            }
            return $data;
        }
        return false;
    }

    /**
     * 从数据库中直接获取图单数据
     * @param null $key
     * @param null $val
     */
    protected function _getDbTeam($key = null, $val = null) {
        $city = $this->_getCityInfo();
        $team_where = $this->_getTeamWhere($city['id']);
        $order = 'sort_order DESC , id DESC';
        $limit = $limit = I('get.offset', 0, 'intval') . ',' . I('get.num', 10, 'intval');
        $field = 'id,title,image,product,team_price,market_price,now_number,view_count,partner_id,max_number,promotion,begin_time';
        if($city['id']==1){
            $catePage = $this->cateTeamPageyl;
        }else{
            $catePage = $this->cateTeamPage;
        }

        if ($key) {
            if ($key == 'movie') {
                $team_where['group_id'] = $val['gid'];
                $team_where['sub_id'] = $val['sid'];
            } else {
                $team_where['group_id'] = $val;
                if ($key == 'amusement') {
                    $team_where['sub_id'] = array('neq', 420);
                }
            }
            $limit = "0 , $catePage[$key]";
        }
        $data = D('Team')->getList($team_where, $order, $limit, $field);
        if ($data) {
            foreach ($data as $key => &$val) {
                $val['image'] = getImagePath($val['image']);
                $promotion = unserialize($val['promotion']);
                $val['not_time'] = 0;
                $val['all_type'] = 0;
                $val['today'] = 0;
                $val['muslim'] = 0;
                if ($promotion && in_array('Q', $promotion)) {
                    $val['muslim'] = 1;
                }
                if ($promotion && in_array('M', $promotion)) {
                    $val['not_time'] = 1;
                }
                if ($promotion && in_array('D', $promotion)) {
                    $val['all_type'] = 1;
                }
                if (date('Y-m-d', $val['begin_time']) == date('Y-m-d')) {
                    $val['today'] = 1;
                }
            }
            return $data;
        }
        return false;
    }

    /**
     * 第三方电子凭证修改团券状态
     */
    public function threeCoupon() {


        if (!C('THREE_VALID_STATE')) {
            exit('未开启第三方电子凭证');
        }

        // 设置php执行不超时
        set_time_limit(0);

        $config = C('THREE_VALID_PARTNER');
        $movie = new \Common\Org\MovieTicket();

        $etime = strtotime(date('Y-m-d 23:59:59'));
        $stime = $etime - 2 * 24 * 3600 + 1;
        foreach ($config as $key => $row) {
            $movie->setVid($row['query_vid']);
            $res = $movie->getFlow($stime, $etime);
            file_put_contents('/tmp/movie.log', var_export($res, true), FILE_APPEND);
            if ($res['ErrCode'] == 0 && count($res['items']) > 0) {
                foreach ($res['items'] as $li) {
                    if ($li['ChangeNum'] == 1) {
                        $coupon = M('coupon')->where("id='" . $li['RFID'] . "'")->find();
                        if ($coupon['consume'] == 'N') {
                            $res = D('Coupon')->consumeCoupon(array(
                                'id' => array($li['RFID']),
                                'list' => array($coupon),
                                'order_id' => $coupon['order_id']
                                    ), $key, '第三方电子凭证', false);
                            if (!$res) {
                                file_put_contents('/tmp/movieticker.log', 'Time:' . date('Y-m-d H:i:s') . '  ' . $coupon['id'] . "更新状态消费失败\r\n", FILE_APPEND);
                            } else {
                                file_put_contents('/tmp/movieticker.log', 'Time:' . date('Y-m-d H:i:s') . '  ' . $coupon['id'] . "更新状态消费成功\r\n", FILE_APPEND);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 同步团单未消费的券号
     */
    public function threeCreateCoupon() {

        if (!C('THREE_VALID_STATE')) {
            exit('未开启第三方电子凭证');
        }
        // 需要同步的订单号
        $team_ids = array(
            78160 => 78160,
            78159 => 78159,
        );

        // 设置php执行不超时
        set_time_limit(0);

        $config = C('THREE_VALID_PARTNER');
        $movie = new \Common\Org\MovieTicket();

        $field = array(
            'coupon.id' => 'id',
            'coupon.expire_time' => 'expire_time',
            'coupon.team_price' => 'price',
            'coupon.partner_id' => 'partner_id',
            'order.mobile' => 'mobile',
            'team.begin_time' => 'begin_time',
            'team.product' => 'title',
            'team.notice' => 'desc',
        );

        if (!$team_ids) {
            exit('没有要同步的团单号！');
        }

        $where = array('coupon.team_id' => array('in', array_keys($team_ids)), 'coupon.consume' => 'N');

        $data = M('coupon')->where($where)->field($field)->order('coupon.create_time')
                        ->join('inner join team on team.id=coupon.team_id')
                        ->join('inner join `order` on `order`.id=coupon.order_id')->select();
        file_put_contents('/tmp/threeCreateCoupon.log', '总共同步' . count($data) . '个券号，开始时间:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);
        if ($data) {
            foreach ($data as &$v) {
                $v['desc'] = mb_substr(preg_replace('/\s*/', '', strip_tags(ternary($v['desc'], ''))), 0, 150, 'utf8');
                if (!isset($config[$v['partner_id']])) {
                    continue;
                }
                $movie->setPartnerId($config[$v['partner_id']]['partner_id']);
                $movie->setVid($config[$v['partner_id']]['vid']);
                $res = $movie->create($v);
                file_put_contents('/tmp/threeCreateCoupon.log', var_export(array(
                            'coupon_info' => $v,
                            'res' => $res,
                                ), true) . "\r\n", FILE_APPEND);
            }
            unset($v);
        }
        file_put_contents('/tmp/threeCreateCoupon.log', '同步券号完成！，结束时间:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);
        exit('执行完成！');
    }

    /**
     *  批量复制团单
     */
    public function batchCopyTeam() {
        $team_id_file_path = '';

        $create_id_file_path = '/tmp/create_team_id' . date('Ymd') . '.txt';

        $active_id = 0;

        if (!trim($team_id_file_path)) {
            exit('团单id文件不能为空！');
        }

        $team_ids = @explode("\n", file_get_contents($team_id_file_path));
        if (!$team_ids) {
            exit('团单id不能为空！');
        }
        $team_ids = array_unique(array_filter($team_ids));

        $where = array(
            'id' => array('in', $team_ids),
        );
        $team = D('Team');
        $team_list = $team->where($where)->select();
        $begin_time = strtotime('2015-11-11 00:00:00');
        $end_time = strtotime('2015-11-12 00:00:00');

        $team_count = count($team_list);
        @file_put_contents($create_id_file_path, "总共{$team_count}个团单,下面是就团单id对应的新团单id\r\n", FILE_APPEND);
        echo "开支拷贝，总共{$team_count}个团单，请稍后...... \r\n";

        foreach ($team_list as $v) {
            $team_data = array_filter($v);
            $team_data['begin_time'] = $begin_time;
            $team_data['end_time'] = $end_time;
            $team_data['activities_id'] = $active_id;
            $team_data['view_count_day'] = $team_data['view_count'] = $team_data['now_number'] = 0;

            $old_team_id = $team_data['id'];
            unset($team_data['id']);
            $create_team_id = $team->add($team_data);
            $log_str = '';
            if ($create_team_id) {
                $log_str = "原团单id：#{$old_team_id}#；新团单id:#{$create_team_id}# \r\n";
            } else {
                $log_str = "原团单id：#{$old_team_id}#； 团单复制失败！ \r\n";
            }
            @file_put_contents($create_id_file_path, $log_str, FILE_APPEND);
        }
        exit("执行完成！\r\n");
    }

    /**
     *  批量修改团单
     */
    public function batchUpdateTeam() {
        $team_id_file_path = '';

        $create_id_file_path = '/tmp/update_team_res' . date('Ymd') . '.txt';

        $active_id = 0;

        if (!trim($team_id_file_path)) {
            exit("团单id文件不能为空！");
        }

        $team_ids = @explode("\n", file_get_contents($team_id_file_path));
        if (!$team_ids) {
            exit('团单id不能为空！');
        }
        $team_ids = array_unique(array_filter($team_ids));

        $where = array(
            'id' => array('in', $team_ids),
        );
        $team = D('Team');
        $update_team_data = array(
            'begin_time' => strtotime('2015-11-11 00:00:00'),
            'end_time' => strtotime('2015-11-12 00:00:00'),
            'activities_id' => $active_id,
        );
        $team_count = count($team_ids);
        echo "开支修改，总共{$team_count}个团单，请稍后...... \r\n";

        $update_res = $team->where($where)->save($update_team_data);

        @file_put_contents($create_id_file_path, '团单修改结果：' . var_export($update_res, true) . "\r\n", FILE_APPEND);

        exit("执行完成！\r\n");
    }

    /**
     *  批量修改团单 根据execl
     */
    public function batchUpdateTeamByExecl() {
        $team_info_file_path = '/tmp/team_update_log/2.csv';

        $create_id_file_path = '/tmp/team_update_log/create_team_res' . date('Ymd') . '.txt';

        $active_id = 1022;

        if (!trim($team_info_file_path)) {
            exit("修改团单文件不能为空！");
        }

        $file = fopen($team_info_file_path, 'r');

        if (!$file) {
            exit("文件读取失败！");
        }

        // 读取并整理数据
        $header = fgetcsv($file);
        $_data = array();
        while ($data = fgetcsv($file)) { //每次读取CSV
            $_line = array();
            foreach ($header as $k => $v) {
                $_line[$v] = $data[$k];
            }
            $_data[$_line['id']] = $_line;
        }
        fclose($file);
        if (!$_data) {
            exit("文件内容为空！");
        }
        $team = M('team');
        $begin_time = strtotime('2015-11-11 00:00:00');
        $end_time = strtotime('2015-11-12 00:00:00');
        echo "开始执行... \r\n";
        foreach ($_data as $k => $v) {
            $team_data = $team->where(array('id' => intval($k)))->find();
            if (!$team_data) {
                @file_put_contents($create_id_file_path, "团单#{$k}#不存在！ \r\n", FILE_APPEND);
                continue;
            }
            $team_data = array_filter($team_data);
            $team_data['begin_time'] = $begin_time;
            $team_data['end_time'] = $end_time;
            $team_data['activities_id'] = $active_id;
            $team_data['view_count_day'] = $team_data['view_count'] = $team_data['now_number'] = 0;

            // 活动时间
            unset($v['id']);
            if (isset($v['time']) && trim($v['time'])) {
                $end_time_ = @strtotime($v['time']);
                if ($end_time_ && $end_time_ > 0) {
                    $team_data['end_time'] = $end_time_;
                }
                unset($v['time']);
            }
            if (isset($v['expire_time']) && trim($v['expire_time'])) {
                $v['expire_time'] = @strtotime($v['expire_time']);
            }
            if (isset($v['begin_time']) && trim($v['begin_time'])) {
                $v['begin_time'] = @strtotime($v['begin_time']);
            }
            unset($v['id']);
            unset($team_data['id']);
            $team_data = array_merge($team_data, array_filter($v));
            $create_team_id = $team->add($team_data);
            $log_str = '';
            if ($create_team_id) {
                $log_str = "原团单id：#{$k}#；新团单id:#{$create_team_id}# \r\n";
            } else {
                $log_str = "原团单id：#{$k}#； 团单复制失败！ \r\n";
            }
            @file_put_contents($create_id_file_path, $log_str, FILE_APPEND);
        }
        exit("执行完成！");
    }

    /**
     * 批量导出商家结算未结算数据
     */
    public function batchExportPartnerDownload() {

        $start_time = strtotime('2015-10-01');
        $end_time = strtotime('2015-10-31 23:59:59');

        $partner_pay = M('partner_pay');
        $partner_income = M('partner_income');
        $partner = M('partner');

        $data = array();

        // 期初未结算
        $qichu_not_pay_res = array(); //$partner_pay->where($where)->group('partner_id')->getField('partner_id,sum(money) as qichu_not_pay', true);
        $where = array(
            'create_time' => array('lt', $start_time),
            '_string' => "pay_id not in (SELECT DISTINCT id FROM partner_pay  WHERE partner_pay.`pay_time`<{$start_time} AND paymark=1 )"
        );
        $qichu_not_pay_income_res = $partner_income->where($where)->group('partner_id')->getField('partner_id,sum(money) as qichu_not_pay', true);
        foreach (array($qichu_not_pay_res, $qichu_not_pay_income_res) as $_v) {
            foreach ($_v as $k => $v) {
                if (!isset($data[$k]) || !$data[$k]) {
                    $data[$k] = array();
                }
                if (!isset($data[$k]['qichu_not_pay']) || !trim($data[$k]['qichu_not_pay'])) {
                    $data[$k]['qichu_not_pay'] = 0;
                }
                $data[$k]['qichu_not_pay'] = sprintf("%.2f", $data[$k]['qichu_not_pay'] + $v);
            }
        }



        // 本期应结算
        $where = array('create_time' => array(array('egt', $start_time), array('elt', $end_time)));
        $benqi_ying_pay_res = array(); //$partner_pay->where($where)->group('partner_id')->getField('partner_id,sum(money) as benqi_ying_pay', true);
        $where = array('create_time' => array(array('egt', $start_time), array('elt', $end_time)));
        $benqi_ying_income_res = $partner_income->where($where)->group('partner_id')->getField('partner_id,sum(money) as benqi_ying_pay', true);
        foreach (array($benqi_ying_pay_res, $benqi_ying_income_res) as $_v) {
            foreach ($_v as $k => $v) {
                if (!isset($data[$k]) || !$data[$k]) {
                    $data[$k] = array();
                }
                if (!isset($data[$k]['benqi_ying_pay']) || !trim($data[$k]['benqi_ying_pay'])) {
                    $data[$k]['benqi_ying_pay'] = 0;
                }
                $data[$k]['benqi_ying_pay'] = sprintf("%.2f", $data[$k]['benqi_ying_pay'] + $v);
            }
        }

        // 本期已结算
        $where = array(
            'pay_time' => array(array('egt', $start_time), array('elt', $end_time)),
            'paymark' => 1,
        );
        $benqi_yi_pay_res = $partner_pay->where($where)->group('partner_id')->getField('partner_id,sum(money) as benqi_yi_pay', true);
        foreach ($benqi_yi_pay_res as $k => $v) {
            if (!isset($data[$k]) || !$data[$k]) {
                $data[$k] = array();
            }
            if (!isset($data[$k]['benqi_yi_pay']) || !trim($data[$k]['benqi_yi_pay'])) {
                $data[$k]['benqi_yi_pay'] = 0;
            }
            $data[$k]['benqi_yi_pay'] = sprintf("%.2f", $data[$k]['benqi_yi_pay'] + $v);
        }

        // 期末未结算
        $where = array(
            'paymark' => 0,
            '_string' => "id in (SELECT DISTINCT pay_id FROM partner_income  WHERE partner_income.`create_time`<={$end_time} AND pay_id<>0 )"
        );
        $qimo_not_pay_res = array(); //$partner_pay->where($where)->group('partner_id')->getField('partner_id,sum(money) as qimo_not_pay', true);
        $where = array(
            'create_time' => array('lt', $end_time),
            '_string' => "pay_id not in (SELECT DISTINCT id FROM partner_pay  WHERE partner_pay.`pay_time`<={$end_time} AND paymark=1 )"
        );
        $qimo_not_pay_income_res = $partner_income->where($where)->group('partner_id')->getField('partner_id,sum(money) as qimo_not_pay', true);
        foreach (array($qimo_not_pay_res, $qimo_not_pay_income_res) as $_v) {
            foreach ($_v as $k => $v) {
                if (!isset($data[$k]) || !$data[$k]) {
                    $data[$k] = array();
                }
                if (!isset($data[$k]['qimo_not_pay']) || !trim($data[$k]['qimo_not_pay'])) {
                    $data[$k]['qimo_not_pay'] = 0;
                }
                $data[$k]['qimo_not_pay'] = sprintf("%.2f", $data[$k]['qimo_not_pay'] + $v);
            }
        }

        // 最后结算时间
        $where = array(
            'paymark' => 1,
        );
        $last_pay_time_res = $partner_pay->where($where)->group('partner_id')->getField('partner_id,max(pay_time) as lat_pay_time', true);
        foreach ($last_pay_time_res as $k => $v) {
            if (!isset($data[$k]) || !$data[$k]) {
                $data[$k] = array();
            }
            if (!isset($data[$k]['lat_pay_time']) || !trim($data[$k]['lat_pay_time'])) {
                $data[$k]['lat_pay_time'] = 0;
            }
            $data[$k]['lat_pay_time'] = date('Y-m-d H:i:s', $v);
        }

        $fp = fopen('c:/file' . date('YmdHi') . '.csv', 'w');
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // 防止中文乱码
        fputcsv($fp, array('商家名称', '期初未结算', '本期应结算金额', '本期已结算金额', '期末未结算', '最后结算时间'));
        $partner_res_data = $partner->order('id asc')->field('id,title')->select();
        foreach ($partner_res_data as &$v) {
            $v['title'] = "{$v['title']}[{$v['id']}]";
            $v['qichu_not_pay'] = isset($data[$v['id']]['qichu_not_pay']) ? $data[$v['id']]['qichu_not_pay'] : 0.00;
            $v['benqi_ying_pay'] = isset($data[$v['id']]['benqi_ying_pay']) ? $data[$v['id']]['benqi_ying_pay'] : 0.00;
            $v['benqi_yi_pay'] = isset($data[$v['id']]['benqi_yi_pay']) ? $data[$v['id']]['benqi_yi_pay'] : 0.00;
            $v['qimo_not_pay'] = isset($data[$v['id']]['qimo_not_pay']) ? $data[$v['id']]['qimo_not_pay'] : 0.00;
            $v['lat_pay_time'] = isset($data[$v['id']]['lat_pay_time']) ? $data[$v['id']]['lat_pay_time'] : 0;
            unset($v['id']);
            fputcsv($fp, array_values($v));
        }
        unset($v);
        fclose($fp);
        file_put_contents('c://12345.log', var_export($partner_res_data, true));
        exit("执行完成！\r\n");
    }

    /**
     *   校准 云购 码 与 redis 一直性
     */
    public function cloud_shopping_code_redis() {

        $team_id = array(
            '97324' => '团单1'
        );
        $team = M('team');
        $cloud_shoping_code = M('cloud_shoping_code');
        $base_code = 10000001;
        $phpredis = new \Common\Org\phpRedis('pconnect');
        $redis = $phpredis::$redis;
        foreach ($team_id as $k => $v) {
            $team_info = $team->where(array('id' => $k))->find();

            if (!$team_info) {
                continue;
            }
            if (!isset($team_info['team_type']) || trim($team_info['team_type']) != 'cloud_shopping') {
                continue;
            }
            echo "开始校准云购单子:{$k} \r\n";
            $cloud_shoping_code_list = $cloud_shoping_code->where(array('team_id' => $k, 'periods_number' => $team_info['now_periods_number']))->getField('cloud_code,id', true);
            if (!$cloud_shoping_code_list) {
                continue;
            }
            $cloud_code_count = count($cloud_shoping_code_list);

            $_key = md5("{$team_info['id']}_{$team_info['now_periods_number']}");
            $key_incr = getCloudShopingRedisKey("yungou_incr_{$_key}");
            $key = getCloudShopingRedisKey("yungou_{$_key}");

            if ($cloud_code_count == $redis->get($key_incr)) {
                continue;
            }

            $redis->set($key_incr, $cloud_code_count);

            // 重新生成云购码
            $arr = array_pad(array(), intval($team_info['max_number']), 0);
            $arr1 = array_keys($arr);
            shuffle($arr1);
            $redis->delete($key);
            foreach ($arr1 as $_v) {
                $_k = $base_code + $_v;
                if (isset($cloud_shoping_code_list[$_k])) {
                    continue;
                }
                $redis->lPush($key, $_v);
            }
            $redis_cloud_code = $redis->lSize($key);
            echo "订单：{$k},已卖数量：{$cloud_code_count}-{$team_info['now_number']},云购数量： {$redis_cloud_code} \r\n";
        }
        die('校准完成！');
    }

    /**
     *  全站推送消息
     */
    public function pushMessageToAllUser() {
        $data = array(
            'title' => '终于到周末了，青团推出新套餐',
            //'account'=>array('925861'),
            'content' => '约上朋友带上家人，快乐周末走起！',
            'custom' => array(),
            'message_type' => '1'
        );

        $pushAppMessage = new \Common\Org\PushAppMessage();

        foreach (array('android', 'ios') as $v) {
            $data['plat'] = $v;
            $pushAppMessage->pushMessageToAll($data);
        }
        die('推送成功！');
    }

}
