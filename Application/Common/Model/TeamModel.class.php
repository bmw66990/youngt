<?php

/**
 * Team 项目表模型
 * Created by JetBrains PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 15-3-21
 * Time: 下午17:30
 * To change this template use File | Settings | File Templates.
 */

namespace Common\Model;

use Common\Model\CommonModel;

class TeamModel extends CommonModel {

    /**
     * 地球半径 km
     */
    const EARTH_RADIUS = 6378.138;

    private $base_code = 10000001;

    /**
     * 限量订单超时时间 s
     */
    const LIMITED_ORDER_TIME_OUT = 7200;

    // 支付方式
    private $_payAction = array(
        'creditpay' => '余额支付',
        'credit' => '余额支付',
        'tenpay' => '财付通支付',
        'alipay' => '支付宝支付',
        'umspay' => '全民付客户端',
        'wechatpay' => '微信支付',
        'wxpay' => '微信支付',
        'wxnpay' => '微信支付',
        'unionpay' => '银联支付',
        'lianlianpay' => '连连支付',
        'wapepay' => 'e支付',
        'wepay' =>  '京东支付'
    );
    private $payType = array(
        'alipay' => '支付宝',
        'pcalipay' => 'pc支付宝',
        'tenpay' => '财付通',
        'pctenpay' => 'pc财付通',
        'pcwxpaycode' => 'pc微信扫码支付',
        'wxpay' => '客户端微信支付',
        'umspay' => '银联全民捷付',
        'unionpay' => '银联支付',
        'wapunionpay' => 'wap银联支付',
        'lianlianpay' => '连连支付',
        'wapalipay' => 'wap支付宝',
        'waptenpay' => 'wap财付通',
        'wapumspay' => 'wap全民付',
        'wapwechatpay' => 'wap微信',
        'wepay' =>  '京东支付'
    );

    /**
     * 自动验证
     * @var array
     */
    protected $_validate = array(
        array('city_id', 'require', '请选择所属分站'),
        array('partner_id', 'require', '请选择所属商家'),
        array('group_id', 'require', '请选择所属分类'),
        array('product', 'require', '团单短标题必须填写'),
        //array('title', 'require', '团单标题必须填写'),
        array('team_price', 'require', '团单价格必须填写'),
        array('ucaii_price', 'require', '团单供货价必须填写'),
        array('market_price', 'require', '团单市场价必须填写'),
        array('expire_time', 'require', '青团券有效期必须填写'),
        array('begin_time', 'require', '团单开始时间必须填写'),
        array('end_time', 'require', '团单结束时间必须填写'),
        //  array('notice', 'require', '团单特别提醒必须填写'),
        array('detail', 'require', '团单详情必须填写'),
        array('title', '1,50', '项目标题不能超过50个字', 3, 'length'),
        array('product', '1,15', '商品名称不能超过15个字', 3, 'length'),
    );
    private $teamField = array(
        'id',
        'product',
        'title',
        'team_price',
        'image', //
        'market_price',
        'now_number',
        'max_number', //
        'partner_id',
        'sort_order', //
        'begin_time',
        'end_time',
        'allowrefund', //
        'promotion', //
        'team_type',
        'per_number',
        'flv',
        'activities_id',
        'group_id',
        'lottery_price',
    );

    /**
     * 自动完成
     */
    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
        array('begin_time', 'strtotime', 3, 'function'),
        array('end_time', 'strtotime', 3, 'function'),
        array('expire_time', 'strtotime', 3, 'function'),
        array('promotion', 'serialize', 3, 'function'),
        array('interval_begin', 'strtotime', 3, 'function'),
        array('interval_end', 'strtotime', 3, 'function'),
    );

    public function getTeamField() {
        return $this->teamField;
    }

    /**
     * 获取团单列表
     * @param $where where条件
     * @param $order 排序
     * @param $limit
     * @return mixed
     */
    public function getTeam($where, $limit, $orderby = 'id DESC,sort_order DESC', $field = '*') {
        $data = $this->alias('t')
                ->field($field)
                ->join(array('LEFT JOIN user u ON t.user_id = u.id', 'LEFT JOIN partner p ON t.partner_id = p.id'))
                ->where($where)
                ->order($orderby)
                ->limit($limit)
                ->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }

    /**
     * 获取团单详情
     * @param $id : 团单id
     * @return mixed
     */
    public function getTeamDetail($id) {
        $field = 'id,city_id,allowrefund,flv,team_type,notice,promotion,product,partner_id,detail,title,summary,team_price,market_price,ucaii_price,lottery_price,begin_time,end_time,expire_time,permin_number,min_number,max_number,now_number,image,per_number,is_optional_model,activities_id,pre_number';
        $info = $this->info($id, $field);

        if (!empty($info)) {
            $info['city_name'] = M('category')->getFieldById($info['city_id'], 'name');
            $partner_info = M('partner')->getFieldById($info['partner_id'], 'id,username,db_id');
            $info['partner_username'] = $partner_info[$info['partner_id']]['username'];
            $info['bd_username'] = ternary(M('bd_user')->getFieldById($partner_info[$info['partner_id']]['db_id'], 'db_username'), '该商家暂未绑定业务员');
            $info['consume_num'] = M('coupon')->where(array('consume' => 'Y', 'team_id' => $info['id']))->count('id');
            $buy = M('order')->field("count(id) as buy_count,sum(quantity) as buy_num,sum(money) as money,sum(credit) as credit, sum(origin) as origin")->where(array('team_id' => $info['id'],'_string'=>"state='pay' or rstate='berefund'"))->find();
            if ($buy === false) {
                $this->errorInfo['info'] = $this->getDbError();
                $this->errorInfo['sql'] = $this->_sql();
            }
            $info = array_merge($info, $buy);
            $info['refund_num'] = M('order')->where(array('rstate' =>'berefund', 'team_id' => $info['id']))->sum('quantity');
        }
        return $info;
    }

    /**
     * 删除项目以及图片信息
     * @param $where 条件
     * @return bool
     */
    public function delTeam($where) {
        $data = $this->where($where)->getField('id,image', true);
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        $rs = $this->where($where)->delete();
        foreach ($data as $row) {
            //TODO 删除图片信息
            //@unlink($row);
        }
        if ($rs === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
    }

    /**
     * 获取业务员总签单
     * @param $where
     */
    public function getBdUserTeam($where) {
        $data = $this->alias('t')->join('LEFT JOIN partner p ON p.id=t.partner_id')->where($where)->group('p.db_id')->getField('p.db_id,count(t.id)', true);
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }

    /**
     * 获取订单对应的Team信息
     * @param $data
     * @return bool|mixed
     */
    public function getOrderTeam($data) {
        if (is_array($data)) {
            $teamId = array();
            foreach ($data as $row) {
                $teamId[] = $row['team_id'];
            }
            if (empty($teamId)) {
                return array();
            }
            $data = array_unique($teamId);
        }
        $map['id'] = array('in', $data);
        $team = $this->where($map)->getField('id,product,title,image,expire_time,begin_time,end_time,delivery,team_type,partner_id,team_price,market_price,now_number', true);
        if ($team === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        return $team;
    }

    /**
     * 统一处理团单数据
     * @param type $data
     * @param type $flag
     * @return type
     */
    public function dealTeamData($data = array(), $isOne = false, $isPartner = true) {

        if (!$data) {
            return array();
        }
        if ($isOne) {
            $data = array($data);
        }

        $_data = array();
        $partner_ids = array();
        $activities_ids = array();
        $nowTime = strval(time());
        $ota = D('Ota');
        foreach ($data as $k => $v_res) {

            if ($ota->tmCheck($v_res['id'])) {
                unset($data[$k]);
                continue;
            }
            /***
             * 2016.6.10苹果bug处理
             */
            if ($v_res['per_number']==0 ||$v_res['per_number']=='0') {
                $v_res['per_number']=500;
            }

            if (!isset($v_res['id'])) {
                continue;
            }
            if (!isset($v_res['partner_id'])) {
                continue;
            }
            if ($isPartner && !isset($partner_ids[$v_res['partner_id']])) {
                $partner_ids[$v_res['partner_id']] = $v_res['partner_id'];
            }
            if (isset($v_res['activities_id']) && trim($v_res['activities_id'])) {
                $activities_ids[$v_res['activities_id']] = $v_res['activities_id'];
            }

            $line = array(
                'id' => ternary($v_res['id'], ''),
                'product' => ternary($v_res['product'], ''),
                'team_price' => ternary($v_res['team_price'], ''),
                'image' => isset($v_res['image']) && trim($v_res['image']) ? getImagePath($v_res['image']) : '',
                'market_price' => ternary($v_res['market_price'], '0'),
                'lottery_price' => ternary($v_res['lottery_price'], '0'),
                'sort_order' => ternary($v_res['sort_order'], '0'),
                'begin_time' => ternary($v_res['begin_time'], '0'),
                'end_time' => ternary($v_res['end_time'], '0'),
                'now_time' => $nowTime,
                'now_number' => ternary($v_res['now_number'], '0'),
                'max_number' => ternary($v_res['max_number'], '0'),
                'partner_id' => ternary($v_res['partner_id'], '0'),
                'team_type' => ternary($v_res['team_type'], ''),
                'title' => ternary($v_res['title'], ''),
                'per_number' => ternary($v_res['per_number'], '0'),
                'allowrefund' => ternary($v_res['allowrefund'], ''),
                'activities_id' => ternary($v_res['activities_id'], '0'),
                'group_id' => ternary($v_res['group_id'], '0'),
                'promotion' => isset($v_res['promotion']) && trim($v_res['promotion']) && unserialize($v_res['promotion']) ? json_encode(unserialize($v_res['promotion'])) : "[]",
            );

            // 如果是 一元众筹，单价统一为1元
            if (isset($v_res['team_type']) && $v_res['team_type'] == 'cloud_shopping'){
                $line['team_price']=1.00;
            }

            // 处理价格
            if (strpos($line['team_price'], '.') !== false) {
                $line['team_price'] = $line['team_price'] > 0 ? rtrim(rtrim($line['team_price'], '0'), '.') : '0';
            }
            if (strpos($line['market_price'], '.') !== false) {
                $line['market_price'] = $line['market_price'] > 0 ? rtrim(rtrim($line['market_price'], '0'), '.') : '0';
            }
            if (strpos($line['lottery_price'], '.') !== false) {
                $line['lottery_price'] = $line['lottery_price'] > 0 ? rtrim(rtrim($line['lottery_price'], '0'), '.') : '0';
            }
            // 处理开始时间 结束时间[秒杀团单的处理]
            $v_res['team_type'] = isset($v_res['team_type']) ? strtolower(trim($v_res['team_type'])) : '';
            if (isset($v_res['team_type']) && $v_res['team_type'] == 'timelimit' && isset($v_res['flv']) && trim($v_res['flv']) == 'Y') {
                $now_day = date('Y-m-d');
                $line['begin_time'] = strtotime($now_day . ' ' . date('H:i:s', $v_res['begin_time']));
                $line['end_time'] = strtotime($now_day . ' ' . date('H:i:s', $v_res['end_time']));
            }
            // 新用户立减价格
            $line['new_user_reduce_price'] = '0';
            if (isset($v_res['team_type']) && $v_res['team_type'] == 'newuser' && isset($v_res['lottery_price']) && $v_res['lottery_price'] > 0) {
                $new_user_reduce_price = sprintf("%.2f", $line['team_price'] - $v_res['lottery_price']);
                if ($new_user_reduce_price > 0) {
                    $line['new_user_reduce_price'] = strval($new_user_reduce_price);
                }
            }
            // 新客立减价格
            $line['new_guser_reduce_price'] = '0';
            if (isset($v_res['team_type']) && $v_res['team_type'] == 'newguser' && isset($v_res['lottery_price']) && $v_res['lottery_price'] > 0) {
                $new_guser_reduce_price = sprintf("%.2f", $line['team_price'] - $v_res['lottery_price']);
                if ($new_guser_reduce_price > 0) {
                    $line['new_guser_reduce_price'] = strval($new_guser_reduce_price);
                }
            }            

            if ($isOne) {
                $line['flv'] = ternary($v_res['flv'], '');
                $line['summary'] = ternary($v_res['summary'], '');
                $line['notice'] = ternary($v_res['notice'], '');
                if (isset($line['notice']) && trim($line['notice'])) {
                    $line['notice'] = str_replace(array('是否提供发票：否', '不提供发票：', '不提供发票'), '消费后评价获得积分', $line['notice']);
                    if (isset($line['team_type']) && $line['team_type'] != 'goods') {
                        $_notice = "<ul><li><span style='line-height:1.5;'>有效期：" . date('Y-m-d', $v_res['begin_time']) . '至' . date('Y-m-d', $v_res['expire_time']) . "</span> </li></ul>";
                        $line['notice'] = $_notice . $line['notice'];
                    }
                }



                // 分店数量
                $line['brance_count'] = $this->getParnerAllBranchList($v_res['partner_id'], array(), true);

                // 分享链接
                $line['share_url'] = "http://m.youngt.com/Team/detail/id/{$v_res['id']}.html";
                //"http://mobile.youngt.com/Index/view/id/{$v_res['id']}";
                // 商家 获取评论数/好评数/评论平均分
                $team_list_res = M('team')->where(array('partner_id' => $v_res['partner_id']))->index('id')->select();
                $comment_team_ids = array();
                $where = array('partner_id' => $v_res['partner_id'], 'is_comment' => 'Y', 'comment_display' => 'Y');
                if ($team_list_res) {
                    $comment_team_ids = array_keys($team_list_res);
                    unset($where['partner_id']);
                    $where['team_id'] = array('in', $comment_team_ids);
                }
                $coment = M('comment');
                $commentCountRes = $coment->where($where)->where("comment_num IS NOT NULL")->field(array('COUNT(id)' => 'comment_count', 'AVG(comment_num)' => 'comment_avg_num'))->find();
                $where['comment_num'] = array('GT', 3);
                $commentHighCountRes = $coment->where($where)->field(array('COUNT(id)' => 'comment_high_count'))->find();
                $line['comment_count'] = ternary($commentCountRes['comment_count'], '');
                $line['comment_avg_num'] = isset($commentCountRes['comment_avg_num']) ? number_format($commentCountRes['comment_avg_num'], 1) : 0;
                $line['comment_high_count'] = ternary($commentHighCountRes['comment_high_count'], '');

                // 本单评论
                $where = array('team_id' => $v_res['id'], 'is_comment' => 'Y', 'comment_display' => 'Y');
                $commentCountRes = $coment->where($where)->where("comment_num IS NOT NULL")->field(array('COUNT(id)' => 'comment_count', 'AVG(comment_num)' => 'comment_avg_num'))->find();
                $line['team_comment_count'] = ternary($commentCountRes['comment_count'], '');
                $line['team_comment_avg_num'] = isset($commentCountRes['comment_avg_num']) ? number_format($commentCountRes['comment_avg_num'], 1) : 0;
            }
            $_data[] = $line;
        }
        unset($line);
        unset($v_res);

        // 商家获取
        $partner = array();
        if ($isPartner && $partner_ids) {
            $field = 'id,group_id,title,images,long,lat,username,mobile,address,phone';
            $partner = M('partner')->field($field)->where(array('id' => array('in', array_keys($partner_ids))))->index('id')->select();
            if (!$partner) {
                $partner = array();
            }
        }
        $activities_res = array();
        if ($activities_ids) {
            $activities_where = array(
                'id' => array('in', array_keys($activities_ids)),
                'type' => 'activities',
                'begin_time' => array('lt', $nowTime),
                'end_time' => array('gt', $nowTime)
            );
            $activities_res = M('admanage')->where($activities_where)->field('id,textarr as title')->index('id')->select();
            if (!$activities_res) {
                $activities_res = array();
            }
        }
        foreach ($_data as &$v_res) {
            $v_res['activities_name'] = '';
            if (isset($v_res['activities_id']) && trim($v_res['activities_id'])) {
                $v_res['activities_name'] = ternary($activities_res[$v_res['activities_id']]['title'], '');
            }
            // 设置成活动价
//            if(trim($v_res['activities_name']) && $v_res['lottery_price']>0){
//                $v_res['team_price']=  ternary($v_res['lottery_price'], $v_res['team_price']);
//            }
            if ($isPartner) {
                $v_res['partner'] = array(
                    'id' => ternary($partner[$v_res['partner_id']]['id'], ''),
                    'group_id' => ternary($partner[$v_res['partner_id']]['group_id'], ''),
                    'part_title' => ternary($partner[$v_res['partner_id']]['title'], ''),
                    'images' => isset($partner[$v_res['partner_id']]['images']) && trim($partner[$v_res['partner_id']]['images']) ? getImagePath($partner[$v_res['partner_id']]['images']) : '',
                    'lng' => ternary($partner[$v_res['partner_id']]['long'], ''),
                    'lat' => ternary($partner[$v_res['partner_id']]['lat'], ''),
                    'username' => ternary($partner[$v_res['partner_id']]['username'], ''),
                    'mobile' => ternary($partner[$v_res['partner_id']]['mobile'], ''),
                    'address' => ternary($partner[$v_res['partner_id']]['address'], ''),
                );
                if (isset($partner[$v_res['partner_id']]['phone']) && trim($partner[$v_res['partner_id']]['phone'])) {
                    $v_res['partner']['mobile'] = $partner[$v_res['partner_id']]['phone'];
                }
            }
        }
        unset($v_res);
        if ($isOne) {
            $_data = array_pop($_data);
        }
        return $_data;
    }

    /**
     * 获取数据为where 和 sort
     * @param type $data
     * @return type
     */
    public function getMysqlWhere($data) {
        // 查询条件拼接
        $nowTime = time();
        $where = array('begin_time' => array('LT', $nowTime), 'end_time' => array('GT', $nowTime), '_string' => "team_type='normal' OR team_type='newuser'");
        if (isset($data['plat']) && trim($data['plat']) == 'wap') {
            $where = array('begin_time' => array('LT', $nowTime), 'end_time' => array('GT', $nowTime), '_string' => "team_type='normal' OR team_type='newuser' OR team_type='goods'");
        }
        if (isset($data['city_id']) && trim($data['city_id'])) {
            $where['city_id'] = $data['city_id'];
            if (isset($data['plat']) && trim($data['plat']) == 'wap') {
                $where['city_id'] = array('in', array($data['city_id'], 957));
            }
        }
        if (isset($data['cityId']) && trim($data['cityId'])) {
            $where['city_id'] = $data['cityId'];
            if (isset($data['plat']) && trim($data['plat']) == 'wap') {
                $where['city_id'] = array('in', array($data['cityId'], 957));
            }
        }

        // 排序字段
        $sort = array();
        if (isset($data['order']) && $data['order']) {
            $lastId = ternary($data['lastId'], '');
            if (strpos($data['order'], '@') !== false) {
                @list($sortType, $sortFildel) = explode('@', $data['order'], 2);
                $sort[$sortFildel] = 'ASC';
                trim($lastId) != '' && $where[$sortFildel] = array('GT', $lastId);
                if (trim($sortType) == '-') {
                    $sort[$sortFildel] = 'DESC';
                    trim($lastId) != '' && $where[$sortFildel] = array('LT', $lastId);
                }
            } else {
                $sort[$data['order']] = 'ASC';
                trim($lastId) != '' && $where[$data['order']] = array('GT', $lastId);
            }
        }

        // 类型过滤
        if (isset($data['type']) && trim($data['type'])) {
            $where['group_id'] = $data['type'];
            if (strpos($data['type'], '@') !== false) {
                @list($groupId, $subId) = explode('@', $data['type']);
                trim($groupId) && $where['group_id'] = $groupId;
                trim($subId) && $where['sub_id'] = $subId;
            }
        }

        if (isset($data['query']) && trim($data['query'])) {
            $pinyin = new \Common\Org\pinyin();
            $all_pinyin = $pinyin->str2py($data['query'], 'all');
            $first_pinyin = $pinyin->str2py($data['query'], 'other');
            $query_key_where = array();
            foreach (array($data['query'], $all_pinyin, $first_pinyin) as $v) {
                $query_key_where[] = "product like '%{$v}%' OR title like '%{$v}%' OR sel1 like '%{$v}%' OR sel2 like '%{$v}%' OR sel3 like '%{$v}%'";
            }
            $query_key_where = $where['_string'] = implode(' OR ', $query_key_where);
        }

        return array($where, $sort);
    }

    /**
     * 获取搜索服务的查询条件
     * @param type $data
     * @return type
     */
    public function getSearchWhere($data) {
        // 拼接search查询条件
        $query = "(team_type:'normal' OR team_type:'newuser')";
        if (isset($data['plat']) && trim($data['plat']) == 'wap') {
            $query = "(team_type:'normal' OR team_type:'newuser' OR team_type:'goods')";
        }
        if (isset($data['query']) && trim($data['query'])) {
            $pinyin = new \Common\Org\pinyin();
            $all_pinyin = $pinyin->str2py($data['query'], 'all');
            $first_pinyin = $pinyin->str2py($data['query'], 'other');
            $query_key_where = array();
            foreach (array($data['query'], $all_pinyin, $first_pinyin) as $v) {
                $query_key_where[] = "product:'{$v}' OR title:'{$v}' OR sel1:'{$v}' OR sel2:'{$v}' OR sel3:'{$v}'";
            }
            $query_key_where = implode(' OR ', $query_key_where);
            $query = "$query AND ($query_key_where)";
        }
        if (isset($data['cityId']) && trim($data['cityId'])) {
            $cityWhere = "city_id:'{$data['cityId']}'";
            if (isset($data['plat']) && trim($data['plat']) == 'wap') {
                $cityWhere = "(city_id:'{$data['cityId']}' OR city_id:'957')";
            }
            $query = "$query AND ($cityWhere)";
        }
        if (isset($data['city_id']) && trim($data['city_id'])) {
            $cityWhere = "city_id:'{$data['city_id']}'";
            if (isset($data['plat']) && trim($data['plat']) == 'wap') {
                $cityWhere = "(city_id:'{$data['city_id']}' OR city_id:'957')";
            }
            $query = "$query AND ($cityWhere)";
        }
        if (isset($data['type']) && trim($data['type'])) {
            $_where['group_id'] = $data['type'];
            if (strpos($data['type'], '@') !== false) {
                @list($groupId, $subId) = explode('@', $data['type']);
                trim($groupId) && $_where['group_id'] = $groupId;
                trim($subId) && $_where['sub_id'] = $subId;
            }
            $typeQuery = "";
            foreach ($_where as $k => $v) {
                $typeQuery .= " AND $k:'$v'";
            }
            trim($typeQuery) && $query = "{$query} {$typeQuery}";
        }

        // 查询条件拼接
        $nowTime = time();
        $where = "end_time>$nowTime AND begin_time<$nowTime";
        // 排序字段
        $sort = array();
        if (isset($data['order']) && trim($data['order'])) {
            $lastId = ternary($data['lastId'], '');
            $lastIdWhere = "";
            if (strpos($data['order'], '@') !== false) {
                @list($sortType, $sortFildel) = explode('@', $data['order'], 2);
                if (trim($sortType) == '-') {
                    $sort[$sortFildel] = '-';
                    $lastIdWhere = $this->getSearchSortWhere($sortFildel, $data['lastId'], $data['end_id'], $sortType);
                }
            } else {
                $sort[$data['order']] = '+';
                $lastIdWhere = $this->getSearchSortWhere($data['order'], $data['lastId'], $data['end_id']);
            }
            trim($lastIdWhere) && $where = "$lastIdWhere AND $where";
        }
        $sort['id'] = '-';

        return array($query, $where, $sort);
    }

    /**
     * 获取search排序字段 所需要的where条件
     */
    public function getSearchSortWhere($sortField, $lastId, $end_id, $sortType = '+') {

        if (!trim($sortField) || trim($lastId) == '') {
            return '';
        }
        $where = "{$sortField}>$lastId";
        if (trim($sortType) == '-') {
            $where = "{$sortField}<$lastId";
        }

        if (trim($sortField) != 'id' && trim($end_id) != '') {
            $where = "($where OR ($sortField='$lastId' AND id<$end_id))";
        }

        return $where;
    }

    /**
     * 获取mysql排序字段 所需要的where条件
     */
    public function getMysqlSortWhere($sortField, $lastId, $idField, $endId, $sortType = '+') {

        if (!trim($sortField) || trim($lastId) == '') {
            return '';
        }
        $where = "{$sortField}>$lastId";
        if (trim($sortType) == '-') {
            $where = "{$sortField}<$lastId";
        }

        if (trim($idField) != '' && trim($endId) != '') {
            $where = "($where OR ($sortField='$lastId' AND $idField<'$endId'))";
        }

        return $where;
    }

    /**
     * 根据条件获取符合条件的商户id
     * @param type $data
     */
    public function getPartnerByWhere($data) {
        $where = array();

        // 街道过滤
        if (isset($data['streetId']) && trim($data['streetId'])) {
            $where['partner.zone_id'] = $data['streetId'];
            if (strpos($data['streetId'], '@') !== false) {
                @list($zoneId, $stationId) = explode('@', $data['streetId']);
                trim($zoneId) && $where['partner.zone_id'] = $zoneId;
                trim($stationId) && $where['partner.station_id'] = $stationId;
            }
        }

        if (!$where) {
            return array();
        }

        $res = M('partner')->where($where)->field('id')->select();
        if (!$res) {
            return array();
        }
        $partnerIds = array();
        foreach ($res as $v) {
            if (isset($v['id']) && !in_array($v['id'], $partnerIds)) {
                $partnerIds[] = $v['id'];
            }
        }
        return $partnerIds;
    }

    /**
     * 团单搜索
     * @param type $data
     */
    public function teamSearch($data, $limit = 20) {

        // 查询条件拼接
        $_where = '';


        // 获取where 和 排序字段
        list($where, $sort) = $this->getMysqlWhere($data);

        $res = $this->where($where)->order($sort)->field($this->teamField)->limit($limit)->select();
        if ($res === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }

        return $res;
    }

    /**
     * 根据条件获取到商家数据  源代码备份
     * @param type $data
     * @return array
     */
    public function getTeamSearchPartner20150814(&$data, $limit = 20) {

        // 条件
        $where = "";
        $_where = array();
        $having = "";

        // 返回字段名称
        $field = array(
            'partner.id' => 'partner_id',
            'partner.group_id' => 'group_id',
            'partner.title' => 'part_title',
            'partner.images' => 'images',
            'partner.long' => 'long',
            'partner.lat' => 'lat',
            'partner.username' => 'username',
            'ifnull(avg(comment.comment_num),0)' => 'comment_avg_num',
            'partner.address' => 'address'
        );

        // 城市过滤
        $_where['partner.city_id'] = 1;
        if (isset($data['cityId']) && trim($data['cityId'])) {
            $_where['partner.city_id'] = intval($data['cityId']);
        }

        // 关键字搜索
        if (isset($data['query']) && trim($data['query'])) {
            $_where['partner.title'] = array('like', "%{$data['query']}%");
        }

        // 街道过滤
        if (isset($data['streetId']) && trim($data['streetId'])) {
            $_where['partner.zone_id'] = $data['streetId'];
            if (strpos($data['streetId'], '@') !== false) {
                @list($zoneId, $stationId) = explode('@', $data['streetId']);
                trim($zoneId) && $_where['partner.zone_id'] = intval($zoneId);
                trim($stationId) && $_where['partner.station_id'] = intval($stationId);
            }
        }

        // 经纬度条件删选
        if (isset($data['lng']) && trim($data['lng']) && isset($data['lat']) && trim($data ['lat'])) {
            $lng = $data['lng'];
            $lat = $data['lat'];
            $distance = ternary($data['distance'], 1);
            $lngLatSquarePoint = $this->returnSquarePoint($lng, $lat, $distance);
            $where = "partner.`long`>='{$lngLatSquarePoint['left-top']['lng']}' AND partner.lat>='{$lngLatSquarePoint['left-bottom']['lat']}' AND partner.`long`<='{$lngLatSquarePoint['right-bottom']['lng']}' AND partner.`lat`<='{$lngLatSquarePoint['left-top']['lat']}'";
            $having = "distance <= $distance";
            $distanceField = $this->getMysqlDistanceField($lat, $lng);
            $field[$distanceField] = 'distance';
        }

        // 排序方式
        $sort = array();
        $joinField = '';
        if (isset($data['order']) && $data['order']) {
            $lastId = ternary($data['lastId'], '');
            switch ($data['order']) {
                // 距离最近
                case 'distance':
                case '+@distance':
                    $sort['distance'] = 'ASC';
                    $sort_filed = 'distance';
                    $order_type = '+';
                    if (!in_array('distance', $field)) {
                        unset($sort['distance']);
                        $sort['sort'] = 'DESC';
                        $sort_filed = 'sort';
                        $order_type = '-';
                    }
                    list($sortField, $joinField) = $this->__getMysqlSortField('sort_order', $order_type, $data);
                    $_having = $this->getMysqlSortWhere($sort_filed, $data['lastId'], 'partner_id', $data['end_id'], $order_type);
                    if (trim($_having)) {
                        $having = trim($having) ? "$having AND $_having" : $_having;
                    }
                    $field[$sortField] = 'sort';
                    unset($data['order']);
                    break;
                case 'comment_avg_num':
                case '+@comment_avg_num':
                    $sort['comment_avg_num'] = 'DESC';
                    list($sortField, $joinField) = $this->__getMysqlSortField('sort_order', '-', $data);
                    $_having = $this->getMysqlSortWhere('comment_avg_num', $data['lastId'], 'partner_id', $data['end_id'], '-');
                    if (trim($_having)) {
                        $having = trim($having) ? "$having AND $_having" : $_having;
                    }
                    $field[$sortField] = 'sort';
                    unset($data['order']);
                    break;
                default:
                    $_having = '';
                    $sort['sort'] = 'ASC';
                    list($sortField, $joinField) = $this->__getMysqlSortField($data['order'], '+', $data);
                    $_having = $this->getMysqlSortWhere('sort', $data['lastId'], 'partner_id', $data['end_id'], '+');
                    if (strpos($data['order'], '@') !== false) {
                        list($sortType, $_sortField) = explode('@', $data['order']);
                        if (trim($sortType) == '-') {
                            $sort['sort'] = 'DESC';
                            trim($lastId) && $_having = "sort < $lastId";
                            $_having = $this->getMysqlSortWhere('sort', $data['lastId'], 'partner_id', $data['end_id'], '-');
                            list($sortField, $joinField) = $this->__getMysqlSortField($_sortField, '-', $data);
                        }
                    }
                    if (trim($_having)) {
                        $having = trim($having) ? "$having AND $_having" : $_having;
                    }
                    $field[$sortField] = 'sort';
                    break;
            }
        }

        $sort['partner.id'] = 'DESC';
        $partner = M('partner');
        if (trim($where)) {
            $partner->where($where);
        }
        if (trim($joinField)) {
            $partner->join($joinField);
        }

        if (trim($having)) {
            $partner->having($having);
        }
        if ($_where) {
            $partner->where($_where);
        }

        $res = $partner->field($field)->order($sort)->join("left JOIN comment ON partner.id=comment.`partner_id` and `comment`.is_comment='Y' and `comment`.comment_display='Y'")->group('partner.id')->limit($limit)->select();
        if ($res === false) {
            $this->errorInfo['info'] = $partner->getDbError();
            $this->errorInfo['sql'] = $partner->_sql();
            return false;
        }

        return $res;
    }

    /**
     * 根据条件获取到商家数据
     * @param type $data
     * @return array
     */
    public function getTeamSearchPartnerOrderCommentNum(&$data, $limit = 20) {

        // 条件
        $where = array();
        $having = "";

        // 返回字段名称
        $field = array(
            'partner.id' => 'partner_id',
            'partner.group_id' => 'group_id',
            'partner.title' => 'part_title',
            'partner.images' => 'images',
            'partner.long' => 'long',
            'partner.lat' => 'lat',
            'partner.username' => 'username',
            'ifnull(avg(comment.comment_num),0)' => 'comment_avg_num',
            'partner.address' => 'address'
        );

        // 城市过滤
        //$where['partner.city_id'] = 1;
        if (isset($data['cityId']) && trim($data['cityId'])) {
            $where['partner.city_id'] = intval($data['cityId']);
        }

        // 关键字搜索
//        if (isset($data['query']) && trim($data['query'])) {
//            $where['partner.title'] = array('like', "%{$data['query']}%");
//        }
        // 街道过滤
        if (isset($data['streetId']) && trim($data['streetId'])) {
            $where['partner.zone_id'] = $data['streetId'];
            if (strpos($data['streetId'], '@') !== false) {
                @list($zoneId, $stationId) = explode('@', $data['streetId']);
                trim($zoneId) && $where['partner.zone_id'] = intval($zoneId);
                trim($stationId) && $where['partner.station_id'] = intval($stationId);
            }
        }

        // 折扣过滤
        if (isset($data['is_discount']) && trim($data['is_discount'])) {
            $where['partner.is_discount'] = intval($data['is_discount']);
        }

        // 经纬度条件删选
        if (isset($data['lng']) && trim($data['lng']) && isset($data['lat']) && trim($data ['lat'])) {
            $lng = $data['lng'];
            $lat = $data['lat'];
            $distance = ternary($data['distance'], 3);
            $lngLatSquarePoint = $this->returnSquarePoint($lng, $lat, $distance);
            $where['_string'] = "partner.`long`>='{$lngLatSquarePoint['left-top']['lng']}' AND partner.lat>='{$lngLatSquarePoint['left-bottom']['lat']}' AND partner.`long`<='{$lngLatSquarePoint['right-bottom']['lng']}' AND partner.`lat`<='{$lngLatSquarePoint['left-top']['lat']}'";
            $having = "distance <= $distance";
            $distanceField = $this->getMysqlDistanceField($lat, $lng);
            $field[$distanceField] = 'distance';
        }

        // 排序方式
        $sort = array();
        $joinField = '';
        if (isset($data['order']) && $data['order']) {
            $lastId = ternary($data['lastId'], '');
            $sort['comment_avg_num'] = 'DESC';
            list($sortField, $joinField) = $this->__getMysqlSortField('sort_order', '-', $data);
            $_having = $this->getMysqlSortWhere('comment_avg_num', $lastId, 'partner_id', $data['end_id'], '-');
            if (trim($_having)) {
                $having = trim($having) ? "$having AND $_having" : $_having;
            }
            $field[$sortField] = 'sort';
            unset($data['order']);
        }

        $sort['partner.id'] = 'DESC';
        $partner = M('partner');
        if ($where) {
            $partner->where($where);
        }
        if (trim($joinField)) {
            $partner->join($joinField);
        }

        if (trim($having)) {
            $partner->having($having);
        }

        $res = $partner->field($field)->order($sort)
                        ->join("left JOIN comment ON partner.id=comment.`partner_id` and `comment`.is_comment='Y' and `comment`.comment_display='Y'")
                        ->group('partner.id')->limit($limit)->select();
        if ($res === false) {
            $this->errorInfo['info'] = $partner->getDbError();
            $this->errorInfo['sql'] = $partner->_sql();
            return false;
        }

        return $res;
    }

    /**
     * 根据条件获取到商家数据  根据其他排序
     * @param type $data
     * @return array
     */
    public function getTeamSearchPartner(&$data, $limit = 20) {
        // 条件
        $where = array();
        $having = "";

        // 返回字段名称
        $field = array(
            'partner.id' => 'partner_id',
            'partner.group_id' => 'group_id',
            'partner.title' => 'part_title',
            'partner.image' => 'images',
            'partner.long' => 'long',
            'partner.lat' => 'lat',
            'partner.username' => 'username',
            'partner.address' => 'address'
        );

        // 城市过滤
        // $where['partner.city_id'] = 1;
        if (isset($data['cityId']) && trim($data['cityId'])) {
            $where['partner.city_id'] = intval($data['cityId']);
            if (isset($data['plat']) && trim($data['plat']) == 'wap') {
                $where['partner.city_id'] = array('in', array(intval($data['cityId']), 957));
            }
        }

        // 关键字搜索
        if (isset($data['query']) && trim($data['query'])) {
            $where['partner.title'] = array('like', "%{$data['query']}%");
        }
        // 街道过滤
        if (isset($data['streetId']) && trim($data['streetId'])) {
            $where['partner.zone_id'] = $data['streetId'];
            if (strpos($data['streetId'], '@') !== false) {
                @list($zoneId, $stationId) = explode('@', $data['streetId']);
                trim($zoneId) && $where['partner.zone_id'] = intval($zoneId);
                trim($stationId) && $where['partner.station_id'] = intval($stationId);
            }
        }

        // 折扣过滤
        if (isset($data['is_discount']) && trim($data['is_discount'])) {
            $where['partner.is_discount'] = intval($data['is_discount']);
            $where['partner.long'] = array('NEQ', '');
            $where['partner.lat']  = array('NEQ', '');
        }

        // 经纬度条件删选
        if (isset($data['lng']) && trim($data['lng']) && isset($data['lat']) && trim($data ['lat'])) {
            $lng = $data['lng'];
            $lat = $data['lat'];
            if (isset($data['distance']) && trim($data['distance'])) {
                $distance = ternary($data['distance'], 1);
                $lngLatSquarePoint = $this->returnSquarePoint($lng, $lat, $distance);
                $where['_string'] = "partner.`long`>='{$lngLatSquarePoint['left-top']['lng']}' AND partner.lat>='{$lngLatSquarePoint['left-bottom']['lat']}' AND partner.`long`<='{$lngLatSquarePoint['right-bottom']['lng']}' AND partner.`lat`<='{$lngLatSquarePoint['left-top']['lat']}'";
                $having = "distance <= $distance";
            }
            $distanceField = $this->getMysqlDistanceField($lat, $lng);
            $field[$distanceField] = 'distance';
        }
        
        // 排序方式
        $sort = array();
        $joinField = '';
        if (isset($data['order']) && $data['order']) {
            $lastId = ternary($data['lastId'], '');
            switch ($data['order']) {
                // 距离最近
                case 'distance':
                case '+@distance':
                    $sort['distance'] = 'ASC';
                    $sort_filed = 'distance';
                    $order_type = '+';
                    if (!in_array('distance', $field)) {
                        unset($sort['distance']);
                        $sort['sort'] = 'DESC';
                        $sort_filed = 'sort';
                        $order_type = '-';
                    }
                    list($sortField, $joinField) = $this->__getMysqlSortField('sort_order', $order_type, $data);
                    $_having = $this->getMysqlSortWhere($sort_filed, $data['lastId'], 'partner_id', $data['end_id'], $order_type);
                    if (trim($_having)) {
                        $having = trim($having) ? "$having AND $_having" : $_having;
                    }
                    $field[$sortField] = 'sort';
                    unset($data['order']);
                    break;
                default:
                    $_having = '';
                    $sort['sort'] = 'ASC';
                    list($sortField, $joinField) = $this->__getMysqlSortField($data['order'], '+', $data);
                    $_having = $this->getMysqlSortWhere('sort', $data['lastId'], 'partner_id', $data['end_id'], '+');
                    if (strpos($data['order'], '@') !== false) {
                        list($sortType, $_sortField) = explode('@', $data['order']);
                        if (trim($sortType) == '-') {
                            $sort['sort'] = 'DESC';
                            trim($lastId) && $_having = "sort < $lastId";
                            $_having = $this->getMysqlSortWhere('sort', $data['lastId'], 'partner_id', $data['end_id'], '-');
                            list($sortField, $joinField) = $this->__getMysqlSortField($_sortField, '-', $data);
                        }
                    }
                    if (trim($_having)) {
                        $having = trim($having) ? "$having AND $_having" : $_having;
                    }
                    $field[$sortField] = 'sort';
                    break;
            }
        }

        $sort['partner.id'] = 'DESC';
        $partner = M('partner');
        if ($where) {
            //2016.5.24解决搜索问题
            if (isset($data['query']) && trim($data['query'])) {
                unset($where['partner.title']);
            }
            $partner->where($where);
        }
        if (trim($joinField)) {
            $joinField=str_replace("team.team_type = 'normal' OR team.team_type='newuser'","team.team_type = 'normal' OR team.team_type='newuser' OR team.team_type='limited'  OR team.team_type='newguser'",$joinField);//2016.3.30 增加分类搜索里面可以显示限量团单
            $partner->join($joinField);
        }

        if (trim($having)) {
            $partner->having($having);
        }

        $res = $partner->field($field)->order($sort)->group('partner.id')->limit($limit)->select();
   
        if (!$res) {
            $this->errorInfo['info'] = $partner->getDbError();
            $this->errorInfo['sql'] = $partner->_sql();
            return false;
        }

        if ($res) {
            // 获取平均分
            $partner_ids = array();
            foreach ($res as &$v) {
                $partner_ids[$v['partner_id']] = $v['partner_id'];
            }
            unset($v);
            $comment_avg_res = array();
            if ($partner_ids) {
                $field = array(
                    'partner_id' => 'partner_id',
                    'AVG(comment_num)' => 'comment_avg_num',
                );
                $comment_where = array(
                    'partner_id' => array('in', array_keys($partner_ids)),
                    'is_comment' => 'Y',
                    'comment_display' => 'Y',
                );
                $comment_avg_res = M('comment')->field($field)->where($comment_where)->group('partner_id')->select();
            }

            $partner_comment_avg_num = array();
            if ($comment_avg_res) {
                foreach ($comment_avg_res as &$v) {
                    $partner_comment_avg_num[$v['partner_id']] = $v;
                }
                unset($v);
            }
            
            foreach ($res as &$v) {
                $v = array(
                    'partner_id' => ternary($v['partner_id'], ''),
                    'group_id' => ternary($v['group_id'], ''),
                    'part_title' => ternary($v['part_title'], ''),
                    'images' => isset($v['images']) ? getImagePath($v['images']) : '',
                    'lng' => ternary($v['long'], ''),
                    'lat' => ternary($v['lat'], ''),
                    'username' => ternary($v['username'], ''),
                    'distance' => ternary($v['distance'], ''),
                    'comment_avg_num' => isset($partner_comment_avg_num[$v['partner_id']]['comment_avg_num']) ? number_format($partner_comment_avg_num[$v['partner_id']]['comment_avg_num'], 1) : '0.0',
                    'cate_name' => '',
                    'sort' => ternary($v['sort'], ''),
                    'address' => ternary($v['address'], ''),
                    'mobile' => isset($v['mobile']) && trim($v['mobile']) ? ternary($v['mobile'], '') : ternary($v['phone'], ''),
                );
            }
            unset($v);
        }

        return $res;
    }

    /**
     * 获取相同商户的其他团单
     * @param type $where
     * @return type
     */
    public function samePartnerOtherTeam($where, $order, $limit = 20) {

        if (!$order) {
            $order = 'id asc';
        }
        $res = $this->where($where)->order($order)->field($this->teamField)->limit($limit)->select();
        if (!$res) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        return $res;
    }

    /**
     * 附近团单
     * @param type $lng
     * @param type $lat
     * @param type $lastId
     * @param type $distance
     * @return type
     */
    public function nearByTeam20150813($city_id, $lng, $lat, $distance = 1, $lastId = 0, $end_id = '', $limit = 20) {

        // 查询where条件
        $lngLatSquarePoint = $this->returnSquarePoint($lng, $lat, $distance);
        $where = "partner.`long`>='{$lngLatSquarePoint['left-top']['lng']}' AND partner.lat>='{$lngLatSquarePoint['left-bottom']['lat']}' AND partner.`long`<='{$lngLatSquarePoint['right-bottom']['lng']}' AND partner.`lat`<='{$lngLatSquarePoint['left-top']['lat']}'";
        $having = "team_count > 0 AND  distance<=$distance";
        $_having = $this->getMysqlSortWhere('distance', $lastId, 'partner_id', $end_id);
        if (trim($_having)) {
            $having = "$having AND $_having";
        }
        if (trim($city_id)) {
            $_where['partner.city_id'] = intval($city_id);
        }

        // 返回的字段
        $distanceField = $this->getMysqlDistanceField($lat, $lng);
        $field = array(
            'partner.id' => 'partner_id',
            'partner.group_id' => 'group_id',
            'partner.title' => 'part_title',
            'partner.images' => 'images',
            'partner.long' => 'long',
            'partner.lat' => 'lat',
            'IFNULL(AVG(comment.comment_num),0)' => 'comment_avg_num',
            'partner.username' => 'username',
            'count(DISTINCT(team.id))' => 'team_count',
            $distanceField => 'distance'
        );

        // 排序字段
        $sort = array('distance' => 'asc', 'partner_id' => 'desc');
        $partner = M('partner');
        list($_, $joinField) = $this->__getMysqlSortField('sort_order');
        if (trim($joinField)) {
            $partner->join($joinField)->having($having);
        }
        $res = $partner->where($where)->where($_where)->field($field)->order($sort)->join('LEFT JOIN comment ON partner.id=comment.`partner_id`')->group('partner.id,team.`partner_id`')->limit($limit)->select();
        if (!$res) {
            $this->errorInfo['info'] = $partner->getDbError();
            $this->errorInfo['sql'] = $partner->_sql();
            return false;
        }

        return $res;
    }

    /**
     * 附近团单
     * @param type $lng
     * @param type $lat
     * @param type $lastId
     * @param type $distance
     * @return type
     */
    public function nearByTeam($city_id, $lng, $lat, $distance = 1, $lastId = 0, $end_id = '', $limit = 20) {

        // 查询where条件
        $where = array();
        if (trim($city_id)) {
            $where['partner.city_id'] = intval($city_id);
        }

        $lngLatSquarePoint = $this->returnSquarePoint($lng, $lat, $distance);
        $where['_string'] = "partner.`long`>='{$lngLatSquarePoint['left-top']['lng']}' AND partner.lat>='{$lngLatSquarePoint['left-bottom']['lat']}' AND partner.`long`<='{$lngLatSquarePoint['right-bottom']['lng']}' AND partner.`lat`<='{$lngLatSquarePoint['left-top']['lat']}'";
        $having = "team_count > 0 AND  distance<=$distance";
        $_having = $this->getMysqlSortWhere('distance', $lastId, 'partner_id', $end_id);
        if (trim($_having)) {
            $having = "$having AND $_having";
        }

        // 返回的字段
        $distanceField = $this->getMysqlDistanceField($lat, $lng);
        $field = array(
            'partner.id' => 'partner_id',
            'partner.group_id' => 'group_id',
            'partner.title' => 'part_title',
            'partner.images' => 'images',
            'partner.long' => 'long',
            'partner.lat' => 'lat',
            'partner.username' => 'username',
            'count(team.id)' => 'team_count',
            $distanceField => 'distance'
        );

        // 排序字段
        $sort = array('distance' => 'asc', 'partner_id' => 'desc');
        $partner = M('partner');
        list($_, $joinField) = $this->__getMysqlSortField('sort_order');
        if (trim($joinField)) {
            $partner->join($joinField)->having($having);
        }
        $res = $partner->where($where)->field($field)->order($sort)->group('team.`partner_id`')->limit($limit)->select();
        if (!$res) {
            $this->errorInfo['info'] = $partner->getDbError();
            $this->errorInfo['sql'] = $partner->_sql();
            return false;
        }
        if ($res) {
            // 获取平均分
            $partner_ids = array();
            foreach ($res as &$v) {
                $partner_ids[$v['partner_id']] = $v['partner_id'];
            }
            unset($v);
            $comment_avg_res = array();
            if ($partner_ids) {
                $field = array(
                    'partner_id' => 'partner_id',
                    'AVG(comment_num)' => 'comment_avg_num',
                );
                $comment_where = array(
                    'partner_id' => array('in', array_keys($partner_ids)),
                    'is_comment' => 'Y',
                    'comment_display' => 'Y',
                );
                $comment_avg_res = M('comment')->field($field)->where($comment_where)->group('partner_id')->select();
            }

            $partner_comment_avg_num = array();
            if ($comment_avg_res) {
                foreach ($comment_avg_res as &$v) {
                    $partner_comment_avg_num[$v['partner_id']] = $v;
                }
                unset($v);
            }
            foreach ($res as &$v) {
                $v = array(
                    'partner_id' => ternary($v['partner_id'], ''),
                    'group_id' => ternary($v['group_id'], ''),
                    'part_title' => ternary($v['part_title'], ''),
                    'images' => isset($v['images']) ? getImagePath($v['images']) : '',
                    'lng' => ternary($v['long'], ''),
                    'lat' => ternary($v['lat'], ''),
                    'username' => ternary($v['username'], ''),
                    'distance' => ternary($v['distance'], ''),
                    'comment_avg_num' => isset($partner_comment_avg_num[$v['partner_id']]['comment_avg_num']) ? number_format($partner_comment_avg_num[$v['partner_id']]['comment_avg_num'], 1) : '0.0',
                    'mobile' => isset($v['mobile']) && trim($v['mobile']) ? ternary($v['mobile'], '') : ternary($v['phone'], ''),
                );
            }
            unset($v);
        }
        return $res;
    }

    /**
     * 今日新单/今日推荐新单
     */
    public function todayRecommendTeam($where = array(), $lastId = '', $end_id = '', $order = '', $limit = 20) {

        // 排序条件
        $_where = "begin_time<end_time";
        if (trim($lastId) != '') {
            $_strWhere = $this->getSearchSortWhere('begin_time', $lastId, $end_id, '-');
            if (trim($order) == 'sort_order') {
                $_strWhere = $this->getSearchSortWhere('sort_order', $lastId, $end_id, '-');
            }
            $_where = "$_where AND $_strWhere";
        }
        $this->where($_where);
        if ($where) {
            $this->where($where);
        }
        $res = $this->order("$order desc,id desc")->field($this->teamField)->limit($limit)->select();

        if (!$res) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        return $res;
    }

    /**
     * 秒杀团单
     */
    public function secondKillTeam($where, $order = '', $limit = 20) {

        if (!trim($order)) {
            $order = 'id asc';
        }
        $res = $this->where("flv='Y' OR end_time>" . time())->where($where)->order($order)->field($this->teamField)->limit($limit)->select();
        if (!$res) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        return $res;
    }

    /**
     * 获取团单列表
     */
    public function getTeamListByWhere($where, $order = '', $limit = 20) {

        if (!trim($order)) {
            $order = 'id asc';
        }

        $res = $this->where($where)->field($this->teamField)->limit($limit)->order($order)->select();
        if (!$res) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        return $res;
    }

    /**
     * 团单购买
     * @param type $uid
     * @param type $id
     * @param type $mobile
     * @param type $plat
     */
    public function teamBuy($uid, $id, $num = 1, $mobile, $plat = '', $uniq_identify = '', $ver = '') {

        $plat = strtolower(trim($plat));
        // 获取商品的基本信息
        $team = M('team');
        $teamRes = $this->where(array('id' => $id))->find();
        if (!$teamRes) {
            return array('error' => '你购买的团单不存在');
        }

        // 团单时间限制
        $nowTime = time();
        $beginTime = $teamRes['begin_time'];
        $endTime = $teamRes['end_time'];
        if (isset($teamRes['team_type']) && $teamRes['team_type'] == 'timelimit' && isset($teamRes['flv']) && strtolower(trim($teamRes['flv'])) == 'y') {
            $beginTime = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $beginTime));
            $endTime = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $endTime));
        }
        if (isset($teamRes['team_type']) && $teamRes['team_type'] == 'cloud_shopping') {
            $endTime = strtotime('+5 year');
        }
        if ($nowTime < $beginTime || $nowTime > $endTime) {
            return array('error' => '你购买的团单未在购买时间范围内');
        }

        // 查询订单
        $order = M('order');
        $where = array('team_id' => $id, 'user_id' => $uid, 'state' => 'pay');
        $orderCount = $order->where($where)->sum('quantity');

        // 是否只能购买一次
        if (isset($teamRes['buyonce']) && strtolower(trim($teamRes['buyonce'])) == 'y' && $orderCount > 0) {
            return array('error' => '该团单只能购买一次');
        }
        // 最低购买的份数限制
        if (isset($teamRes['permin_number']) && intval($teamRes['permin_number']) > 0 && intval($teamRes['permin_number']) > $num) {
            return array('error' => '该团单最低购买' . $teamRes['permin_number'] . '份');
        }

        if (isset($teamRes['per_number']) && intval($teamRes['per_number']) > 0 && intval($teamRes['per_number']) < intval($num + $orderCount)) {
            // 每人限购的份数判断//2016.4.6增加&& strtolower(trim($teamRes['flv'])) == 'n' 如果是每天活动的团单不进入次此判断
            if(strtolower(trim($teamRes['team_type']))=='limited'  && strtolower(trim($teamRes['flv'])) == 'y'){
                  if(intval($teamRes['per_number']) < intval($num)){
                      return array('error' => '该团单每人每天限购' . $teamRes['per_number'] . '份');
                  }
            }else{
                return array('error' => '该团单每人限购' . $teamRes['per_number'] . '份');
            }

        }

        // 判断商品数量充足
        if (isset($teamRes['now_number']) && isset($teamRes['max_number']) && intval($teamRes['max_number']) > 0 && intval($num + $teamRes['now_number']) > intval($teamRes['max_number'])) {
            if(strtolower(trim($teamRes['team_type']))=='limited'  && strtolower(trim($teamRes['flv'])) == 'y'){
                if(intval($num) > intval($teamRes['max_number'])){
                    return array('error' => '该团单数量不足！');
                }
            }else{
                return array('error' => '该团单数量不足！');
            }

        }

        // 根据团单类型 处理不同团单
        $origin = ternary($teamRes['team_price'], 0) * $num;
        $fare = 0; // 邮费

        // 是否OTA团单
        $ota = D('Ota');
        $parkcode = $ota->tmCheck($id);
        $teamRes['team_type'] = $parkcode ? 'ota' : $teamRes['team_type'];

        $teamType = isset($teamRes['team_type']) ? strtolower(trim($teamRes['team_type'])) : '';
        switch ($teamType) {
            case 'ota':
                $otares = $ota->products($parkcode);
                if (!$otares) {
                    return array('error' => '获取资源失败，暂时无法购买！');
                }
                break;
            case 'normal':

                break;
            case 'newuser':

                // 判断是否为新用户
                $where = array('user_id' => $uid,'team_id'=>$id,'state'=>'pay');
                $where['_string'] = "(state='pay' or rstate<>'normal')";
                $newUser = $order->where($where)->count();

                // 兼容老的程序 2016.6.11修改
                /*if ($newUser && $newUser > 0) {
                    return array('error' => '你不是新用户，不能购买此商品！');
                }*/
                if (trim($uniq_identify)) {
                    $where = array('device_uniq_identify' => $uniq_identify);
                    $where['_string'] = "(state='pay' or rstate<>'normal')";
                    $newUser = $order->where($where)->count();
                    if ($newUser && $newUser > 0) {
                        //return array('error' => '该设备已经享受过新用户独享的团单，不能继续购买！');
                    }
                }

                if ($newUser == 0 && ($plat == 'ios' || $plat == 'android') && isset($teamRes['lottery_price']) && $teamRes['lottery_price'] > 0) {
                    $new_user_reduce_price = ternary($teamRes['team_price'], 0) - $teamRes['lottery_price'];
                    if ($new_user_reduce_price > 0) {
                        $origin = $origin - $new_user_reduce_price;
                    }
                }
                break;
            case 'newguser':

                // 判断是否为新新客
                $where = array('user_id' => $uid,'partner_id'=>$teamRes['partner_id'],'team_id'=>$id,'state'=>'pay');
                $where['_string'] = "(state='pay' or rstate<>'normal')";
                $newGuset = $order->where($where)->count();


                // 兼容老的程序
                /*注释了可以购买多份第二份按照正常价格2016.4.23
                if ($newGuset && $newGuset > 0) {
                    return array('error' => '你不是新用户，不能购买此商品！');
                }
                if (trim($uniq_identify)) {
                    $where = array('device_uniq_identify' => $uniq_identify);
                    $where['_string'] = "(state='pay' or rstate<>'normal')";
                    $newGuset = $order->where($where)->count();
                    if ($newGuset && $newGuset > 0) {
                        return array('error' => '该设备已经享受过新用户独享的团单，不能继续购买！');
                    }
                }*/

                if ($newGuset == 0 && ($plat == 'ios' || $plat == 'android') && isset($teamRes['lottery_price']) && $teamRes['lottery_price'] > 0) {
                    $new_guser_reduce_price = ternary($teamRes['team_price'], 0) - $teamRes['lottery_price'];
                    if ($new_guser_reduce_price > 0) {
                        $origin = $origin - $new_guser_reduce_price;
                    }
                }
                break;
            case 'limited':

                // 查看每人限购的数量
                $where = array('team_id' => $id, 'state' => 'pay', 'user_id' => $uid);
                $error='该团单每人限购';
                if (isset($teamRes['flv']) && strtolower(trim($teamRes['flv'])) == 'y') {
                    $where['pay_time'] = array('EGT',strtotime(date('Y-m-d')));
                    $error='该团单每人每天限购';
                }
                $userCount = $order->where($where)->sum('quantity');
                if (isset($teamRes['per_number']) && $userCount > intval($teamRes['per_number'])) {
                    return array('error' => $error . $teamRes['per_number'] . '份');
                }

                // 查看该商品限购数量
                unset($where['user_id']);
                $payCount = $order->where($where)->sum('quantity');
                if (isset($teamRes['max_number']) && trim($teamRes['max_number']) && $payCount >= $teamRes['max_number']) {
                    return array('error' => '该团单数量不足！');
                }
                $expire_time = time() - self::LIMITED_ORDER_TIME_OUT;
                $where['state'] = 'unpay';
                $where['create_time'] = array('EGT' => $expire_time);
                if (isset($where['pay_time'])) {
                    unset($where['pay_time']);
                }
                $unpayCount = $order->where($where)->sum('quantity');
                if (isset($teamRes['max_number']) && $unpayCount + $payCount >= $teamRes['max_number']) {

                    return array('error' => '该团单最大库存不足！');
                }
                break;
            case 'timelimit':
                // 秒杀团单购买！
                break;
            case 'goods':
//                if($plat != 'android' && $plat != 'ios' ){
//                    return array('error'=>'该订单不支持电脑端购买，请下载手机app购买！');
//                }
                // 邮购购买！
                if (isset($teamRes['fare']) && $teamRes['fare'] > 0) {
                    $fare = $teamRes['fare'];
                    $origin = $origin + $fare;
                }
                break;
            case 'cloud_shopping':

                if (isset($teamRes['now_periods_number']) && isset($teamRes['max_periods_number']) && intval($teamRes['max_periods_number']) < intval($teamRes['now_periods_number'])) {
                    return array('error' => '云购期数已满！');
                }
                $now_periods_number = intval($teamRes['now_periods_number']);
                if ($now_periods_number > 1) {
                    $where = array(
                        'team_id' => $teamRes['id'],
                        'periods_number' => intval($now_periods_number - 1)
                    );
                    $cloud_shoping_result_res = M('cloud_shoping_result')->where($where)->field('begin_time')->find();
                    $now_time = time();
                    if (isset($cloud_shoping_result_res['begin_time']) && trim($cloud_shoping_result_res['begin_time']) && $cloud_shoping_result_res['begin_time'] > $now_time) {
                        return array('error' => '本期云购还未开始，请稍后购买！');
                    }
                }

                //连接redis
                $redis = new \Common\Org\phpRedis('pconnect');
                $key = getCloudShopingRedisKey("yungou_incr_" . md5("{$id}_{$teamRes['now_periods_number']}"));
                $now_number = $redis::$redis->get($key);
                if ($now_number && isset($teamRes['max_number']) && intval($teamRes['max_number']) > 0 && intval($num + $now_number) > intval($teamRes['max_number'])) {
                    return array('error' => '该团单数量不足！');
                }
                $teamRes['team_price'] = 1;
                $origin = ternary($teamRes['team_price'], 0) * $num;
                $teamRes['product'] =ternary($teamRes['title'], '一元众筹');
                break;
            default:
                //  return array('error' => '非法团单类型');
                break;
        }

        // 下单入库操作
        $addOrderRes = $this->addOrder($uid, $id, $num, $mobile, $plat, $origin, $uniq_identify, $fare);
        if (!$addOrderRes) {
            return array('error' => '下单入库失败');
        }
        list($orderId, $service, $credit, $money, $orderRes) = $addOrderRes;

        // 判断是否使用抵金券
        $is_use_card = 'N';
        $vouchers = D('Card')->isUseCard($teamRes['city_id'], $teamType);
        if ($vouchers) {
            $is_use_card = 'Y';
        }
        if ($teamType == 'cloud_shopping') {
            $is_use_card = 'N';
        }

        $card_id = '';
        $card_money = 0;
        //非手机端提交订单抵金券设置为空
        if ($plat != 'ios' && $plat != 'android') {
            $this->updateTeamBuy($orderId, '', $uid);
            $is_use_card = 'N';
        } else if (isset($orderRes['card_id']) && trim($orderRes['card_id'])) {
            $updateTeamBuyres = $this->updateTeamBuy($orderId, $orderRes['card_id'], $uid);
            $card_id = ternary($updateTeamBuyres['card_id'], $card_id);
            $card_money = ternary($updateTeamBuyres['card_money'], $card_money);
            $money = ternary($updateTeamBuyres['money'], $money);
            $service = ternary($updateTeamBuyres['service'], $service);
        }

        // 返回支付信息
        $data = array(
            'order_id' => $orderId,
            'partner_id' => $teamRes['partner_id'],
            'team_price' => $teamRes['team_price'],
            'is_use_card' => $is_use_card,
            'all_price' => $origin,
            'num' => $num,
            'product' => $teamRes['product'],
            'credit' => $credit,
            'money' => $money,
            'card_id' => $card_id,
            'fare' => $fare,
            'card_money' => $card_money,
            'pay_type' => array()
        );

        // 现金支付
        if (trim($service) == 'credit') {
            $data['creditpay'] = true;
            return $data;
        }

        // 其他方式支付
        if($ver){
            $data['wxnpay'] = C('WX_NewPAY');
        }else{
            $data['wechatpay'] = C('WX_PAY');
        }
        $data['unionpay'] = C('UNION_PAY');
        $data['lianlianpay'] = C('LIANLIAN_PAY');
        $data['alipay'] = C('ALI_PAY');
        $data['tenpay'] = C('TEN_PAY');
        $data['umspay'] = C('UMS_PAY');
        $data['wapepay'] = C('E_PAY');
        if($plat=='ios'){
            $wepay = false;
        }else{
            $wepay = C('WE_PAY');
        }
        $data['wepay'] = $wepay;
        if($ver){
            $data['pay_type'] = array(
                array('alipay' => C('ALI_PAY')),
                array('wxnpay' => C('WX_NewPAY')),
                array('unionpay' => C('UNION_PAY')),
                array('lianlianpay' => C('LIANLIAN_PAY')),
                array('tenpay' => C('TEN_PAY')),
                array('umspay' => C('UMS_PAY')),
                array('wapepay' => C('E_PAY')),
                array('wepay' => $wepay),
            );
        }else{
            $data['pay_type'] = array(
                array('alipay' => C('ALI_PAY')),
                array('wechatpay' => C('WX_PAY')),
                array('unionpay' => C('UNION_PAY')),
                array('lianlianpay' => C('LIANLIAN_PAY')),
                array('tenpay' => C('TEN_PAY')),
                array('umspay' => C('UMS_PAY')),
                array('wapepay' => C('E_PAY')),
                array('wepay' => $wepay),
            );
        }


        return $data;
    }

    /**
     * 下单入库操作
     */
    public function addOrder($uid, $id, $num = 1, $mobile, $plat = '', $origin = 0, $uniq_identify = '', $fare = 0) {

        $sourcePlat = strtolower(trim($plat));
        if (!trim($plat)) {
            $sourcePlat = '-未知来源-' . $plat;
        }
        $teamRes = $this->where(array('id' => $id))->find();
        if (!$teamRes) {
            return false;
        }

        // 活动期间按照活动价格下单
//        if(isset($teamRes['activities_id']) && trim($teamRes['activities_id'])){
//            $nowTime = time();
//            $activities_where = array(
//                'id' => $teamRes['activities_id'],
//                'type' => 'activities',
//                'begin_time'=>array('lt',$nowTime),
//                'end_time' => array('gt', $nowTime)
//            );
//            $is_exist_activies =D('Admanage')->isExistActivities($activities_where);
//            if($is_exist_activies && $is_exist_activies>0){
//                if(isset($teamRes['lottery_price']) && $teamRes['lottery_price']>0){
//                     $teamRes['team_price'] = $teamRes['lottery_price'];
//                     $origin = sprintf("%.2f",(ternary($teamRes['team_price'], 0) * $num)+$fare);
//                }
//            }
//
//        }
        // 获取用户真实ip和所在城市
        $user_buy_ip = '';
        $user_buy_city_name = '';
        // 一元云购 价格调整
        if (isset($teamRes['team_type']) && trim($teamRes['team_type']) == 'cloud_shopping') {
            if ($teamRes['team_price'] > 0) {
                $teamRes['ucaii_price'] = sprintf("%.2f", $teamRes['ucaii_price'] / $teamRes['team_price']);
            }
            $teamRes['team_price'] = 1;

            $user_buy_ip = get_client_ip(0, true);
            if ($user_buy_ip) {
                $expressQuery = new \Common\Org\ExpressQuery();
                $user_buy_city_name = $expressQuery->getIPLoc_sina($user_buy_ip);
            }
        }

        if (!$origin) {
            $origin = ternary($teamRes['team_price'], 0) * $num;
        }

        // 付款方式
        $user = M('user');
        $userRes = $user->where(array('id' => $uid))->find();
        $service = 'tenpay';
        $credit = 0;
        if (isset($userRes['money']) && $userRes['money'] > 0) {
            $credit = $userRes['money'];
            if ($userRes['money'] >= $origin) {
                $credit = $origin;
                $service = 'credit';
            }
        }

        if($origin <= 0){
            $credit = 0;
            $service = 'credit';
        }

        // 查看该用户 该产品是否存在未付款订单
        $order = M('order');
        $where = array('team_id' => $id, 'user_id' => $uid, 'state' => 'unpay', 'rstate' => 'normal', 'is_display' => 'Y');
        $orderRes = $order->where($where)->field('id')->find();
        $orderId = '';

        // 开启事务回滚
        $model = M();
        $model->startTrans();
        $money = sprintf("%.2f", $origin - $credit);
        $randId = strtr($money, array('.' => '-', ',' => '-'));
        if ($orderRes) {
            // 更新该订单
            $orderId = ternary($orderRes['id'], '');
            $updateData = array(
                'pay_id' => "go-$orderId-$num-$randId",
                'quantity' => $num,
                'origin' => $origin,
                'city_id' => ternary($teamRes['city_id'], 0),
                'team_type' => ternary($teamRes['team_type'], 0),
                'price' => ternary($teamRes['team_price'], 0),
                'ucaii_price' => ternary($teamRes['ucaii_price'], 0),
                'mobile' => $mobile,
                'now_periods_number' => ternary($teamRes['now_periods_number'], 1),
                'service' => $service,
                'device_uniq_identify' => $uniq_identify,
                'credit' => $credit,
                'laiyuan' => $sourcePlat,
                'money' => $money,
                'fare' => $fare,
                'express' => 'N',
                'yuming' => $sourcePlat,
            );
            if ($plat != 'ios' && $plat != 'android' && $plat != 'wap') {
                unset($updateData['pay_id']);
            }

            if (!trim($uniq_identify)) {
                unset($updateData['device_uniq_identify']);

            }

            // 添加购买时的ip 和城市名称
            if ($user_buy_ip) {
                $updateData['user_buy_ip'] = $user_buy_ip;
                $updateData['user_buy_city_name'] = $user_buy_city_name;
            }

            //2016-04-21 daipignshan 增加分销处理情况
            $openid = session('wx_share_openid');
            if($openid){
                session('wx_share_openid',null);
                $updateData['openid'] = $openid;
            }

            // 更新订单
            $res = $order->where(array('id' => $orderId))->save($updateData);
            if ($res === false) {
                $this->errorInfo['info'] = $order->getDbError();
                $this->errorInfo['sql'] = $order->_sql();
                $model->rollback();
                return false;
            }

        } else {
            // 添加订单
            $data = array(
                'user_id' => $uid,
                'allowrefund' => $teamRes['allowrefund'],
                'quantity' => $num,
                'team_id' => $id,
                'city_id' => ternary($teamRes['city_id'], 0),
                'partner_id' => ternary($teamRes['partner_id'], 0),
                'price' => ternary($teamRes['team_price'], 0),
                'ucaii_price' => ternary($teamRes['ucaii_price'], 0),
                'team_type' => ternary($teamRes['team_type'], 0),
                'now_periods_number' => ternary($teamRes['now_periods_number'], 1),
                'origin' => $origin,
                'yuming' => $sourcePlat,
                'laiyuan' => $sourcePlat,
                'mobile' => $mobile,
                'state' => 'unpay',
                'credit' => $credit,
                'money' => $money,
                'fare' => $fare,
                'service' => $service,
                'express' => 'N',
                'device_uniq_identify' => $uniq_identify,
                'create_time' => time()
            );
            if (!trim($uniq_identify)) {
                unset($data['device_uniq_identify']);
            }

            //2016-04-21 daipignshan 增加分销处理情况
            $openid = session('wx_share_openid');
            if($openid){
                session('wx_share_openid',null);
                $data['openid'] = $openid;
            }

            // 添加购买时的ip 和城市名称
            if ($user_buy_ip) {
                $updateData['user_buy_ip'] = $user_buy_ip;
                $updateData['user_buy_city_name'] = $user_buy_city_name;
            }
            $orderId = $order->add($data);
            if (!$orderId) {
                $this->errorInfo['info'] = $order->getDbError();
                $this->errorInfo['sql'] = $order->_sql();
                $model->rollback();
                return false;
            }

            $updatedata = array('pay_id' => "go-$orderId-$num-$randId");
            $res = $order->where(array('id' => $orderId))->save($updatedata);
            if (!$res) {
                $this->errorInfo['info'] = $order->getDbError();
                $this->errorInfo['sql'] = $order->_sql();
                $model->rollback();
                return false;
            }

            // ota占用订单
            $ota = D('Ota');
            $parkcode = $ota->tmCheck($id);
            $otaorder = $data;
            $otaorder['order_id'] = $orderId;
            $otaorder = array_merge($otaorder,$ota->getTmpinfo());
            if ($parkcode) {
                $otares = $ota->products($parkcode);
                if (!$otares) {
                    $model->rollback();
                    return false;
                }
                $res = $ota->orderLock(array_pop($otares), $otaorder);
                if (!$res) {
                    $model->rollback();
                    return false;
                }
            }
        }

        // 纪录客户端下单纪录
        $referer = M('referer');
        $data = array('user_id' => $uid, 'order_id' => $orderId);
        $refererRes = $referer->where($data)->find();
        $data['referer'] = $sourcePlat;
        if ($refererRes) {
            $res = $referer->where(array('user_id' => $uid, 'order_id' => $orderId))->save($data);
        } else {
            $data = array(
                'user_id' => $uid,
                'order_id' => $orderId,
                'referer' => $sourcePlat,
                'create_time' => time()
            );
            $res = $referer->add($data);
        }
        if ($res === false) {
            $this->errorInfo['info'] = $referer->getDbError();
            $this->errorInfo['sql'] = $referer->_sql();
            $model->rollback();
            return false;
        }

        // 提交事务
        $model->commit();

        return array($orderId, $service, $credit, $money, $orderRes);
    }

    /**
     * 选择抵金券更新团单
     * @param type $order_id
     * @param type $card_id
     */
    public function updateTeamBuy($order_id, $card_id, $uid) {

        if (!trim($order_id) || !trim($uid)) {
            return false;
        }
        $order = M('order');
        $orderRes = $order->where(array('id' => $order_id, 'state' => 'unpay', 'user_id' => $uid))->find();
        if (!$orderRes) {
            return false;
        }
        $card = M('card');
        $cardRes = array();
        if (trim($card_id)) {
            $cardRes = $card->where(array('id' => $card_id))->find();
            if (!$cardRes) {
                return false;
            }
        }

        $user = M('user');
        $userRes = $user->where(array('id' => $uid))->find();

        $card_money = ternary($cardRes['credit'], 0);
        $user_money = ternary($userRes['money'], 0);
        $order_origin = ternary($orderRes['origin'], 0);

        $card_max_money = D('Card')->getMoneyUseRules($order_origin);
        if (!$card_max_money || $card_money > $card_max_money) {
            return false;
        }

        $money = $order_origin;
        $credit = 0;
        $service = 'tenpay';
        // 使用抵金券
        if ($card_money > 0 && $order_origin > $card_money) {
            $money = sprintf("%.2f", $order_origin - $card_money);
        }
        if ($card_money > 0 && $order_origin <= $card_money) {
            $money = 0;
            $service = 'credit';
        }

        // 使用余额
        if ($money > 0 && $user_money > 0 && $user_money >= $money) {
            $credit = $money;
            $money = 0;
            $service = 'credit';
        }
        if ($money > 0 && $user_money > 0 && $user_money < $money) {
            $credit = $user_money;
            $money = sprintf("%.2f", $money - $user_money);
        }

        // 开启事务
        $model = M();
        $model->startTrans();

        // 如果原来绑定抵金券，则修改元抵金券的状态
        if (isset($orderRes['card_id']) && trim($orderRes['card_id'])) {
            $res = $card->where(array('id' => $orderRes['card_id']))->save(array('consume' => 'N', 'use_order_id' => 0, 'pay_time' => 0));
            if ($res === false) {
                $model->rollback();
                return false;
            }
        }

        // 修改当前抵金券
        $res = $card->where(array('id' => $card_id))->save(array('consume' => 'Y', 'use_order_id' => $order_id, 'pay_time' => time()));
        if ($res === false) {
            $model->rollback();
            return false;
        }
        $randId = strtr(sprintf("%.2f", $money), array('.' => '-', ',' => '-'));
        $data = array(
            'pay_id' => "go-$order_id-{$orderRes['quantity']}-$randId",
            'service' => $service,
            'credit' => $credit,
            'money' => $money,
            'card_id' => $card_id,
            'card' => $card_money,
        );
        $res = $order->where(array('id' => $order_id))->save($data);
        if ($res === false) {
            $model->rollback();
            return false;
        }
        $model->commit();

        // 返回支付信息
        $data = array(
            'order_id' => $order_id,
            'credit' => $credit,
            'service' => $service,
            'money' => $money,
            'card_id' => $card_id,
            'card_money' => $card_money,
            'pay_type' => array(),
        );
        if (trim($service) == 'credit') {
            $data['creditpay'] = true;
            return $data;
        }

        // 其他方式支付
        $data['wechatpay'] = C('WX_PAY');
        $data['unionpay'] = C('UNION_PAY');
        $data['lianlianpay'] = C('LIANLIAN_PAY');
        $data['alipay'] = C('ALI_PAY');
        $data['tenpay'] = C('TEN_PAY');
        $data['umspay'] = C('UMS_PAY');
        $data['wapepay'] = C('E_PAY');
        $data['wepay'] = C('WE_PAY');

        $data['pay_type'] = array(
            array('alipay' => C('ALI_PAY')),
            array('wechatpay' => C('WX_PAY')),
            array('unionpay' => C('UNION_PAY')),
            array('lianlianpay' => C('LIANLIAN_PAY')),
            array('tenpay' => C('TEN_PAY')),
            array('umspay' => C('UMS_PAY')),
            array('wapepay' => C('E_PAY')),
            array('wepay' => C('E_PAY')),
        );

        return $data;
    }

    /**
     * 邮购订单属性设置
     * @param type $uid
     * @param type $order_id
     * @param type $address_id
     * @param type $delivery_time
     * @param type $goods
     */
    public function mailTeamBuyUpdateOrder($uid, $order_id, $address_id, $delivery_time, $goods) {
        if (!$uid) {
            return array('error' => '用户未登录');
        }
        if (!$order_id) {
            return array('error' => '下单失败！');
        }
        if (!$address_id) {
            return array('error' => '地址为空');
        }
        if (!$delivery_time) {
            return array('error' => '送货时间为空！');
        }

        $order_count = M('order')->where(array('id' => $order_id, 'user_id' => $uid))->count();
        if (!$order_count || $order_count < 0) {
            return array('error' => '下单失败！');
        }

        // 拼接地址
        $address_res = M('address')->where(array('id' => $address_id, 'user_id' => $uid))->find();
        $address = @json_encode($address_res);
        $data = array(
            'address' => $address,
            'address_id' => $address_id,
            'delivery_time' => $delivery_time,
            'optional_model' => $goods,
            'express' => 'Y'
        );
        $res = M('order')->where(array('id' => $order_id, 'user_id' => $uid))->save($data);
        if ($res === false) {
            return array('error' => '下单失败！');
        }
        return $data;
    }

    /**
     * 验证支付方式
     */
    public function isPayAction($payAction) {

        if (!trim($payAction)) {
            return false;
        }
        return isset($this->_payAction[$payAction]);
    }

    /**
     * 团单付款
     * @param type $uid
     * @param type $orderId
     * @param type $payAction
     * @param type $plat
     * @return type
     */
    public function teamPay($uid, $orderId, $payAction, $plat) {

        // 获取团单信息
        $order = M('order');
        $orderRes = $order->where(array('id' => $orderId, 'user_id' => $uid))->find();
        if (!$orderRes) {
            return array('error' => '订单不存在！');
        }

        if (!isset($orderRes['state']) || strtolower(trim($orderRes['state'])) == 'pay') {
            return array('error' => '该订单已经支付，不能重复支付！');
        }

        $teamId = $orderRes['team_id'];
        $team = M('team');
        $teamRes = $team->where(array('id' => $teamId))->find();
        if (!trim($teamId) || !$teamRes) {
            return array('error' => '购买的该团单不存在！');
        }

        // 团单时间判断
        $nowTime = time();
        $beginTime = $teamRes['begin_time'];
        $endTime = $teamRes['end_time'];
        if (isset($teamRes['team_type']) && $teamRes['team_type'] == 'timelimit' && isset($teamRes['flv']) && strtolower(trim($teamRes['flv'])) == 'y') {
            $beginTime = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $beginTime));
            $endTime = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $endTime));
        }

        if (isset($teamRes['team_type']) && $teamRes['team_type'] == 'cloud_shopping') {
            $endTime = strtotime('+5 year');
        }

        if ($nowTime < $beginTime || $nowTime > $endTime) {
            return array('error' => '现在不是购买的时间！');
        }

        // 判断商品数量充足
//        if (isset($teamRes['now_number']) && isset($teamRes['max_number']) && trim($teamRes['max_number']) && isset($orderRes['quantity']) && $orderRes['quantity'] + $teamRes['now_number'] > $teamRes['max_number']) {
//            return array('error' => '产品余额不足！');
//        }
        // 重新计算总金额
        $money = $orderRes['origin'] - $orderRes['credit'];
        if (isset($orderRes['card_id']) && trim($orderRes['card_id'])) {
            $cardRes = M('card')->where(array('id' => $orderRes['card_id']))->find();
            $card_money = ternary($cardRes['credit'], 0);
            if ($card_money > 0) {
                $money = $money - $card_money;
            }
        }

        $pay_id = $orderRes['pay_id'];

        // 根据非法支付方式获取相关参数信息
        $data = array('order_id' => $orderId, 'payAction' => $payAction);
        $pay = new \Common\Org\Pay();
        $payNew = new \Common\Org\PayNew();
        switch ($payAction) {
            case 'tenpay':

                // 判断该平台是否支持财付通支付
                if (!C('TEN_PAY')) {
                    return array('error' => '不支持财付通支付！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getTenPayData($pay_id, $teamRes['title'], $teamRes['product'], $payFee, $plat, $orderId);
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'alipay':

                // 判断该平台是否支持支付宝支付
                if (!C('ALI_PAY')) {
                    return array('error' => '不支持支付宝支付！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getALiPayData($pay_id, $teamRes['title'], $teamRes['product'], $payFee, $plat);
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'umspay':

                // 判断该平台是否支持全民付支付
                if (!C('UMS_PAY')) {
                    return array('error' => '不支持全民付支付！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getUmsPayData($pay_id, $teamRes['title'], $teamRes['product'], $payFee, $plat);
                if (!isset($payRes['content']) || !isset($payRes['transId'])) {
                    return false;
                }
                $data['pay_url_params'] = array('payKey' => $payRes['content']);
                // 全民付 保存TransId
                $res = $order->where(array('id' => $orderRes['id']))->save(array('trade_no' => $payRes['transId']));
                if ($res === false) {
                    return array('error' => '全民付支付参数获取失败！');
                }
                break;
            case 'wechatpay':

                // 判断该平台是否支持微信支付
                if (!C('WX_PAY')) {
                    return array('error' => '不支持微信支付！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getWXPayData($pay_id, $teamRes['title'], $teamRes['product'], $payFee, $plat);
                if (isset($payRes['error'])) {
                    return $payRes;
                }
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'wxnpay':

                // 判断该平台是否支持微信支付
                if (!C('WX_NewPAY')) {
                    return array('error' => '不支持微信支付！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $payNew->setNewWXPayData($pay_id, $teamRes['title'], $teamRes['product'], $payFee, $plat);
                if (isset($payRes['error'])) {
                    return $payRes;
                }
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'unionpay':

                // 判断该平台是否支持银联支付支付
                if (!C('UNION_PAY')) {
                    return array('error' => '不支持银联支付！');
                }
                $pay_id = str_replace('-', 'U', $orderRes['pay_id']);
                $res = $order->where(array('id' => $orderId, 'user_id' => $uid))->save(array('pay_id' => $pay_id));
                if ($res === false) {
                    return array('error' => '支付id更新失败！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getAppUnionPayData($pay_id, $teamRes['title'], $teamRes['product'], $payFee, $plat);
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'lianlianpay':

                // 判断该平台是否支持连连支付支付
                if (!C('LIANLIAN_PAY')) {
                    return array('error' => '不支持连连支付！');
                }
                // 获取用户信息
                $user_info = M('user')->where(array('id' => $uid))->field('id,create_time,mobile')->find();
                if (isset($orderRes['mobile']) && trim($orderRes['mobile'])) {
                    $user_info['mobile'] = $orderRes['mobile'];
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getAppLianlianPayData($pay_id, $teamRes['title'], $teamRes['product'], $payFee, $user_info);
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'wepay':
                // 判断该平台是否支京东支付
                if (!C('WE_PAY')) {
                    return array('error' => '不支持京东支付！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getWePayData($pay_id, $teamRes['title'], $teamRes['product'], $payFee, $plat, $orderId);
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'wapepay':
                return false;
                break;
            case 'creditpay':
                // 余额支付
                if (isset($orderRes['state']) && strtolower(trim($orderRes['state'])) == 'pay') {
                    return array('error' => '该订单已经支付，不能重复支付！');
                }
                $user = M('user');
                $userRes = $user->where(array('id' => $uid))->field(array('money'))->find();
                if (!$userRes) {
                    return array('error' => '该用户不存在！');
                }
                $card_money = 0;
                if (isset($orderRes['card_id']) && trim($orderRes['card_id'])) {
                    $cardRes = M('card')->where(array('id' => $orderRes['card_id']))->getField('credit');
                    if ($cardRes) {
                        $card_money = $cardRes;
                    }
                }
                if (!isset($userRes['money']) || $userRes['money'] < 0 || !isset($orderRes['origin']) || sprintf("%.2f", $userRes['money'] + $card_money) < $orderRes['origin']) {
                    return array('error' => '余额不足！');
                }
                // 支付
                if (isset($orderRes['rstate']) && trim($orderRes['rstate']) != 'normal') {
                    return array('error' => '订单状态错误！');
                }
                $res = $this->updateOrderUser($orderRes, 0, 'CNY', 'credit', '', '');
                if (!$res) {
                    return array('error' => '余额支付失败！');
                }
                break;
            default:
                return false;
                break;
        }

        // 更新支付类型
        if (isset($data['pay_url_params'])) {
            M('order')->where(array('id' => $orderId))->save(array('service' => $payAction));
        }

        return $data;
    }

    /**
     * 获取云购码
     * @param type $teamRes
     * @param type $orderRes
     * @return boolean|array
     */
    private function __getCloudShopingCode($teamRes, $orderRes) {

        if (!$teamRes || !$orderRes) {
            return false;
        }

        $phpredis = new \Common\Org\phpRedis('pconnect');
        $redis = $phpredis::$redis;
        $_key = md5("{$teamRes['id']}_{$teamRes['now_periods_number']}");
        $key_incr = getCloudShopingRedisKey("yungou_incr_{$_key}");
        $key = getCloudShopingRedisKey("yungou_{$_key}");
        $order_quantity = intval(ternary($orderRes['quantity'], 0));
        if (intval($redis->incrBy($key_incr, $orderRes['quantity'])) === $order_quantity) {
            $arr = array_pad(array(), intval($teamRes['max_number']), 0);
            $arr1 = array_keys($arr);
            shuffle($arr1);
            $redis->delete($key);
            foreach ($arr1 as $v) {
                $redis->lPush($key, $v);
            }
        }

        $team_code_res = array();
        if ($redis->lSize($key) <= 0) {
            return $team_code_res;
        }

        $base_code = $this->base_code;
        //$base_code = intval($teamRes['id']) + intval($teamRes['now_periods_number'])+1;
        for ($i = 1; $i <= $order_quantity; $i++) {
            $code = $redis->lPop($key);
            if ($code === false) {
                break;
            }
            $team_code_res[] = $base_code + intval($code);
        }
        return $team_code_res;
    }

    /**
     * 如果数据库更新失败  则回滚云购码
     */
    private function cloud_code_redis_rollback($teamRes, $cloud_code) {
        $cloud_code_count = count($cloud_code);

        if (!$cloud_code_count || $cloud_code_count <= 0) {
            return false;
        }
        if (!$cloud_code) {
            return false;
        }

        $phpredis = new \Common\Org\phpRedis('pconnect');
        $redis = $phpredis::$redis;
        $_key = md5("{$teamRes['id']}_{$teamRes['now_periods_number']}");
        $key_incr = getCloudShopingRedisKey("yungou_incr_{$_key}");
        $key = getCloudShopingRedisKey("yungou_{$_key}");

        $redis->decrBy($key_incr, $cloud_code_count);
        $base_code = $this->base_code;
        foreach ($cloud_code as &$v) {
            $v = intval($v)-$base_code;
            $redis->lPush($key, $v);
        }
        unset($v);
        return true;
    }

    // 云购一期商品买完后的相关处理
    public function cloudShopingCompleteUpdate($orderRes) {
        if (!isset($orderRes['team_id']) || !trim($orderRes['team_id'])) {
            return false;
        }
        $team = M('team');
        $field = "team_type,now_number,max_number,now_periods_number,max_periods_number,delivery,team_price,ucaii_price,partner_id,credit,expire_time,product,begin_time";
        $teamRes = $team->where(array('id' => $orderRes['team_id']))->field($field)->find();
        if (!isset($teamRes['team_type']) || trim($teamRes['team_type']) != 'cloud_shopping') {
            return false;
        }

        $team_now_number = intval($teamRes['now_number']);
        $team_max_number = intval($teamRes['max_number']);
        if (!$team_now_number || !$team_max_number || $team_now_number < $team_max_number) {
            return false;
        }

        // 计算中奖码
        $where = array(
            'team_type' => 'cloud_shopping',
            'state' => 'pay',
            'rstate' => 'normal',
            'team_id' => ternary($orderRes['team_id'], 0),
            'now_periods_number' => ternary($teamRes['now_periods_number'], 1),
            'pay_time' => array('gt', 0),
        );
        $cloudShopingCodeRes = M('order')->where($where)->field('microtime')->limit(50)->order('pay_time desc')->select();
        $end_50_all_microtime = 0;
        foreach ($cloudShopingCodeRes as $v) {
            if (!isset($v['microtime']) || !trim($v['microtime'])) {
                continue;
            }
            $end_50_all_microtime = intval($end_50_all_microtime + microtime_type($v['microtime']));
        }
        $base_code = $this->base_code;
        $winning_cloud_code = intval(($end_50_all_microtime % $team_max_number) + $base_code);

        // 获取中奖用户id和订单id
        $winning_order_id = $winning_user_id = 0;
        $where = array(
            'team_id' => ternary($orderRes['team_id'], 0),
            'periods_number' => ternary($teamRes['now_periods_number'], 1),
            'cloud_code' => $winning_cloud_code
        );
        $winning_res = M('cloud_shoping_code')->where($where)->field('order_id,user_id')->find();
        if (!isset($winning_res['order_id']) || !trim($winning_res['order_id'])) {
            return false;
        }
        if (!isset($winning_res['user_id']) || !trim($winning_res['user_id'])) {
            return false;
        }
        $winning_order_id = intval($winning_res['order_id']);
        $winning_user_id = intval($winning_res['user_id']);

        $cloud_shoping_result = M('cloud_shoping_result');
        $where = array(
            'team_id' => ternary($orderRes['team_id'], 0),
            'periods_number' => ternary($teamRes['now_periods_number'], 1),
        );
        $cloud_shoping_result_count = $cloud_shoping_result->where($where)->count();


        // 事务开启
        $model = M();
        $model->startTrans();
        $res = false;
        if ($cloud_shoping_result_count && $cloud_shoping_result_count > 0) {
            $data = array(
                'winning_cloud_code' => $winning_cloud_code,
                'winning_order_id' => $winning_order_id,
                'winning_user_id' => $winning_user_id,
                'end_50_all_microtime' => $end_50_all_microtime,
                'status' => 1,
                'begin_time' => time(),
            );
            $res = $cloud_shoping_result->where($where)->save($data);
        } else {
            $data = array(
                'team_id' => ternary($orderRes['team_id'], 0),
                'max_number' => ternary($teamRes['max_number'], 0),
                'periods_number' => ternary($teamRes['now_periods_number'], 1),
                'winning_cloud_code' => $winning_cloud_code,
                'winning_order_id' => $winning_order_id,
                'winning_user_id' => $winning_user_id,
                'end_50_all_microtime' => $end_50_all_microtime,
                'team_delivery' => ternary($teamRes['delivery'], ''),
                'all_team_price' => ternary($teamRes['team_price'], ''),
                'all_ucaii_price' => ternary($teamRes['ucaii_price'], ''),
                'status' => 1,
                'begin_time' => time(), //strtotime('+15 minutes'),
                'create_time' => time()
            );
            $res = $cloud_shoping_result->add($data);
        }
        if (!$res) {
            $this->errorInfo['info'] = $cloud_shoping_result->getDbError();
            $this->errorInfo['sql'] = $cloud_shoping_result->_sql();
            $model->rollback();
            return false;
        }
        if (intval($teamRes['now_periods_number'] + 1) <= intval($teamRes['max_periods_number'])) {
            $data = array(
                'team_id' => ternary($orderRes['team_id'], 0),
                'max_number' => ternary($teamRes['max_number'], 0),
                'periods_number' => intval(ternary($teamRes['now_periods_number'], 1) + 1),
                'winning_cloud_code' => 0,
                'winning_order_id' => 0,
                'winning_user_id' => 0,
                'end_50_all_microtime' => 0,
                'team_delivery' => ternary($teamRes['delivery'], ''),
                'all_team_price' => ternary($teamRes['team_price'], ''),
                'all_ucaii_price' => ternary($teamRes['ucaii_price'], ''),
                'status' => 0,
                'begin_time' => 0,
                'create_time' => time()
            );
            $res = $cloud_shoping_result->add($data);
            if (!$res) {
                $this->errorInfo['info'] = $cloud_shoping_result->getDbError();
                $this->errorInfo['sql'] = $cloud_shoping_result->_sql();
                $model->rollback();
                return false;
            }
        }

        // 添加团购卷
        if (isset($teamRes['delivery']) && trim($teamRes['delivery']) == 'coupon') {
            $winning_order_res = M('order')->where(array('id' => $winning_order_id))->find();
            $winning_order_res['quantity'] = 1;
            $teamRes['title'] = $teamRes['product'];
            $res = $this->addCoupon($winning_order_res, $teamRes);
            if (!$res) {
                $model->rollback();
                return false;
            }
        }

        // 更新团单信息
        $data = array(
            'now_number' => 0,
            'now_periods_number' => intval($teamRes['now_periods_number']) + 1,
        );
        $res = $team->where(array('id' => $orderRes['team_id']))->save($data);
        if (!$res) {
            $this->errorInfo['info'] = $team->getDbError();
            $this->errorInfo['sql'] = $team->_sql();
            $model->rollback();
            return false;
        }
        $model->commit();
        return true;
    }

    /**
     *  云购订单 支付成功后 修改订单信息
     */
    private function __updateCloudShopingOrderUser($orderRes, $money, $currency = '', $service = '', $bank = '', $trade_no = '', $teamRes = array()) {
        $team = M('team');
        if (!$teamRes) {
            $teamRes = $team->where(array('id' => $orderRes['team_id']))->find();
        }

        // 用户余额扣减
        $user = M('user');
        $userRes = $user->where(array('id' => $orderRes['user_id']))->find();
        if (!$userRes) {
            return false;
        }
        // 支付流水
        $pay = M('pay');
        $pay_count_res = $pay->where(array('id' => $orderRes['pay_id'], 'order_id' => $orderRes['id']))->count();

        // 团购结果
        $where = array(
            'team_id' => ternary($orderRes['team_id'], 0),
            'periods_number' => ternary($teamRes['now_periods_number'], 1),
        );

        $order = M('order');

        // 事务开启
        $model = M();
        $model->startTrans();
        $data = array(
            'service' => $service,
            'state' => 'pay',
            'trade_no' => $trade_no,
        );
        $res = $order->where(array('id' => $orderRes['id']))->save($data);
        if (!$res) {
            $this->errorInfo['info'] = $order->getDbError();
            $this->errorInfo['sql'] = $order->_sql();
            $model->rollback();
            return false;
        }

        if ($money > 0 && trim($trade_no)) {

            if ($pay_count_res && $pay_count_res > 0) {
                return false;
            }
            $pdata = array(
                'id' => $orderRes['pay_id'],
                'vid' => $trade_no,
                'order_id' => $orderRes['id'],
                'bank' => $bank,
                'money' => $money,
                'currency' => $currency,
                'service' => $service,
                'create_time' => time()
            );
            $res = $pay->add($pdata);
            if (!$res) {
                $this->errorInfo['info'] = $pay->getDbError();
                $this->errorInfo['sql'] = $pay->_sql();
                $model->rollback();
                return false;
            }
        }

        $yunCodes = $this->__getCloudShopingCode($teamRes, $orderRes);
        file_put_contents('/tmp/cloud_shopping_pay_callback.log', var_export(array(
                '订单id' => $orderRes['id'],
                '团单id' => $orderRes['team_id'],
                '云购码'=>$yunCodes), true), FILE_APPEND);
        $yunCodeCount = 0;
        if ($yunCodes) {
            $yunCodeCount = count($yunCodes);
            $cloud_shoping_code_data = array();
            $now_time = time();
            $ucaii_price = 0;
            if ($teamRes['team_price'] > 0) {
                $ucaii_price = sprintf("%.2f", $teamRes['ucaii_price'] / $teamRes['team_price']);
            }
            $microtime_float = microtime_float();
            foreach ($yunCodes as $y_code) {
                $cloud_shoping_code_data[] = array(
                    'cloud_code' => $y_code,
                    'order_id' => ternary($orderRes['id'], 0),
                    'user_id' => ternary($orderRes['user_id'], 0),
                    'team_id' => ternary($teamRes['id'], 0),
                    'periods_number' => ternary($teamRes['now_periods_number'], 1),
                    'microtime' => $microtime_float,
                    'from' => $service,
                    'team_price' => 1,
                    'ucaii_price' => $ucaii_price,
                    'create_time' => $now_time
                );
            }
            $cloud_shoping_code = M('cloud_shoping_code');
            $res = $cloud_shoping_code->addAll($cloud_shoping_code_data);
            if (!$res) {
                $this->errorInfo['info'] = $cloud_shoping_code->getDbError();
                $this->errorInfo['sql'] = $cloud_shoping_code->_sql();
                $model->rollback();
                $this->cloud_code_redis_rollback($teamRes,$yunCodes);
                return false;
            }
        }

        // 如果支付完后  发现买超了  重新计算相关金额
        $origin = ternary($orderRes['origin'], 0);
        $del_credit = sprintf("%.2f", $orderRes['origin'] - $money);
        $add_credit = 0;
        $quantity = intval(ternary($orderRes['quantity'], 0));
        if ($quantity > $yunCodeCount) {
            $quantity = $yunCodeCount;
            $origin = sprintf("%.2f", $orderRes['price'] * $quantity);
            if ($money > 0) {
                if ($origin >= $money) {
                    $del_credit = sprintf("%.2f", $origin - $money);
                } else {
                    $del_credit = 0;
                    $add_credit = sprintf("%.2f", $money - $origin);
                }
            } else {
                $del_credit = $origin;
            }
        }

        $data = array(
            'origin' => $origin,
            'quantity' => $quantity,
            'money' => $money,
            'credit' => $del_credit,
            'pay_time' => time(),
            'microtime' => microtime_float(),
        );
        $res = $order->where(array('id' => $orderRes['id']))->save($data);
        if ($res === false) {
            $this->errorInfo['info'] = $order->getDbError();
            $this->errorInfo['sql'] = $order->_sql();
            $model->rollback();
            $this->cloud_code_redis_rollback($teamRes,$yunCodes);
            return false;
        }

        // 数据库团单已卖出份数更新
        $res = $team->where(array('id' => $orderRes['team_id']))->setInc('now_number', $quantity);
        if (!$res) {
            $this->errorInfo['info'] = $team->getDbError();
            $this->errorInfo['sql'] = $team->_sql();
            $model->rollback();
            $this->cloud_code_redis_rollback($teamRes,$yunCodes);
            return false;
        }


        // 更新用户余额
        if ($add_credit > 0 || $del_credit > 0) {
            $userMoney = sprintf("%.2f", $userRes['money']);
            if ($add_credit > 0) {
                $userMoney = sprintf("%.2f", $userMoney + $add_credit);
            }
            if ($del_credit > 0 && $userMoney >= $del_credit) {
                $userMoney = sprintf("%.2f", $userMoney - $del_credit);
            }
            if ($userMoney < 0) {
                $userMoney = 0;
            }
            $res = $user->where(array('id' => $orderRes['user_id']))->save(array('money' => $userMoney));
            if (!$res) {
                $this->errorInfo['info'] = $user->getDbError();
                $this->errorInfo['sql'] = $user->_sql();
                $model->rollback();
                $this->cloud_code_redis_rollback($teamRes,$yunCodes);
                return false;
            }
        }

        // 添加流水信息
        if ($add_credit > 0) {
            $res = $this->addFlowData($orderRes, $add_credit, 'income', 'paycharge');
        }
        if ($money > 0) {
            $res = $this->addFlowData($orderRes, $money, 'income', 'paycharge');
        }
        $res = $this->addFlowData($orderRes, $origin, 'expense', 'buy');
        if (!$res) {
            $model->rollback();
            $this->cloud_code_redis_rollback($teamRes,$yunCodes);
            return false;
        }

        // 添加评论
        $res = $this->addComment($orderRes, $teamRes);
        if (!$res) {
            $model->rollback();
            $this->cloud_code_redis_rollback($teamRes,$yunCodes);
            return false;
        }

        // 添加积分
        $_orderRes = $orderRes;
        $_orderRes['origin'] = $origin;
        $res = $this->addCredit($_orderRes, $userRes);
        if (!$res) {
            $model->rollback();
            $this->cloud_code_redis_rollback($teamRes,$yunCodes);
            return false;
        }

        $model->commit();

        // 购买成功后发送短信
        $this->paySuccessSendSms($orderRes, $teamRes, true, $service);

        // 云购每期商品买完后的相关处理
        $this->cloudShopingCompleteUpdate($orderRes);

        return true;
    }

    /**
     * 支付回调成功后修改相关信息
     * @param type $orderRes
     * @param type $money
     * @param type $currency
     * @param type $service
     * @param type $bank
     * @param type $trade_no
     */
    public function updateOrderUser($orderRes, $money, $currency = '', $service = '', $bank = '', $trade_no = '') {

        // 团单 信息
        $team = M('team');
        $teamRes = $team->where(array('id' => $orderRes['team_id']))->find();

        // 云购订单 支付成功后 修改订单信息
        if (isset($teamRes['team_type']) && trim($teamRes['team_type']) == 'cloud_shopping') {
            return $this->__updateCloudShopingOrderUser($orderRes, $money, $currency, $service, $bank, $trade_no, $teamRes);
        }

        // 用户余额扣减
        $user = M('user');
        $userRes = $user->where(array('id' => $orderRes['user_id']))->find();
        if (!$userRes) {
            return false;
        }

        $card_money = 0;
        if (isset($orderRes['card_id']) && trim($orderRes['card_id'])) {
            $cardRes = M('card')->where(array('id' => $orderRes['card_id']))->getField('credit');
            if ($cardRes) {
                $card_money = $cardRes;
            }
        }
        // 更新订单
        $order = M('order');
        $_credit = sprintf("%.2f", $orderRes['origin'] - $money - $card_money);
        if (!$_credit || $_credit < 0) {
            $_credit = 0;
        }

        // 流水数量
        $pay = M('pay');
        $pay_count_res = $pay->where(array('id' => $orderRes['pay_id'], 'order_id' => $orderRes['id']))->count();

        // 事务开启
        $model = M();
        $model->startTrans();

        $data = array(
            'service' => $service,
            'state' => 'pay',
            'trade_no' => $trade_no,
            'money' => $money,
            'credit' => $_credit,
        );
        $res = $order->where(array('id' => $orderRes['id']))->save($data);
        if (!$res) {
            $this->errorInfo['info'] = $order->getDbError();
            $this->errorInfo['sql'] = $order->_sql();
            $model->rollback();
            return false;
        }
        $res = $order->where(array('id' => $orderRes['id']))->save(array('pay_time' => time()));

        // 添加支付流水
        if ($money > 0 && trim($trade_no)) {
            if ($pay_count_res && $pay_count_res > 0) {
                return false;
            }
            $pdata = array(
                'id' => $orderRes['pay_id'],
                'vid' => $trade_no,
                'order_id' => $orderRes['id'],
                'bank' => $bank,
                'money' => $money,
                'currency' => $currency,
                'service' => $service,
                'create_time' => time()
            );
            $res = $pay->add($pdata);
            if (!$res) {
                $this->errorInfo['info'] = $pay->getDbError();
                $this->errorInfo['sql'] = $pay->_sql();
                $model->rollback();
                return false;
            }
        }

        // 更新属性库存
        if (isset($teamRes['team_type']) && trim($teamRes['team_type']) == 'goods') {
            $optional_model = @json_decode(ternary($orderRes['optional_model'], ''), true);
            if ($optional_model) {
                $up_where = array('team_id' => $orderRes['team_id']);
                foreach ($optional_model as $v) {
                    $up_where['id'] = $v['id'];
                    $res = M('team_attribute')->where($up_where)->setInc('now_num', $v['num']);
                }
            }
        }

        // 数据库更新
        $res = $team->where(array('id' => $orderRes['team_id']))->setInc('now_number', $orderRes['quantity']);
        if (!$res) {
            $this->errorInfo['info'] = $team->getDbError();
            $this->errorInfo['sql'] = $team->_sql();
            $model->rollback();
            return false;
        }

        if (isset($userRes['money']) && $userRes['money'] > 0 && isset($data['credit']) && $data['credit'] > 0) {
            $userMoney = sprintf("%.2f", $userRes['money'] - $data['credit']);
            if ($userMoney < 0) {
                $userMoney = 0;
            }
            $res = $user->where(array('id' => $orderRes['user_id']))->save(array('money' => $userMoney));
            if (!$res) {
                $this->errorInfo['info'] = $user->getDbError();
                $this->errorInfo['sql'] = $user->_sql();
                $model->rollback();
                return false;
            }
        }

        // 添加流水信息
        if ($card_money > 0) {
            $res = $this->addFlowData($orderRes, $card_money, 'income', 'card');
        }
        if ($money > 0) {
            $res = $this->addFlowData($orderRes, $money, 'income', 'paycharge');
        }
        $res = $this->addFlowData($orderRes, $orderRes['origin'], 'expense', 'buy');
        if (!$res) {
            $model->rollback();
            return false;
        }

        // 释放资源，获取凭证
        $ota = D('Ota');
        $parkcode = $ota->tmCheck($orderRes['team_id']);
        if ($parkcode) {
            $resdata = $ota->orderEnd($orderRes['id'] ,$parkcode);
            if (!$resdata) {
                $model->rollback();
                return false;
            }
            $orderRes['ota'] = $resdata;
        }

        // 添加团购卷
        if (isset($teamRes['delivery']) && trim($teamRes['delivery']) == 'coupon') {
            $ress = $this->addCoupon($orderRes, $teamRes);
            if (!$ress) {
                $model->rollback();
                return false;
            }
        }

        // 添加评论
        $res = $this->addComment($orderRes, $teamRes);
        if (!$res) {
            $model->rollback();
            return false;
        }

        // 添加积分
        $res = $this->addCredit($orderRes, $userRes);
        if (!$res) {
            $model->rollback();
            return false;
        }

        $model->commit();

        // 购买成功后发送短信
        $this->paySuccessSendSms($orderRes, $teamRes, true, $service,$ress);
        return true;
    }

    /**
     * 保存流水信息收入
     * @param type $order
     * @param type $money
     * @param type $state
     * @param type $action
     * @return type
     */
    public function addFlowData($order, $money, $state, $action) {
        $data = array(
            'user_id' => $order['user_id'],
            'money' => $money,
            'direction' => $state,
            'action' => $action,
            'detail_id' => $order['team_id'],
            'create_time' => time(),
            'team_id' => $order['team_id'],
            'partner_id' => $order['partner_id'],
            'marks' => '',
        );
        if (($action == 'refund' || $action == 'paycharge') && isset($order['pay_id'])) {
            $data['detail_id'] = $order['pay_id'];
        }
        return M('flow')->add($data);
    }

    /**
     * 添加团购卷
     */
    public function addCoupon($orderRes, $teamRes) {

        $coupon = M('coupon');
        $coupon_delete = M('coupon_delete');
        $count = $coupon->where(array('order_id' => $orderRes['id']))->count();
        $cids = array();
        $log_select_count = 0;
        if (isset($orderRes['quantity'])) {
            while ($count < $orderRes['quantity']) {
                $log_select_count++;
                $cid = mt_rand(100000, 999999) . mt_rand(100000, 999999);
                $row = $coupon->where(array('id' => strval($cid)))->count();
                if ($row > 0) {
                    continue;
                }
                $row = $coupon_delete->where(array('id' => strval($cid)))->count();
                if ($row > 0) {
                    continue;
                }
                $cids[] = $cid;
                $count++;
            }
        }
        file_put_contents('/tmp/add_coupon.log', "订单{$orderRes['id']},购买{$orderRes['quantity']}份，查询次数：{$log_select_count} \r\n", FILE_APPEND);

        $partner_id = ternary($teamRes['partner_id'], '');
        if (!trim($partner_id) && isset($orderRes['partner_id'])) {
            $partner_id = $orderRes['partner_id'];
        }

        // 新用户立减价格
        $new_user_price = ternary($teamRes['team_price'], 0);
        $teamRes['team_type'] = isset($teamRes['team_type']) ? strtolower(trim($teamRes['team_type'])) : '';
        if ($teamRes['team_type'] == 'newuser') {
            $origin = ternary($orderRes['origin'], 0);
            $team_all_price = sprintf("%.2f", $new_user_price * ternary($orderRes['quantity'], 0));
            if ($team_all_price > $origin) {
                $new_user_price = sprintf("%.2f", $new_user_price - ($team_all_price - $origin));
            }
        }
        // 新客立减价格 因为是商家掏钱所以团购价也需要减
        //$new_user_price = ternary($teamRes['team_price'], 0);
        $new_guser_price = ternary($teamRes['ucaii_price'], 0);
        //$teamRes['team_type'] = isset($teamRes['team_type']) ? strtolower(trim($teamRes['team_type'])) : '';
        if ($teamRes['team_type'] == 'newguser') {
            $origin = ternary($orderRes['origin'], 0);
            $team_all_price = sprintf("%.2f", $new_user_price * ternary($orderRes['quantity'], 0));
            if ($team_all_price > $origin) {
                $new_user_price = sprintf("%.2f", $new_user_price - ($team_all_price - $origin));
                $new_guser_price = sprintf("%.2f", $new_guser_price - ($team_all_price - $origin));
            }
        }

        // 批量添加团卷
        $data = array();
        $default_expire_time = strtotime('+7 day');

        foreach ($cids as $key => $val) {
            $data[$key] = array(
                'id' => $val,
                'user_id' => ternary($orderRes['user_id'], 0),
                'buy_id' => ternary($orderRes['buy_id'], 0),
                'partner_id' => ternary($teamRes['partner_id'], 0),
                'order_id' => ternary($orderRes['id'], 0),
                'credit' => ternary($teamRes['credit'], 0),
                'team_type' => ternary($teamRes['team_type'], 0),
                'team_id' => ternary($orderRes['team_id'], 0),
                'team_price' => $key == 0 ? $new_user_price : ternary($teamRes['team_price'], 0),
                'ucaii_price' => $key == 0 ? $new_guser_price : ternary($teamRes['ucaii_price'],0),
                'secret' => '',
                'from' => '新版系统',
                'expire_time' => ternary($teamRes['expire_time'], $default_expire_time),
                'create_time' => time()
            );
        };
        file_put_contents('/tmp/add_coupon.log', var_export($data, true)."\r\n", FILE_APPEND);
        if (!$data) {
            return false;
        }
        $res = $coupon->addAll($data);
        if ($res) {
            $w_data = array(
                'user_id' => ternary($orderRes['user_id'], ''),
                'order_id' => ternary($orderRes['id'], ''),
                'price' => ternary($teamRes['team_price'], ''),
                'end_time' => ternary($teamRes['expire_time'], $default_expire_time),
                'title' => ternary($teamRes['title'], ''),
                'coupon' => implode(',', $cids),
            );
            $this->_WeiXinSendCoupon($w_data, 'buy');

            //调用第三方创建电子凭证
            threeValidCoupon($teamRes['partner_id'], array(
                'title' => ternary($teamRes['product'], ''),
                'desc' => mb_substr(preg_replace('/\s*/', '', strip_tags(ternary($teamRes['notice'], ''))), 0, 150, 'utf8'),
                'mobile' => ternary($orderRes['mobile'], ''),
                'begin_time' => ternary($teamRes['begin_time'], 0),
                'price' => ternary($orderRes['price'], 0),
                'amount' => ternary($orderRes['origin'], 0),
                'coupon' => $data,
                    ), 'create');
            $res=$cids;//2016.5.20加
        }
        return $res;
    }

    /**
     * 支付成功后添加评论
     */
    public function addComment($orderRes, $teamRes) {
        // 添加评论
        $data = array(
            'user_id' => ternary($orderRes['user_id'], ''),
            'team_id' => ternary($orderRes['team_id'], ''),
            'partner_id' => ternary($orderRes['partner_id'], ''),
            'cate_id' => ternary($teamRes['group_id'], ''),
            'order_id' => ternary($orderRes['id'], ''),
            'create_time' => time(),
            'is_comment' => 'N',
            'consume' => 'N'
        );
        return M('comment')->add($data);
    }

    /**
     *
     * @param type $orderRes
     * @return boolean支付成功后 添加积分
     */
    public function addCredit($orderRes, $userRes) {

        $score = 1;
        if (!isset($orderRes['origin']) || $orderRes['origin'] <= 0) {
            return true;
        }
        if (!isset($orderRes['user_id']) || !trim($orderRes['user_id'])) {
            return true;
        }
        if (!isset($orderRes['team_id']) || !trim($orderRes['team_id'])) {
            return true;
        }
        if ($orderRes['origin'] > 0) {
            $score = (int) round($orderRes['origin']);
        }

        $rs = M('User')->where(array('id' => $orderRes['user_id']))->setInc('score', $score); //评论积分
        if ($rs === false) {
            return false;
        }
        $creditData = array(
            'create_time' => time(),
            'user_id' => $orderRes['user_id'],
            'score' => $score,
            'action' => 'pay',
            'detail_id' => $orderRes['team_id'],
            'sumscore' => $userRes['score'] + $score
        );
        $flowRes = M('Credit')->add($creditData);   //积分流水
        if ($flowRes === false) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param type $orderRes
     * @param type $userRes
     */
    public function delCredit($orderRes, $userRes, $action = 'refund') {
        $score = 1;
        if (!isset($orderRes['origin']) || $orderRes['origin'] <= 0) {
            return true;
        }
        if (!isset($orderRes['user_id']) || !trim($orderRes['user_id'])) {
            return true;
        }
        if (!isset($orderRes['team_id']) || !trim($orderRes['team_id'])) {
            return true;
        }
        if ($orderRes['origin'] > 0) {
            $score = (int) round($orderRes['origin']);
        }
        $sumscore = $userRes['score'] - $score;
        if ($sumscore < 0) {
            $sumscore = 0;
            $score = $userRes['score'];
        }

        $rs = M('User')->where(array('id' => $orderRes['user_id']))->setDec('score', $score); //评论积分
        if ($rs === false) {
            return false;
        }
        $creditData = array(
            'create_time' => time(),
            'user_id' => $orderRes['user_id'],
            'score' => "-{$score}",
            'action' => $action,
            'detail_id' => $orderRes['team_id'],
            'sumscore' => $sumscore
        );
        $flowRes = M('Credit')->add($creditData);   //积分流水
        if ($flowRes === false) {
            return false;
        }

        return true;
    }

    /**
     * 支付成功后发送短信
     * @param type $orderRes
     * @param type $teamRes
     * @return type
     */
    public function paySuccessSendSms($orderRes, $teamRes, $isPushMessage = false, $service = '',$coupon='') {
        date_default_timezone_set('Asia/Hong_Kong');
        if (!isset($orderRes['mobile'])) {
            $orderRes['mobile'] = M('user')->where(array('id' => $orderRes['user_id']))->getField('mobile');
        }
        if (!isset($orderRes['mobile'])) {
            return false;
        }

        $sendSms = new \Common\Org\sendSms();

        // 获取发送内容
        $sellerPhone = M('partner')->where(array('id' => $orderRes['partner_id']))->getField('phone');
        $is_goods = false;
        $content = "您已成功购买：[{$teamRes['product']}]，请耐心等待发货，如有疑问请致电：4000-998-433";
        if (isset($teamRes['delivery']) && trim($teamRes['delivery']) == 'coupon') {
            file_put_contents('/tmp/sms.log',var_export($coupon, true).'||',FILE_APPEND);
            if($coupon){
                $coupons=$coupon;
                if ($coupons) {
                    $cids = implode(', ', $coupons);
                    $content = " {$teamRes['product'] } {$orderRes['quantity']}份，券号 $cids ，有效期至：" . date("Y-m-d", $teamRes["expire_time"]) . "，商家电话$sellerPhone 。";
                }
            }else{
                $coupons = M('coupon')->where(array('order_id' => $orderRes['id']))->select();
                if ($coupons) {
                    $_coupon = array();
                    foreach ($coupons as $i => $coupon) {
                        $_coupon[] = $coupon['id'];
                    }
                    $cids = implode(', ', $_coupon);
                    $content = " {$teamRes['product'] } {$orderRes['quantity']}份，券号 $cids ，有效期至：" . date("Y-m-d", $teamRes["expire_time"]) . "，商家电话$sellerPhone 。";
                }
            }


            $is_goods = false;
        }

        // 一元众筹  短信内容
        if (isset($teamRes['team_type']) && trim($teamRes['team_type']) == 'cloud_shopping') {
            $content = "您已成功参与一元众筹，{$teamRes['title']}第{$teamRes['now_periods_number']}期{$orderRes['quantity']}次，请留意开奖信息。";
        }

        // 是邮购商品
        if (isset($teamRes['team_type']) && trim($teamRes['team_type']) == 'goods') {
            $is_goods = true;
        }
        // OTA旅游门票电子券
        $parkcode = D('Ota')->tmCheck($teamRes['id']);
        if ($parkcode && isset($orderRes['ota'])) {
            $otadata = $orderRes['ota'];
            $content = "您已成功购买：[{$teamRes['product']} {$otadata->ProductCount}张]，入园电子码凭证：{$otadata->ECode}，入园二维码凭证：{$otadata->UrlECode}，商家电话：{$sellerPhone}。";
            $is_goods = true;
            $isPushMessage = false;
        }

        // 推送消息
        if ($isPushMessage) {
            $push_message = "您已成功购买[{$teamRes['product'] }]{$orderRes['quantity']}份，请到我的团购券中查看";
            $plat = 'android';
            if (isset($orderRes['laiyuan']) && $orderRes['laiyuan'] == 'ios') {
                $plat = 'ios';
            }

            $data = array(
                'title' => '购买成功提示',
                'content' => $push_message,
                'account' => array($orderRes['user_id']),
                'custom' => array('type' => "pay_success", 'data' => array('id' => $orderRes['id'])),
                'plat' => $plat,
            );
            $pushAppMessage = new \Common\Org\PushAppMessage();
            $pushAppMessage->pushMessageToAccess($data);
        }
        $res = true;
        $service = trim($service);
        if ($is_goods || !isset($orderRes['laiyuan']) || ($orderRes['laiyuan'] != 'ios' && $orderRes['laiyuan'] != 'android')) {
            $res = $sendSms->sendMsg($orderRes['mobile'], $content);
        }
        \Think\Log::write('短信提示结果: '.var_export($res,true), 'INFO');
        return $res;
    }

    /**
     * 根据某个经纬度 和 范围值  获取经纬度范围的四个点
     * @param type $lng 经度
     * @param type $lat 纬度
     * @param type $distance 距离 单位km
     */
    public function returnSquarePoint($lng, $lat, $distance) {
        $dlng = 2 * asin(sin($distance / (2 * self::EARTH_RADIUS)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);
        $dlat = $distance / self::EARTH_RADIUS;
        $dlat = rad2deg($dlat);
        return array(
            'left-top' => array('lat' => $lat + $dlat, 'lng' => $lng - $dlng),
            'right-top' => array('lat' => $lat + $dlat, 'lng' => $lng + $dlng),
            'left-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng - $dlng),
            'right-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng + $dlng)
        );
    }

    /**
     * 根据经纬度 获取 mysql计算经纬度的字段 距离经度保留小数点后4位
     * @param type $lat 纬度
     * @param type $lng 经度
     */
    public function getMysqlDistanceField($lat, $lng, $prefix = null) {
        $pi = pi() / 180;
        $lat = $lat * $pi;
        $lng = $lng * $pi;
        $latFiled = "lat*$pi";
        $lngFiled = "`long`*$pi";
        if ($prefix) {
            $latFiled = "$prefix.lat*$pi";
            $lngFiled = "$prefix.`long`*$pi";
        }
        $calcLongitude = "($lngFiled-$lng)/2";
        $calcLatitude = "($latFiled-$lat)/2";

        $stepOne = "POW(SIN($calcLatitude),2)";
        $steptow = cos($lat) . "*COS($latFiled)";
        $stepThree = "POW(SIN($calcLongitude),2)";
        $sqlField = "ROUND(" . self::EARTH_RADIUS . "*2*ASIN(LEAST(1,sqrt($stepOne+$steptow*$stepThree)))*10000)/10000";

        return trim($sqlField);
    }

    /**
     * 获取排序字段
     * @param type $order 排序的字段名
     * @param type $orderType 排序方式 升序+，降序-   默认升序
     */
    private function __getMysqlSortField($order, $orderType = '+', $data = array()) {

        $field = "MIN(team.$order)";
        if (trim($orderType) == '-') {
            $field = "MAX(team.$order)";
        }
        $nowTime = time();
        $where = "team.`partner_id`=partner.`id` AND team.begin_time<$nowTime AND team.end_time>$nowTime AND (team.team_type = 'normal' OR team.team_type='newuser')";
        if (isset($data['plat']) && trim($data['plat']) == 'wap') {
            $where = "team.`partner_id`=partner.`id` AND team.begin_time<$nowTime AND team.end_time>$nowTime AND (team.team_type = 'normal' OR team.team_type='newuser' OR team.team_type='goods')";
        }
        // 类型过滤
        if (isset($data['type']) && trim($data['type'])) {
            if (strpos($data['type'], '@') !== false) {
                @list($groupId, $subid) = explode('@', $data['type']);
                trim($groupId) && $where = "$where AND team.group_id=$groupId";
                trim($subid) && $where = "$where AND team.sub_id=$subid";
            } else {
                $where = "$where AND team.group_id={$data['type']}";
            }
        }
        // 关键字搜索
        if (isset($data['query']) && trim($data['query'])) {
            $where = "$where AND (partner.title like '%{$data['query']}%' OR team.product like '%{$data['query']}%' OR team.title like '%{$data['query']}%' OR team.sel1 like '%{$data['query']}%' OR team.sel2 like '%{$data['query']}%' or team.sel3 like '%{$data['query']}%')";
        }
        return array($field, "INNER JOIN team ON $where");
    }

    /**
     * 获取团单的评论条数和评价分
     * @param $team
     * @return mixed
     */
    public function getTeamReviews($team) {
        $teamId = '';
        foreach ($team as $val) {
            $teamId .= $val['team_id'] . ',';
        }

        if (empty($teamId)) {
            return $team;
        }
        $map['team_id'] = array('in', substr($teamId, 0, -1));
        $comment = M('Comment')->where($map)->group('team_id')->getField('team_id,count(id) num,avg(comment_num) score', true);
        foreach ($team as $key => $val) {
            $val['image'] = getImagePath($val['image']);
            $val['count'] = ternary($comment[$val['team_id']]['num'], 0);
            $val['num'] = ternary($comment[$val['team_id']]['score'], 0);
            $team[$key] = $val;
        }
        return $team;
    }

    /**
     * @param $where 条件
     * @param $order 排序
     * @param $limit 分页
     */
    public function getPayCount($where, $order, $limit) {
        $data = $this->field('team.id,team.image,team.product,team.team_price,team.begin_time,team.end_time,team.ucaii_price')
                ->join('left join coupon on coupon.team_id=team.id')
                ->group('coupon.team_id')
                ->where($where)->order($order)->limit($limit)
                ->select();

        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        if($data){
            $team_ids = array();
            foreach ($data as &$val) {
                if(isset($val['id'])){
                    $team_ids[$val['id']] = $val['id'];
                }
                $val['image'] = getImagePath($val['image']);
            }
            unset($val);
            $num_info = $pay_num_info = array();
            if($team_ids){
                $coupon_where = array(
                    'team_id'=>array('in',array_keys($team_ids))
                );
                if(isset($where['coupon.partner_id']) && $where['coupon.partner_id']){
                    $coupon_where['partner_id'] = $where['coupon.partner_id'];
                }
                $num_info = D('Coupon')->where($coupon_where)->group('team_id')->getField('team_id,count(id) as count_id',true);
                $coupon_where['consume']='Y';
                $pay_num_info = D('Coupon')->where($coupon_where)->group('team_id')->getField('team_id,count(id) as count_id',true);
            }
            foreach ($data as &$val) {
                $val['num'] = ternary($num_info[$val['id']],0);
                $time = array(date('Y-m-d',$val['begin_time']),date('Y-m-d',$val['end_time']));
                $val['time'] = implode('/', $time);
                $val['pay_num'] = ternary($pay_num_info[$val['id']],0);
                $val['tteam_price'] = $val['team_price'];//新版增加
                $val['team_price'] = $val['ucaii_price'];//客户端字段取值有问题
            }
            unset($val);
        }

        return $data;
    }

    /***
     * @param $where
     * @param $order
     * @param $limit
     * @return mixed
     * 2016.7.9加解决不显示团单情况
     */
    public function getPayCountNew($where, $order, $limit) {
        $data = $this->field('team.id,team.image,team.product,team.team_price,team.begin_time,team.end_time,team.ucaii_price')
            //->join('left join coupon on coupon.team_id=team.id')
            ->group('id')
            ->where($where)->order($order)->limit($limit)
            ->select();

        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        if($data){
            $team_ids = array();
            foreach ($data as &$val) {
                if(isset($val['id'])){
                    $team_ids[$val['id']] = $val['id'];
                }
                $val['image'] = getImagePath($val['image']);
            }
            unset($val);
            $num_info = $pay_num_info = array();
            if($team_ids){
                $coupon_where = array(
                    'team_id'=>array('in',array_keys($team_ids))
                );
                if(isset($where['coupon.partner_id']) && $where['coupon.partner_id']){
                    $coupon_where['partner_id'] = $where['coupon.partner_id'];
                }
                $num_info = D('Coupon')->where($coupon_where)->group('team_id')->getField('team_id,count(id) as count_id',true);
                $coupon_where['consume']='Y';
                $pay_num_info = D('Coupon')->where($coupon_where)->group('team_id')->getField('team_id,count(id) as count_id',true);
            }
            foreach ($data as &$val) {
                $val['num'] = ternary($num_info[$val['id']],0);
                $time = array(date('Y-m-d',$val['begin_time']),date('Y-m-d',$val['end_time']));
                $val['time'] = implode('/', $time);
                $val['pay_num'] = ternary($pay_num_info[$val['id']],0);
                $val['tteam_price'] = $val['team_price'];//新版增加
                $val['team_price'] = $val['ucaii_price'];//客户端字段取值有问题
            }
            unset($val);
        }

        return $data;
    }
    /**
     * @param $where
     * @param $order
     * @param $limit
     * @param $having
     * @param $field
     */
    public function getAroundList($where, $order, $limit, $field) {
        $data = $this->alias('t')->field($field)->join('inner join partner p ON t.partner_id = p.id')->where($where)->order($order)->limit($limit)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            if ($data) {
                foreach ($data as &$val) {
                    $val['image'] = getImagePath($val['image']);
                    $val['range'] = round($val['range'] * 1000);
                    $promotion = unserialize($val['promotion']);
                    $val['not_time'] = 0;
                    $val['all_type'] = 0;
                    $val['today'] = 0;
                    foreach ($promotion as $pro) {
                        if (strtolower($pro) == 'm') {
                            $val['not_time'] = 1;
                        } else if (strtolower($pro) == 'd') {
                            $val['all_type'] = 1;
                        }
                    }
                    if (date('Y-m-d', $val['begin_time']) == date('Y-m-d')) {
                        $val['today'] = 1;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 获取附近符合条件的总记录数
     * @param $where
     * @param $having
     * @param $field
     */
    public function getAroundCount($where) {
        $count = $this->alias('t')->join('inner join partner p ON t.partner_id = p.id')->where($where)->count('t.id');
        if ($count === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            return $count;
        }
    }

    /**
     * pc同步支付回调
     * @param type $payAction
     */
    public function synchronousPayCallbackHandle($payAction) {

        if (isset($_GET['oid'])) {
            unset($_GET['oid']);
        }
        if (isset($_GET['type'])) {
            unset($_GET['type']);
        }
        if (isset($_GET['code'])) {
            unset($_GET['code']);
        }
        if (isset($_GET['payAction'])) {
            unset($_GET['payAction']);
        }

        // 非法参数判断
        if (!trim($payAction)) {
            return array('error' => 'fail');
        }
        $payAction = strtolower(trim($payAction));
        $pay = new \Common\Org\Pay();
        switch ($payAction) {
            case 'alipay':
            case 'alipaycode':
            case 'pcalipay':
                $res = $pay->pcPayVerifyReturn('pcalipay');
                if (!$res) {
                    return array('error' => 'fail');
                }
                $payAction = 'pcalipay';
                // 支付成功！
                if (isset($res['trade_status']) && $res['trade_status'] == "TRADE_SUCCESS") {
                    if (isset($res['refund_status'])) {
                        //    $this->thirdPartyRefundUpdateData($res);
                        return array('message' => 'success');
                    }
                    return $this->__paySuccess($res['out_trade_no'], $res['total_fee'], $payAction, $res['trade_no']);
                }

                if (isset($res['trade_status']) && $res['trade_status'] == "WAIT_BUYER_PAY") {
                    return array('message' => 'success');
                }
                return array('error' => 'fail');
                break;
            case 'tenpay':
            case 'pctenpay':
                $payAction = 'pctenpay';
                $res = $pay->pcPayVerifyReturn('pctenpay');
                if (!$res) {
                    return array('error' => 'fail');
                }
                return $this->__paySuccess($res['sp_billno'], $res['total_fee'], $payAction, $res['transaction_id']);
                break;
            default:
                break;
        }
    }

    /**
     * app 支付同步回调同步
     */
    public function appSynchronousPayCallbackHandle($order_id = '') {
        if (!trim($order_id)) {
            return false;
        }
        $where = array(
            'id' => $order_id,
            'rstate' => 'normal',
            'state' => 'unpay',
        );
        $orderRes = M('order')->where($where)->field('pay_id,service')->find();
        if (!$orderRes) {
            return false;
        }
        if (!isset($orderRes['pay_id']) || !trim($orderRes['pay_id'])) {
            return false;
        }
        if (isset($orderRes['service']) && (trim($orderRes['service']) == 'credit' || trim($orderRes['service']) == 'cash')) {
            return false;
        }
        if (!$this->isPayAction($orderRes['service'])) {
            return false;
        }
        $pay = new \Common\Org\Pay();
        $data = $pay->orderQuery($orderRes['service'], $orderRes['pay_id'], '', false);
        if (!$data || !isset($data['pay_result']) || trim($data['pay_result']) == 'N') {
            return false;
        }
        if (!isset($data['pay_id']) || !trim($data['pay_id'])) {
            return false;
        }
        if (!isset($data['trade_no']) || !trim($data['trade_no'])) {
            return false;
        }
        if (!isset($data['pay_money']) || !trim($data['pay_money'])) {
            return false;
        }
        return $this->__paySuccess($data['pay_id'], $data['pay_money'], $orderRes['service'], $data['trade_no']);
    }

    /**
     *
     * @param type $payAction
     * @return type 异步支付回调
     */
    public function payCallbackHandle($payAction) {

        // 非法参数判断
        if (!trim($payAction)) {
            return array('error' => 'fail');
        }

        $pay = new \Common\Org\Pay();
        $payNew = new \Common\Org\PayNew();
        $payAction = strtolower(trim($payAction));
        if (isset($_GET['payAction'])) {
            unset($_GET['payAction']);
        }
        // 参数特殊处理
        if (isset($_POST['youngt_ts_version'])) {
            $_POST['version'] = $_POST['youngt_ts_version'];
            unset($_POST['youngt_ts_version']);
        }

        switch ($payAction) {
            case 'alipay':
            case 'pcalipay':
            case 'wapalipay':
                $res = false;
                if ($payAction == 'pcalipay') {
                    $res = $pay->pcPayVerify('pcalipay');
                } else if ($payAction == 'wapalipay') {
                    $res = $pay->wapPayVerify('wapalipay');
                } else {
                    $res = $pay->getALiCallBackVerify();
                }
                if (!$res) {
                    return array('error' => 'fail');
                }
                // 支付成功！
                if (isset($res['trade_status']) && $res['trade_status'] == "TRADE_SUCCESS") {
                    if (isset($res['refund_status'])) {
                        //  $this->thirdPartyRefundUpdateData($res);
                        return array('message' => 'success');
                    }
                    return $this->__paySuccess($res['out_trade_no'], $res['total_fee'], $payAction, $res['trade_no']);
                }

                if (isset($res['trade_status']) && $res['trade_status'] == "WAIT_BUYER_PAY") {
                    return array('message' => 'success');
                }
                return array('error' => 'fail');
                break;
            case 'tenpay':
            case 'pctenpay':
            case 'waptenpay':
                $res = false;
                if ($payAction == 'pctenpay') {
                    $res = $pay->pcPayVerify('pctenpay');
                } else if ($payAction == 'waptenpay') {
                    $res = $pay->wapPayVerify('waptenpay');
                } else {
                    $params = array_merge($_GET, $_POST);
                    $res = $pay->getTenCallBackVerify($params);
                }

                if (!$res) {
                    return array('error' => 'fail');
                }
                return $this->__paySuccess($res['sp_billno'], $res['total_fee'], $payAction, $res['transaction_id']);
                break;
            case 'wxpay':
            case 'newwxpay':
            case 'pcwxpaycode':
            case 'wapwechatpay':
                $res = false;
                if ($payAction == 'pcwxpaycode' || $payAction == 'wapwechatpay') {
                    $res = $pay->getPCWXpayVerify();
                } else if($payAction == 'wxpay'){
                    $params = array_merge($_GET, $_POST);
                    $res = $pay->getWXCallBackVerify($params);
                }else {
                    $res = $payNew->getNewPCWXpayVerify();
                    $payAction='wxpay';
                }
                if (!$res || (isset($res['trade_state']) && trim($res['trade_state']) != '0')) {
                    return array('error' => 'fail');
                }
                return $this->__paySuccess($res['out_trade_no'], $res['total_fee'], $payAction, $res['transaction_id']);
                break;
            case 'umspay':
            case 'wapumspay':
                $res = $pay->getUmsCallBackVerify();
                if (!$res) {
                    return array('error' => 'fail');
                }
                return $this->__paySuccess($res['MerOrderId'], $res['TransAmt'], $payAction, $res['TransId']);
                break;
            case 'unionpay':
            case 'wapunionpay':
                $res = $pay->getAppUnionPayVerify($payAction);
                if (!$res) {
                    return array('error' => 'fail');
                }
                return $this->__paySuccess($res['orderId'], $res['txnAmt'], $payAction, $res['queryId']);
                break;
                break;
            case 'lianlianpay':
                $res = $pay->getAppLianlianPayVerify();
                if (!$res) {
                    return array('error' => 'fail');
                }
                return $this->__paySuccess($res['no_order'], $res['money_order'], $payAction, $res['oid_paybill']);
                break;
            case 'wepay':
                $res = $pay->getWeCallBackVerify();

                if (!$res) {
                    return array('error' => 'fail');
                }
                if ($res['CODE'][0] != '0000') {
                    return array('error' => 'fail');
                }
                return $this->__paySuccess($res['ID'][0], $res['AMOUNT'][0], $payAction, 'JD-0000');
                break;

            // 退款回调
            case 'unionpay_refund':
            case 'wapunionpay_refund':
                $res = $pay->getAppUnionPayVerify($payAction);
                if (!$res) {
                    return array('error' => 'fail');
                }
                $res['refund_etime'] = time();
                return $this->thirdPartyRefundUpdateData($res);
                break;
            case 'lianlianpay_refund':
                $res = $pay->getAppLianlianPayRefundVerify();
                if (!$res) {
                    return array('error' => 'fail');
                }
                $res['refund_etime'] = time();
                return $this->thirdPartyRefundUpdateData($res);
                break;
            case 'alipay_refund':
                $res = $pay->pcPayVerify($payAction);
                if (!$res) {
                    return array('error' => 'fail');
                }
                $res['refund_etime'] = time();
                return $this->thirdPartyRefundUpdateData($res);
                break;
            case 'alipay_betch_refund':
                $res_data = $pay->pcPayVerify($payAction);
                if (!$res_data) {
                    return array('error' => 'fail');
                }
                foreach ($res_data as $v) {
                    $res = $this->thirdPartyRefundUpdateData($v);
                    if (isset($res['error']) && trim($res['error'])) {
                        return $res;
                    }
                }
                return array('message' => 'success');
                break;
            case 'wepay_refund':
                // 京东支付退款
                $res = $pay->getWePayRefoundVerify();
                if (!$res) {
                    return array('error' => 'fail');
                }
                $res['refund_etime'] = time();
                return $this->thirdPartyRefundUpdateData($res);
                break;
            default:
                return array('error' => 'fail');
                break;
        }
    }

    /**
     * 第三方支付回调处理
     */
    private function __paySuccess($pay_id, $total_fee, $payAction, $trade_no) {

        // 非法参数判断
        if (!trim($pay_id)) {
            return array('error' => 'fail');
        }

        // 优惠买单处理
        if (strpos(trim($pay_id),'dis') === 0) {
            return D('Discount')->paySuccess($pay_id, $total_fee, $payAction, $trade_no);
        }

        $order = M('order');

        // 查询条件查询
        $where = array(
            'pay_id' => trim($pay_id),
            'rstate' => 'normal',
        );

        if (strpos($pay_id, 'cart_') === false) {
            $order_id = trim($pay_id);
            if (strpos($order_id, '-') !== false) {
                list($_, $order_id, $_) = @explode('-', $order_id, 3);
            }
            if (strpos($order_id, 'U') !== false) {
                list($_, $order_id, $_) = @explode('U', $order_id, 3);
            }
            if (strpos($order_id, '_') !== false) {
                list($_, $order_id, $_) = @explode('_', $order_id, 3);
            }
            $where['id'] = $order_id;
            unset($where['pay_id']);
        }

        $orderRes = $order->where($where)->order('origin DESC')->select();
        $orderCount = count($orderRes);
        if ($orderCount < 1) {
            return array('error' => 'fail');
        }

        if (strpos($pay_id, 'cart_') !== false) {
            // 支付订单的数量是否与数据库查询出来的是否一致
            list($_, $_, $payOrderCount) = @explode('_', $pay_id, 3);
            // 防止购物车掉单
            if ($payOrderCount != $orderCount) {
                return array('error' => 'fail');
            }
        }

        if ($orderCount == 1) {
            $orderRes = array_pop($orderRes);
            if ($orderRes && isset($orderRes['state']) && $orderRes['state'] == 'unpay' && isset($orderRes['rstate']) && $orderRes['rstate'] == 'normal') {
                // 支付成功后更新数据库信息
                $upRes = $this->updateOrderUser($orderRes, $total_fee, 'CNY', $payAction, $this->payType[$payAction], $trade_no);
                if (!$upRes) {
                    return array('error' => 'fail');
                }
            }
            return array('message' => 'success');
        }

        // 购物车回调
        // 计算团单实际总余额
        $orderMoney = $order->where($where)->sum('origin');
        $credit = 0;
        if ($orderMoney > $total_fee) {
            $credit = $orderMoney - $total_fee;
        }

        foreach ($orderRes as $v) {
            $money = $v['origin'];
            if ($credit >= $money) {
                $credit = sprintf("%.2f", $credit - $money);
                $money = 0;
            } else {
                $money = sprintf("%.2f", $money - $credit);
                $credit = 0;
            }
            if (isset($v['state']) && $v['state'] == 'unpay' && isset($v['rstate']) && $v['rstate'] == 'normal') {
                // 支付成功后更新数据库信息
                $upRes = $this->updateOrderUser($v, $money, 'CNY', $payAction, $this->payType[$payAction], $trade_no);
                if (!$upRes) {
                    return array('error' => 'fail');
                }
            }
        }
        return array('message' => 'success');
    }

    /**
     * 第三方支付团款数据库更新
     * @param type $data
     * @return type
     */
    public function thirdPartyRefundUpdateData($data = array()) {


        $order_where = array();
        if (isset($data['pay_id']) && trim($data['pay_id'])) {
            $order_where['order.pay_id'] = $data['pay_id'];
        }
        if (isset($data['trade_no']) && trim($data['trade_no'])) {
            $order_where['order.trade_no'] = $data['trade_no'];
        }
        if (isset($data['order_id']) && trim($data['order_id'])) {
            $order_where['order.id'] = $data['order_id'];
        }
        if (!$order_where) {
            return array('error' => 'fail');
        }

        $field = array(
            'order.id' => 'order_id',
            'order.state' => 'order_state',
            'order.quantity' => 'order_quantity',
            'order.credit' => 'order_credit',
            'order.service' => 'order_service',
            'order.money' => 'order_money',
            'order.price' => 'order_price',
            'order.origin' => 'order_origin',
            'order.pay_time' => 'order_pay_time',
            'order.user_id' => 'order_user_id',
            'order.fare' => 'order_fare',
            'order.team_id' => 'order_team_id',
            'order.partner_id' => 'order_partner_id',
            'order.rstate' => 'order_rstate',
            'order.adminremark' => 'order_adminremark',
            'order.pay_id' => 'order_pay_id',
            'order.express' => 'order_express',
            'order.team_type' => 'order_team_type',
        );
        $orderRes = M('order')->field($field)->where($order_where)->find();
        if (!$orderRes) {
            return array('error' => 'fail');
        }

        // 处理时间
        $update_order_refund_time = array();
        if (isset($data['refund_ptime']) && trim($data['refund_ptime'])) {
            $update_order_refund_time['refund_ptime'] = $data['refund_ptime'];
        }
        if (isset($data['refund_etime']) && trim($data['refund_etime'])) {
            $update_order_refund_time['refund_etime'] = $data['refund_etime'];
        }
        if ($update_order_refund_time) {
            $res = M('order')->where($order_where)->save($update_order_refund_time);
        }

        if (isset($orderRes['order_rstate']) && $orderRes['order_rstate'] == 'berefund') {
            return array('message' => 'success');
        }

        $coupon_ids = array();
        if (isset($data['coupon_ids']) && $data['coupon_ids']) {
            $coupon_ids = $data['coupon_ids'];
        } else {
            $couponRes = M('coupon')->where(array('order_id' => $orderRes['order_id'], 'consume' => array('neq', 'Y')))->select();
            if ($couponRes) {
                foreach ($couponRes as $v) {
                    if (isset($v['id'])) {
                        $coupon_ids[] = $v['id'];
                    }
                }
            }
        }

        // 查询需要退款的券号
        $coupon_where = array('id' => array('in', $coupon_ids), 'consume' => array('neq', 'Y'));
        $couponRes = M('coupon')->where($coupon_where)->select();

        // 查询用户积分
        $userRes = M('user')->where(array('id' => $orderRes['order_user_id']))->field('score,money,id')->find();

        $orderRes['order_service_name'] = '';
        if (isset($orderRes['order_service']) && trim($orderRes['order_service']) != 'credit') {
            $orderRes['order_service_name'] = order_service($orderRes['order_service'], '第三方支付');
        }
        $coupon_unpay_count = count($coupon_ids);
        $orderRes['order_credit_money'] = $orderRes['order_credit'];
        $orderRes['order_third_party_money'] = $orderRes['order_money'];
        $this->setOrderRefundMoney($orderRes, $coupon_unpay_count);
        $teamRes = $this->where(array('id' => $orderRes['order_team_id']))->find();

        $model = M();
        $model->startTrans();

        $update_order_data = array(
            'state' => 'unpay',
            'rstate' => 'berefund',
        );
        $res = M('order')->where($order_where)->save($update_order_data);
        if (!$res) {
            $model->rollback();
            return array('error' => '订单更新失败！');
        }
        $adminremark = $this->getOrderadminremark($orderRes, ternary($data['refund_mark'], '操作成功！'), $data);
        $update_order_data = array(
            'adminremark' => $adminremark,
                //  'quantity' => (int) ($orderRes['order_quantity'] - $coupon_unpay_count),
                //  'origin' => sprintf("%.2f", ($orderRes['order_quantity'] - $coupon_unpay_count) * $orderRes['order_price']),
        );
        M('order')->where($order_where)->save($update_order_data);

        // 余额退回
        if (isset($orderRes['order_credit_money']) && $orderRes['order_credit_money'] > 0) {
            $res = M('user')->where(array('id' => $orderRes['order_user_id']))->setInc('money', $orderRes['order_credit_money']);
            if (!$res) {
                $model->rollback();
                return array('error' => '余额更新失败！');
            }
            $f_data = array(
                'user_id' => $orderRes['order_user_id'],
                'team_id' => $orderRes['order_team_id'],
                'partner_id' => $orderRes['order_partner_id'],
                'user_id' => $orderRes['order_user_id'],
                'pay_id' => $orderRes['order_pay_id'],
            );
            // 添加流水
            $res = $this->addFlowData($f_data, $orderRes['order_credit_money'], 'income', 'refund');
            if (!$res) {
                $model->rollback();
                return array('error' => '流水添加失败！');
            }
        }

        // 删除团券
        if ($couponRes) {
            // 过滤空值
            foreach ($couponRes as &$v) {
                $v = array_filter($v);
            }
            unset($v);
            $res = M('coupon_delete')->addAll($couponRes);
            if (!$res) {
                $model->rollback();
                return array('error' => '券号删除失败！');
            }
            $res = M('coupon')->where($coupon_where)->delete();
            if (!$res) {
                $model->rollback();
                return array('error' => '券号删除失败！');
            }

            //第三方作废电子凭证
            threeValidCoupon($orderRes['partner_id'], $couponRes, 'invalid');
        }

        team_state($teamRes);
        if (isset($teamRes['state']) && trim($teamRes['state']) != 'failure') {
            $minus = isset($teamRes['conduser']) && trim($teamRes['conduser']) == 'Y' ? 1 : $coupon_unpay_count;
            $res = M('team')->where(array('id' => $orderRes['order_team_id']))->setDec('now_number', $minus);
            if (!$res) {
                $model->rollback();
                return array('error' => '团单信息更新失败！');
            }
        }

        // 修改用户积分
        $_order_res = array(
            'origin' => $orderRes['order_credit_money'] + $orderRes['order_third_party_money'],
            'user_id' => $orderRes['order_user_id'],
            'team_id' => $orderRes['order_team_id']
        );

        $res = $this->delCredit($_order_res, $userRes);
        if (!$res) {
            $model->rollback();
            return array('error' => '积分扣减失败！');
        }


        $model->commit();
        return array('message' => 'success');
    }

    private function getOrderadminremark($orderRes, $refund_mark = '', $data = array()) {
        $adminremark = "{$orderRes['order_adminremark']}.\r\n";
        $adminremark .= "#第三方退款自动备注#\r\n";
        $adminremark .= " > 向[{$orderRes['order_service_name']}]平台申请退款，      \r\n";
        $adminremark .= " > 向用户余额中退款 {$orderRes['order_credit_money']}       \r\n";
        $adminremark .= " > 向[{$orderRes['order_service_name']}]中对应的用户账号原路退回 {$orderRes['order_third_party_money']} 元 \r\n";
        $adminremark .= " > 处理结果相关数据：{$refund_mark}\r\n";
        if (isset($data['op_admin_user']) && trim($data['op_admin_user'])) {
            $adminremark .= " > 操作员信息：{$data['op_admin_user']}\r\n";
        }
        $adminremark .= "#第三方退款结束#\r\n";
        return $adminremark;
    }

    /**
     * 设置第三方退款中的金额
     * @param type $orderRes
     * @param type $coupon_pay_count
     */
    public function setOrderRefundMoney(&$orderRes, $coupon_unpay_count = 0) {

        if(isset($orderRes['order_team_type']) && trim($orderRes['order_team_type']) == 'cloud_shopping'){
            return true;
        }

        if (!isset($orderRes['order_express']) || trim($orderRes['order_express']) == 'N') {
            if ($coupon_unpay_count < $orderRes['order_quantity']) {
                $refund_money = sprintf("%.2f", $coupon_unpay_count * $orderRes['order_price']);
                if ($orderRes['order_credit_money'] > 0) {
                    if ($orderRes['order_credit_money'] >= $refund_money) {
                        $orderRes['order_credit_money'] = $refund_money;
                        $orderRes['order_third_party_money'] = '0.00';
                    } else {
                        $orderRes['order_third_party_money'] = sprintf("%.2f", $refund_money - $orderRes['order_credit_money']);
                    }
                } else {
                    $orderRes['order_third_party_money'] = $refund_money;
                }
            }
        } else {
            $fare = ternary($orderRes['order_fare'], 0);
            if ($fare > 0 && $orderRes['order_credit_money'] >= $fare) {
                $orderRes['order_credit_money'] = sprintf("%.2f", $orderRes['order_credit_money'] - $fare);
            } else if ($fare > 0 && $orderRes['order_third_party_money'] >= $fare) {
                $orderRes['order_third_party_money'] = sprintf("%.2f", $orderRes['order_third_party_money'] - $fare);
            } else if ($fare > 0) {
                if ($fare > $orderRes['order_credit_money']) {
                    $fare = sprintf("%.2f", $fare - $orderRes['order_credit_money']);
                    $orderRes['order_credit_money'] = 0.00;
                }
                if ($fare > 0) {
                    $orderRes['order_third_party_money'] = sprintf("%.2f", $orderRes['order_third_party_money'] - $fare);
                    if ($orderRes['order_third_party_money'] < 0) {
                        $orderRes['order_third_party_money'] = 0.00;
                    }
                }
            }
        }
    }

    /**
     * 获取团单的列表的距离
     */
    public function getTeamDistance(&$res, $data = array(), $is_one = false) {

        if (!isset($data['lng']) || !trim($data['lng'])) {
            return false;
        }
        if (!isset($data['lat']) || !trim($data['lat'])) {
            return false;
        }
        if ($is_one) {
            $res = array($res);
        }
        foreach ($res as &$v) {
            $v['distance'] = '';
            if (!isset($v['partner']['lng']) || !trim($v['partner']['lng'])) {
                continue;
            }
            if (!isset($v['partner']['lat']) || !trim($v['partner']['lat'])) {
                continue;
            }
            $v['distance'] = $this->getPhpLngLatDistance($v['partner']['lat'], $v['partner']['lng'], $data['lat'], $data['lng']);
        }
        unset($v);
        if ($is_one) {
            $res = array_pop($res);
        }
        return $res;
    }

    /**
     * php 代码计算 经纬度之间的距离
     * @param type $lat1
     * @param type $lng1
     * @param type $lat2
     * @param type $lng2
     * @return type
     */
    public function getPhpLngLatDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = self::EARTH_RADIUS;
        // 转换为弧度
        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;
        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;
        // 使用半正矢公式  用尺规来计算
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;
        return sprintf('%.4f', $calculatedDistance);
    }

    /**
     * 获取分店信息
     * @param type $partner_id
     * @param type $data
     * @param type $is_count
     * @return type
     */
    public function getParnerAllBranchList($partner_id = '', $data = array(), $is_count = false) {
        $fid = $partner_id;
        $res = M('partner')->where(array('id' => $partner_id))->field('is_branch,fid')->find();
        if (isset($res['is_branch']) && strtolower($res['is_branch']) == 'n') {
            if (isset($res['fid']) && trim($res['fid'])) {
                $fid = $res['fid'];
            }
        }

        $where = array('fid' => $fid, '_logic' => 'OR', 'id' => $partner_id);
        if ($is_count) {
            return M('partner')->where($where)->count();
        }
        $field = array(
            'id' => 'partner_id',
            'group_id' => 'group_id',
            'title' => 'part_title',
            'images' => 'images',
            'long' => 'long',
            'lat' => 'lat',
            'username' => 'username',
            'long' => 'long',
            'address' => 'address',
            'mobile' => 'mobile',
            'phone' => 'phone',
        );
        $pdate=M('partner')->field($field)->where($where)->select();

        $data = $this->dealPartnerData($pdate, array(), $data);

        return $data;
    }

    /**
     * 整理商家数据
     * @param type $partnerRes
     * @param type $category
     * @param type $is_one
     * @return type
     */
    public function dealPartnerData(&$partnerRes, $category = array(), $data = array(), $is_one = false) {
        if (!$partnerRes) {
            return array();
        }
        if ($is_one) {
            $partnerRes = array($partnerRes);
        }

        foreach ($partnerRes as &$res) {
            $res = array(
                'partner_id' => ternary($res['partner_id'], ''),
                'group_id' => ternary($res['group_id'], ''),
                'part_title' => ternary($res['part_title'], ''),
                'images' => isset($res['images']) ? getImagePath($res['images']) : '',
                'lng' => ternary($res['long'], ''),
                'lat' => ternary($res['lat'], ''),
                'username' => ternary($res['username'], ''),
                'distance' => ternary($res['distance'], ''),
                'comment_avg_num' => isset($res['comment_avg_num']) ? number_format($res['comment_avg_num'], 1) : '0.0',
                'cate_name' => ternary($category[$res['group_id']]['name'], ''),
                'sort' => ternary($res['sort'], ''),
                'address' => ternary($res['address'], ''),
                'mobile' => isset($res['mobile']) && trim($res['mobile']) ? ternary($res['mobile'], '') : ternary($res['phone'], ''),
            );

            if (isset($data['lng']) && trim($data['lng']) && isset($data['lat']) && trim($data['lat'])) {
                $res['distance'] = $this->getPhpLngLatDistance($res['lat'], $res['lng'], $data['lat'], $data['lng']);
            }
        }
        unset($res);
        if ($is_one) {
            $partnerRes = array_pop($partnerRes);
        }
        return $partnerRes;
    }

    /**
     * 获取团单的属性列表
     * @param $team
     * @return array|mix
     */
    public function getTeamAttrItem($team) {
        if (is_numeric($team)) {
            $team = $this->find($team);
        }

        if ($team['is_optional_model'] == 'Y') {
            $data = M('team_attribute')->index('id')->where('team_id=' . $team['id'])->select();
            if (!$data) {
                $this->errorInfo['error'] = M('team_attribute')->getDbError();
                $this->errorInfo['sql'] = M('team_attribute')->_sql();
            }
            return $data;
        }
        return false;
    }

    /**
     * 添加团单属性
     * @param $attrItem
     * @param $attrNum
     * @param $id
     * @return bool|int|mixed
     */
    public function addTeamAttrItem($attrItem, $attrNum, $id) {
        $attrData = array();
        foreach ($attrItem as $key => $val) {
            if ($val) {
                $num = ternary($attrNum[$key], 0);
                $attrData[] = array(
                    'team_id' => $id,
                    'name' => $val,
                    'max_num' => intval($num),
                    'create_time' => time(),
                    'update_time' => time()
                );
            }
        }
        if (M('team_attribute')->addAll($attrData)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 更新团单属性
     * @param $team
     * @param $attrId
     * @param $attrItem
     * @param $attrNum
     * @return bool|int|mixed
     */
    public function updateTeamAttrItem($team, $attrId, $attrItem, $attrNum, $attrList = array()) {
        //如果原团单is_optional_model为N的话,等于新增属性
        //1.查出团单对应的属性
        //2.对比,找出需要更新|添加|删除的属性
        //3.进行相应的操作
        if (!$attrList) {
            $attrList = $this->getTeamAttrItem($team);
        }
        if ($team['old_is_optional_model'] == 'N' || empty($attrList)) {
            return $this->addTeamAttrItem($attrItem, $attrNum, $team['id']);
        }

        $attrModel = M('team_attribute');
        $addData = $updateData = $delData = array();
        foreach ($attrId as $k => $v) {
            $v = intval($v);
            if ($v == 0) {
                $addData[] = array(
                    'team_id' => $team['id'],
                    'name' => $attrItem[$k],
                    'max_num' => intval($attrNum[$k]),
                    'create_time' => time(),
                    'update_time' => time()
                );
            } else {
                if (in_array($v, array_keys($attrList))) {
                    if ($attrList[$v]['max_num'] != $attrNum[$k] || $attrList[$v]['name'] != $attrItem[$k]) {
                        $attrModel->where('id=' . $v)->save(array(
                            'name' => $attrItem[$k],
                            'max_num' => intval($attrNum[$k]),
                            'update_time' => time()
                        ));
                    }
                } else {
                    $delData[] = $v;
                }
            }
        }

        $delData = array_merge($delData, array_diff(array_keys($attrList), $attrId));
        if (count($delData) > 0) {
            $attrModel->where(array('id' => array('IN', $delData), 'team_id' => $team['id']))->delete();
        }

        if (count($addData) > 0) {
            $attrModel->addAll($addData);
        }

        return true;
    }

    /**
     * 删除团单属性
     * @param $id
     * @return mixed
     */
    public function delTeamAttrItem($id) {
        return M('team_attribute')->where('team_id=' . intval($id))->delete();
    }

}
