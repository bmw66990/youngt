<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/5/4
 * Time: 11:22
 */

namespace Home\Controller;

/**
 * 附近团购
 * Class AroundController
 * @package Home\Controller
 */
class AroundController extends CommonController {

    /**
     * 附近团购首页
     */
    public function index() {
        $this->_getWebTitle(array('title' => '附近团购'));
        $this->display();
    }

    /**
     * 获取附近团购数据
     */
    public function position() {
        $Model       = D('Team');
        $postAddress = I('post.address', '', 'strval');
        $position    = I('post.position', '', 'strval');
        $gid         = I('get.gid', 0, 'intval');
        $sid         = I('get.sid', 0, 'intval');
        if ($position) {
            $address       = $postAddress;
            $lng           = I('post.lng');
            $lat           = I('post.lat');
            $cookieAddress = $address . ',' . $lng . ',' . $lat;
            cookie("saveAddress", $cookieAddress, time() + 3600 * 24 * 30);
            $this->assign('postaddress', $postAddress);
        }
        //获取一级分类
        $groups = $this->_getCategoryList('group', array('fid' => 0));
        if ($lng && $lat) {
            $range   = $Model->getMysqlDistanceField($lat, $lng, 'p');
            $squares = $Model->returnSquarePoint($lng, $lat, 2);
        } else {
            $saveAddress = explode(',', cookie('saveAddress'));
            $range       = $Model->getMysqlDistanceField($saveAddress[2], $saveAddress[1], 'p');
            $squares     = $Model->returnSquarePoint($saveAddress[1], $saveAddress[2], 2);
            $this->assign('postaddress', $saveAddress[0]);
        }
        $field        = 't.id,t.title,t.promotion,t.image,t.product,t.team_price,t.market_price,t.now_number,t.view_count,t.partner_id,t.max_number,t.begin_time,' . $range . ' as `range`';
        $team_where   = $this->_getTeamWhere('', 't');
        $team_where[] = array(
            "p.lat<>0",
            "p.lat>'" . $squares['right-bottom']['lat']."'",
            "p.lat<'" . $squares['left-top']['lat']."'",
            "p.`long`>'" . $squares['left-top']['lng']."'",
            "p.`long`<'" . $squares['right-bottom']['lng']."'"
        );
        if ($gid) {
            $team_where['t.group_id'] = $gid;
            $params['gid'] = $gid;
        }
        if ($sid){
            $team_where['t.sub_id'] = $sid;
            $params['sid'] = $sid;
        }
        $order     = '`range` asc ,t.sort_order desc';
        $teamTotal = $Model->getAroundCount($team_where);
        $page      = $this->pages($teamTotal, $this->reqnum);
        $page->setConfig('theme', "%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE%");
        $limit     = $page->firstRow . ',' . $page->listRows;
        $data      = $Model->getAroundList($team_where, $order, $limit, $field);
        if ($gid) {
            $where['group_id'] = array('eq', $gid);
            $params['gid']     = $gid;
            $this->assign('gname', M('category')->where(array('id' => $gid))->getField('name'));
            $subs = $this->_getCategoryList('group', array('fid' => $gid));
            $this->assign('subs', $subs);
        }
        // 二级分类参数
        if ($sid && $gid) {
            $where['sub'] = array('eq', $sid);
            $this->assign('sname', M('category')->where(array('id' => $sid))->getField('name'));
            $params['sid'] = $sid;
        }
        // 参数赋值
        $this->assign('searchParams', $params);
        $this->assign('teams', $data);
        $this->assign('groups', $groups);
        $this->assign('page', $page->show());
        $this->_getWebTitle(array('title' => '附近团购列表'));
        $this->display();
    }
}