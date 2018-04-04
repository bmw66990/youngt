<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class TeamCouponController extends CommonController {

    public function __construct() {
        parent::__construct();
    }

    public function index($partner_id = '') {

        if (!trim($partner_id)) {
            $partner_id = $this->partner_id;
        }

        // 查询条件
        $where = array(
            'partner_id' => $partner_id
        );

        // 查询数据
        $team = M('team');
        $count = $team->where($where)->count();
        $Page = $this->pages($count, $this->reqnum);
        $field = 'id,product,expire_time';
        $teamRes = $team->where($where)->field($field)->order('expire_time DESC,id desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();

        // 获取团卷数据
        if ($teamRes) {
            $coupon = M('coupon');
            $filed = array('consume' => 'consume', 'count(id)' => 'consume_count');
            foreach ($teamRes as &$v) {
                $res = $coupon->where(array('team_id' => $v['id'], 'consume' => array('in', array('Y', 'N'))))->field($filed)->group('consume')->select();
                $allCount = 0;
                $v['sumconsume'] = 0;
                $v['sumrconsume'] = 0;
                foreach ($res as $_v) {
                    
                    switch (strtolower(trim($_v['consume']))) {
                        case 'y':
                            $v['sumconsume'] = $_v['consume_count'];
                            $allCount+=$_v['consume_count'];
                            break;
                        case 'n':
                            $v['sumrconsume'] = $_v['consume_count'];
                            $allCount+=$_v['consume_count'];
                            break;
                    }
                }
                $v['sumconpon'] = $allCount;
            }
        }

        // 查询分店
        $partner = D('Partner');
        $partner_breach = $partner->getPartnerBranch($this->partner_id);

        $data = array(
            'count' => $count,
            'page' => $Page->show(),
            'list' => $teamRes,
            'partner_breach' => $partner_breach,
            'partner_id' => $partner_id,
        );
        
        $this->assign($data);
        $this->display();
    }

}
