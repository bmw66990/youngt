<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-03-21
 * Time: 09:46
 */
namespace Manage\Controller;

/**
 * 统计
 * Class ChartController
 * @package Manage\Controller
 */
class ChartController extends CommonController {

    /**
     * 管理员统计信息
     * @var array
     */
    protected $_allCount   = array(
        'regUser', 'onlineTeam', 'newTeam', 'orderNum', 'recharge'
    );

    /**
     * 管理员异步统计
     * @var array
     */
    protected $_asyncAllCount = array(
        'orderMoney', 'payMoney', 'sourcePay',
    );

    /**
     * 代理统计信息
     * @var array
     */
    protected $_agentCount = array(
        'onlineTeam', 'newTeam', 'orderNum'
    );

    /**
     * 代理异步统计
     * @var array
     */
    protected $_asyncAgentCount = array(
        'payMoney', 'sourcePay',
    );

    /**
     * 城市id
     * @var
     */
    protected $_cityId;

    /**
     * 按周/月/年搜索
     * @var
     */
    protected $_timeType;

    /**
     * 搜索时间
     * @var array
     */
    protected $_searchTime = array();

    public function __construct() {
        parent::__construct();
        $this->_cityId = $this->_getCityId();
        $this->_getCountTime();
    }

    /**
     * 展示统计信息
     */
    public function index() {
        if(empty($this->_cityId)) {
            $count = $this->_allCount;
        } else {
            $count = $this->_agentCount;
        }
        foreach($count as $row) {
            $$row = $this->{'_' . $row}();
            $this->assign("{$row}", $$row);
        }
        $weekList = array('周日', '周一', '周二', '周三', '周四', '周五', '周六');
        $this->assign('weekList', $weekList);
        $this->display();
    }

    /**
     * 异步获取数据
     */
    public function async() {
        $this->_cityId = $this->_getCityId();
        if(empty($this->_cityId)) {
            $count = $this->_asyncAllCount;
        } else {
            $count = $this->_asyncAgentCount;
        }
        $this->_getCountTime();
        foreach($count as $row) {
            $$row = $this->{'_' . $row}();
            $this->assign("{$row}", $$row);
        }
        $data['html']  = $this->fetch('count_tpl');
        $chart         = $this->_getChartData($payMoney, $sourcePay);
        $data['chart'] = $chart;
        $this->ajaxReturn($data);
    }

    /**
     * 组装统计数据
     * @param $payMoney
     * @param $sourcePay
     * @return array
     */
    protected function _getChartData($payMoney, $sourcePay) {
        $data  = array();
        $stime = $this->_searchTime['st'];
        $etime = $this->_searchTime['et'];
        while ($stime < $etime) {
            $row         = date('Y-m-d', $stime);
            $tmp['day'] = $row;
            $tmp['r0']   = ternary($payMoney['paytotal'][$row], 0);
            $tmp['r1']   = ternary($payMoney['online'][$row], 0);
            $tmp['r2']   = ternary($payMoney['balance'][$row], 0);
            $tmp['r3']   = ternary($sourcePay['pc'][$row], 0);
            $tmp['r4']   = ternary($sourcePay['wap'][$row], 0);
            $tmp['r5']   = ternary($sourcePay['android'][$row], 0);
            $tmp['r6']   = ternary($sourcePay['ios'][$row], 0);
            $data[]      = $tmp;
            $stime       = strtotime("+1 day", $stime);
        }
        return $data;
    }

    /**
     * 获取统计日期
     */
    protected function _getCountTime() {
        $type = I('get.type', 'week', 'trim');
        $this->_timeType = $type;
        switch($type) {
            case 'week':
                $this->_getWeekCountTime();
                break;
            case 'month':
                $this->_getMonthCountTime();
                break;
            case 'year':
                $this->_getYearCountTime();
                break;
            default:
                $this->error('非法访问！');
        }
    }

    /**
     * 周筛选时间获取
     */
    protected function _getWeekCountTime() {

        $page = I('get.page', 0, 'intval');
        $page = $page < 0 ? 0 : $page;
        $pageweek = 7 * $page;

        $pageday = date('Y-m-d', strtotime("-{$pageweek} days"));
        $daytime = strtotime($pageday);
//        $daytime=strtotime('2016-3-12');
        $w = date('w');
        $this->_searchTime['st'] = $daytime - $w * 86400;
        $this->_searchTime['et'] = $daytime + (7 - $w) * 86400;

        $this->_searchTime['format'] = 'Y-m-d';
        $this->_searchTime['len'] = 'day';
        $this->assign('searchTime', $this->_searchTime);
        $this->assign('curPage', $page);

    }

    // 废弃
    // 请查看 getDaydata
    public function getWeekData(){
        $page = I('get.page', 0, 'intval');
        $start = I('get.start');
		//var_dump($start);	
		if($start){
			$daytime = strtotime("{$start}");			
		}else{
			$page = $page < 0 ? 0 : $page;
			$pageweek = 7 * $page;
			$pageday = date('Y-m-d', strtotime("-{$pageweek} days"));	
			$daytime = strtotime($pageday);
		}
		
        $w = date('w');
        $this->_searchTime['st'] = $daytime - $w * 86400;
        $this->_searchTime['et'] = $daytime + (7 - $w) * 86400;

        $this->_searchTime['format'] = 'Y-m-d';
        $this->_searchTime['len'] = 'day';
        $this->assign('searchTime', $this->_searchTime);
        $this->assign('curPage', $page);
        $this->assign('start', $start);
		
        $city_id=$this->_cityId;

        $stime=$this->_searchTime['st'];
        $etime=$this->_searchTime['et'];

		$model = D('Order');

        $pays    = $model->stateCount($city_id,$stime,$etime,10,'pay');
        $refunds = $model->stateCount($city_id,$stime,$etime,10,'refund');
        $unpays  = $model->stateCount($city_id,$stime,$etime,10,'unpay');
        $verifys = $model->vCouponCount($city_id,$stime,$etime,10);
        $dayorder = array();
        while ($stime < $etime) {
            $time = get_weekday(date('w',$stime)).date('(d)', $stime);
            $num['time']   = $time;
            $num['pay']    = ternary($pays[$time], 0);
            $num['unpay']  = ternary($unpays[$time], 0);
            $num['refund'] = ternary($refunds[$time], 0);
            $num['verify'] = ternary($verifys[$time], 0);
            $dayorder[] = $num;
            $stime = strtotime("+1 day", $stime);
        }
        $res['data'] = $dayorder;
        $this->ajaxReturn($res);
    }
	
	// 废弃
    // 请查看 getMonthdata
	public function getMonthTotalData(){
	    $page = I('get.page', 0, 'intval');	
		$start = I('get.start');
		$curmonth = intval(date('m'));//=3	
		if($start){
			$stime = date('Y-m', strtotime("{$start} -6 months"));//2
			$etime = date('Y-m', strtotime("{$start}"));//14
		}else{
			$sdate= ($page+1)*6+$page-1;
			$edate = 6 * $page-1 ;
			$stime = date('Y-m', strtotime("-{$sdate} months"));//2
			$etime = date('Y-m', strtotime("-{$edate} months"));//14
		}
	    
	    $this->_searchTime['st']     = strtotime($stime);
	    $this->_searchTime['et']     = strtotime($etime);
	    $this->_searchTime['format'] = 'Y-m';
	    $this->_searchTime['len']    = 'month';

	    $city_id=$this->_cityId;
	    $stime=$this->_searchTime['st'];
	    $etime=$this->_searchTime['et'];

	    $model = D('Order');

	    $pays    = $model->stateCount($city_id,$stime,$etime,7,'pay');
	    $refunds = $model->stateCount($city_id,$stime,$etime,7,'refund');
	    $unpays  = $model->stateCount($city_id,$stime,$etime,7,'unpay');
	    $verifys = $model->vCouponCount($city_id,$stime,$etime,7);
	    $orderData = array();

	    while ($stime < $etime) {
	        $time = date('m', $stime).'月';
	        $num['time']   = $time;
	        $num['pay']    = ternary($pays[$time], 0);
	        $num['unpay']  = ternary($unpays[$time], 0);
	        $num['refund'] = ternary($refunds[$time], 0);
	        $num['verify'] = ternary($verifys[$time], 0);
	        $monthorder[]  = $num;
	        $stime = strtotime("+1 month", $stime);
	    }

	    $res['data'] = $monthorder;	
		$this->ajaxReturn($res);
	}
	
	// 统计信息
    public function stats_list() {
        $curtab = I('get.curtab','day','trim');

        $cur_day = date('Y-m-d');
        $day['end']  = I('get.end_day',date('Y-m-d'),'trim');
        $day['prev'] = date('Y-m-d', strtotime("{$day['end']} -1 months"));
        $day['next'] = date('Y-m-d', strtotime("{$day['end']} +1 months"));
        if ($day['next'] > $cur_day) {
            $day['next'] = $cur_day;
        }

        $cur_month = date('Y-m-d');
        $month['end']  = I('get.end_month',date('Y-m'),'trim');
        $end  = $month['end'].'-'.date('d');
        $month['prev'] = date('Y-m', strtotime("{$end} -1 years"));
        $month['next'] = date('Y-m', strtotime("{$end} +1 years"));
        if ($month['next'] > $cur_month) {
            $month['next'] = $cur_month;
        }

        $this->assign('curtab', $curtab);
		$this->assign('day', $day);
        $this->assign('month', $month);
        $this->display();
    }

    // 按天统计
    function getDaydata() {
        $city_id = $this->_cityId;
        $end_day = I('get.end_day');

        $etime = strtotime(date('Y-m-d', strtotime("{$end_day} +1 days"))) - 1;
		$stime = strtotime(date('Y-m-d', strtotime("{$end_day} -1 months")));

		$model = D('Order');

		$all      = $model->stateCount($city_id,$stime,$etime,10);
		$pays     = $model->stateCount($city_id,$stime,$etime,10,'pay');
		$refunds  = $model->stateCount($city_id,$stime,$etime,10,'refund');
		$unpays   = $model->stateCount($city_id,$stime,$etime,10,'unpay');
		$vcoupons = $model->vCouponCount($city_id,$stime,$etime,10);
		$list = [];
        while ($stime <= $etime) {
            $time = date('Y-m-d', $stime);
            $num['time']   = $time;
            $num['all']    = ternary($all[$time], 0);
            $num['pay']    = ternary($pays[$time], 0);
            $num['refund'] = ternary($refunds[$time], 0);
            $num['unpay']  = ternary($unpays[$time], 0);
            $num['verify'] = ternary($vcoupons[$time], 0);
            $list[] = $num;
            $stime = strtotime("+1 day", $stime);
        }
        $res['data'] = $list;
        $this->ajaxReturn($res);
    }

    // 按月统计
    function getMonthdata() {
        $city_id   = $this->_cityId;
        $end_month = I('get.end_month');

        $etime = strtotime(date('Y-m', strtotime("{$end_month} +1 months"))) - 1;
        $stime = strtotime(date('Y-m', strtotime("{$end_month} -1 years")));
    
        $model = D('Order');

		$all      = $model->stateCount($city_id,$stime,$etime,7);
		$pays     = $model->stateCount($city_id,$stime,$etime,7,'pay');
		$refunds  = $model->stateCount($city_id,$stime,$etime,7,'refund');
		$unpays   = $model->stateCount($city_id,$stime,$etime,7,'unpay');
		$vcoupons = $model->vCouponCount($city_id,$stime,$etime,7);
		$list = [];
        while ($stime <= $etime) {
            $time = date('Y-m', $stime);
            $num['time']   = $time;
            $num['all']    = ternary($all[$time], 0);
            $num['pay']    = ternary($pays[$time], 0);
            $num['refund'] = ternary($refunds[$time], 0);
            $num['unpay']  = ternary($unpays[$time], 0);
            $num['verify'] = ternary($vcoupons[$time], 0);
            $list[] = $num;
            $stime = strtotime("+1 month", $stime);
        }
        $res['data'] = $list;
        $this->ajaxReturn($res);
    }

    /**
     * 月筛选时间获取
     */
    protected function _getMonthCountTime() {
        $page = I('get.page', 0, 'intval');
        $curmonth = intval(date('m'));
        if($page == 0) {
            $edate = 0;
        } else if($page == 1) {
            $edate = $curmonth - 1;
        } else {
            $edate = ($page - 1) * 12 + $curmonth - 1;
        }
        $sdate = 12 * $page + $curmonth - 1;
        $stime = date('Y-m-01 00:00:00', strtotime("-{$sdate} months"));
        $etime = date('Y-m-01 00:00:00', strtotime("-{$edate} months"));

        $this->_searchTime['st']     = strtotime($stime);
        $this->_searchTime['et']     = strtotime($etime);
        $this->_searchTime['format'] = 'Y-m';
        $this->_searchTime['len']    = 'month';

}


    /**
     * 年筛选时间获取
     * 5年
     */
    protected function _getYearCountTime() {
        $page  = I('get.page', 1, 'intval');
        $stime = $page * 5;
        $stime = strtotime("-$stime years");
        $etime = ($page - 1) * 5;
        $etime = strtotime("-$etime years");

        $this->_searchTime['st']     = strtotime(date('Y-01-01 00:00:00', $stime));
        $this->_searchTime['et']     = strtotime(date('Y-12-31 23:59:59', $etime)) + 1;
        $this->_searchTime['format'] = 'Y';
        $this->_searchTime['len']    = 'year';
    }

    public function getRegUser() {
        $data = $this->_regUser();
        $this->assign('regUser', $data);
        $res['html'] = $this->fetch('count_tpl');
        $this->ajaxReturn($res);
    }

    /**
     * 注册用户统计
     * @return mixed
     */
    protected function _regUser() {
        //$list = D('User')->regUserCount($this->_cityId, $this->_searchTime['st'], $this->_searchTime['et'], $this->_timeType);   //旧的统计方法
        $list = D('User')->newRegUserCount($this->_cityId, $this->_searchTime['st'], $this->_searchTime['et']); //新的统计方法
        $this->_writeDBErrorLog($list, D('User'));
        $data = $this->_combinationData('reguser', $list);
        return $data;
    }

    /**
     * 上线项目统计
     * @return array
     */
    protected function _onlineTeam() {
        $model = M('Team');

        /*$data           = array();
        $city_id        = $this->_cityId;
        $map            = array();
        $map['city_id'] = $city_id;
        foreach ($this->_searchTime['week'] as $row) {
            $curTime           = strtotime($row);
            $map['begin_time'] = array('elt', $curTime);
            $map['end_time']   = array('egt', $curTime);
            $rs                = $model->where($map)->count();
            if($rs === false) {
                //TODO 记录错误
            }
            $data[$row] = $rs;
        }*/

        //TODO 后期需要性能测试
        $map['city_id']    = $this->_cityId;
        $map['begin_time'] = array('elt', $this->_searchTime['et']);
        $map['end_time']   = array('egt', $this->_searchTime['st']);

        $list = $model->field('id,begin_time,end_time')->where($map)->select();
        $this->_writeDBErrorLog($list, $model);

        $data  = array();
        $stime = $this->_searchTime['st'];
        $etime = $this->_searchTime['et'];
        while($stime < $etime) {
            $row = date($this->_searchTime['format'], $stime);
            $ct = $stime;
            foreach($list as $key=>$val) {
                if($ct >= $val['begin_time'] && $ct <= $val['end_time']) {
                    if(isset($data[$row])) {
                        $data[$row] += 1;
                    } else {
                        $data[$row] = 1;
                    }
                }
                if($ct > $val['end_time']) {
                    unset($list[$key]);
                }
            }
            $stime = strtotime("+1 {$this->_searchTime['len']}", $stime);
        }
        return $data;
    }

    /**
     * 新项目统计
     * @return mixed
     */
    protected function _newTeam() {
        $where['city_id']    = $this->_cityId;
        $where['begin_time'] = array('between', array($this->_searchTime['st'], $this->_searchTime['et']));
        $data = M('Team')->field('LEFT(FROM_UNIXTIME(begin_time),10) ct,count(*) num')->where($where)->group('ct')->order('null')->select();
        $this->_writeDBErrorLog($data, M('Team'));
        $list = array();
        foreach($data as $row) {
            $list['newteam'][$row['ct']] = $row['num'];
        }
        return $this->_combinationData('newteam', $list);
    }

    /**
     * 获取订单数量统计
     */
    public function getOrderNum() {
        $data = $this->_orderNum();
        $this->assign('orderNum', $data);
        $res['html'] = $this->fetch('count_tpl');
        $this->ajaxReturn($res);
    }

    /**
     * 订单数量统计
     * @return array
     */
    protected function _orderNum() {
        $list = D('Order')->orderNumCount($this->_cityId, $this->_searchTime['st'], $this->_searchTime['et'], $this->_timeType);
        $this->_writeDBErrorLog($list, D('Order'));
        $data = array();
        $stime = $this->_searchTime['st'];
        $etime = $this->_searchTime['et'];
        while($stime < $etime) {
            $val = date($this->_searchTime['format'], $stime);
            $data['pay'][$val]   = ternary($list['pay'][$val], 0);
            $data['unpay'][$val] = ternary($list['unpay'][$val], 0);
            $data['total'][$val] = $data['pay'][$val] + $data['unpay'][$val];
            $stime = strtotime("+1 {$this->_searchTime['len']}", $stime);
        }
        return $data;
    }

    /**
     * 获取充值统计
     */
    public function getRecharge() {
        $data = $this->_recharge();
        $this->assign('recharge', $data);
        $res['html'] = $this->fetch('count_tpl');
        $this->ajaxReturn($res);
    }

    /**
     * 充值统计
     * @return array
     */
    protected function _recharge() {
        $list = D('Flow')->rechargeCount($this->_cityId, $this->_searchTime['st'], $this->_searchTime['et'], $this->_timeType);
        $this->_writeDBErrorLog($list, D('Flow'));
        $data = $this->_combinationData(array('charge', 'store'), $list);
        return $data;
    }

    /**
     * 订单金额
     * @return mixed
     */
    protected function _orderMoney() {
        $list = D('Order')->orderMoneyCount($this->_cityId, $this->_searchTime['st'], $this->_searchTime['et'], $this->_timeType);
        $this->_writeDBErrorLog($list, D('Order'));
        $data  = array();
        $stime = $this->_searchTime['st'];
        $etime = $this->_searchTime['et'];
        while($stime < $etime) {
            $val   = date($this->_searchTime['format'], $stime);
            $pay   = ternary($list['pay'][$val], 0);
            $unpay = ternary($list['unpay'][$val], 0);
            $data['pay'][$val]   = $pay;
            $data['unpay'][$val] = $unpay;
            $data['total']       = $pay + $unpay;
            $stime = strtotime("+1 {$this->_searchTime['len']}", $stime);
        }
        return $data;
    }

    /**
     * 支付金额
     * @return array
     */
    protected function _payMoney() {
        $list = D('Order')->payMoneyCount($this->_cityId, $this->_searchTime['st'], $this->_searchTime['et'], $this->_timeType);
        $this->_writeDBErrorLog($list, D('Order'));
        $data  = $this->_combinationData(array('balance', 'online', 'paytotal'), $list);
        return $data;
    }

    /**
     * 来源支付
     * @return array
     */
    protected function _sourcePay() {
        $list = D('Order')->sourcePayCount($this->_cityId, $this->_searchTime['st'], $this->_searchTime['et'], $this->_timeType);
        $this->_writeDBErrorLog($list, D('Order'));
        $data  = array();
        $stime = $this->_searchTime['st'];
        $etime = $this->_searchTime['et'];
        while($stime < $etime) {
            $val      = date($this->_searchTime['format'], $stime);
            $wap      = ternary($list['m.youngt.com'][$val], 0) + ternary($list['wap'][$val], 0) + ternary($list['mobile.youngt.com'][$val], 0);
            $pc       = ternary($list['pc'][$val], 0) + ternary($list[''][$val], 0);
            $android1 = ternary($list['android'][$val], 0);
            $android2 = ternary($list['newandroid'][$val], 0);
            $android  = $android1 + $android2;
            $ios1     = ternary($list['ios'][$val], 0);
            $ios2     = ternary($list['newios'][$val], 0);
            $ios      = $ios1 + $ios2;

            $data['wap'][$val]          = $wap;
            $data['pc'][$val]           = $pc;
            $data['android'][$val]      = $android;
            $data['ios'][$val]          = $ios;
            $total                      = $wap + $pc + $android + $ios;
            $data['wap_rate'][$val]     = sprintf('%.2f', ($wap / ($total) * 100)) . '%';
            $data['pc_rate'][$val]      = sprintf('%.2f', ($pc / ($total) * 100)) . '%';
            $data['android_rate'][$val] = sprintf('%.2f', ($android / ($total) * 100)) . '%';
            $data['ios_rate'][$val]     = sprintf('%.2f', ($ios / ($total) * 100)) . '%';

            $stime = strtotime("+1 {$this->_searchTime['len']}", $stime);
        }
        return $data;
    }

    /**
     * 获取支付类型占比统计
     */
    public function getPayType() {
        $data = $this->_payType();
        $this->assign('payType', $data);
        $res['html'] = $this->fetch('count_tpl');
        $this->ajaxReturn($res);
    }

    /**
     * 支付类型占比统计
     * @return array
     */
    protected function _payType() {
        if(!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['pay_time'][] = array('egt', $this->_searchTime['st']);
        $where['pay_time'][] = array('lt', $this->_searchTime['et']);
        $where['_string']    = "(state='pay') OR (state='unpay' && rstate='berefund')";
        $total = D('Order')->getTotal($where);
        $list = D('Order')->orderSourceCount($this->_cityId, $this->_searchTime['st'], $this->_searchTime['et'], $this->_timeType);
        $this->_writeDBErrorLog($list, D('Order'));
        $data  = array();
        $stime = $this->_searchTime['st'];
        $etime = $this->_searchTime['et'];
        while($stime < $etime) {
            $row       = date($this->_searchTime['format'], $stime);
            $alipay    = ternary($list['alipay'][$row], 0) + ternary($list['aliapp'][$row], 0) + ternary($list['aliwap'][$row], 0) + ternary($list['pcalipay'][$row], 0) + ternary($list['wapalipay'][$row], 0);
            $tenpay    = ternary($list['tenpay'][$row], 0) + ternary($list['tenapp'][$row], 0) + ternary($list['tenwap'][$row], 0) + ternary($list['pctenpay'][$row], 0) + ternary($list['waptenpay'][$row], 0);
            $wechatpay = ternary($list['wechatpay'][$row], 0) + ternary($list['wapwechatpay'][$row], 0) + ternary($list['pcwxpaycode'][$row], 0);
            $umspay    = ternary($list['umspay'][$row], 0) + ternary($list['wapumspay'][$row], 0);

            //TODO 考虑数据重组
            $data['alipay'][$row]    = sprintf('%.2f', $alipay / $total * 100);
            $data['tenpay'][$row]    = sprintf('%.2f', $tenpay / $total * 100);
            $data['wechatpay'][$row] = sprintf('%.2f', $wechatpay / $total * 100);
            $data['umspay'][$row]    = sprintf('%.2f', $umspay / $total * 100);
            /*$data[$row] = array(
                'alipay'    => sprintf('%.2f', $alipay / $total * 100),
                'tenpay'    => sprintf('%.2f', $tenpay / $total * 100),
                'wechatpay' => sprintf('%.2f', $wechatpay / $total * 100),
                'umspay'    => sprintf('%.2f', $umspay / $total * 100),
            );*/
            $stime = strtotime("+1 {$this->_searchTime['len']}", $stime);
        }
        return $data;
    }

    /**
     * 统计数据组合
     * @param $attr
     * @param $data
     * @return array
     */
    protected function _combinationData($attr, $data) {
        $list   = array();
        $stime  = $this->_searchTime['st'];
        $etime  = $this->_searchTime['et'];
        $format = $this->_searchTime['format'];
        $type   = $this->_searchTime['len'];
        while($stime < $etime) {
            $row = date($format, $stime);
            if (is_string($attr)) {
                $list[$attr][$row] = ternary($data[$attr][$row], 0);
            } else {
                foreach($attr as $val) {
                    $list[$val][$row] = ternary($data[$val][$row], 0);
                }
            }
            $stime = strtotime("+1 {$type}", $stime);
        }
        return $list;
    }

    /**
     * 团单总和
     * @return mixed
     */
    public function getTotalTeam() {
        $map = array();
        if($this->_cityId) {
            $map['city_id'] = $this->_cityId;
        }
        $count = D('Team')->getTotal($map);
        $this->_writeDBErrorLog($count, D('Team'));
        $this->ajaxReturn(array('html' => $count));
    }

    /**
     * 用户总和
     * @return mixed
     */
    public function getTotalUser() {
        $map = array();
        if($this->_cityId) {
            $map['city_id'] = $this->_cityId;
        }
        $count = D('User')->getTotal($map);
        $this->_writeDBErrorLog($count, D('User'));
        $this->ajaxReturn(array('html' => $count));
    }

    /**
     * 团购订单总和
     * @return mixed
     */
    public function getTotalOrder() {
        $map = array();
        if($this->_cityId) {
            $map['city_id'] = $this->_cityId;
        }
        $count = D('Order')->getTotal($map);
        $this->_writeDBErrorLog($count, D('Order'));
        $this->ajaxReturn(array('html' => $count));
    }
}