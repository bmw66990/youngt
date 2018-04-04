<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/6/12
 * Time: 9:56
 */

namespace Home\Controller;


class StudyController extends CommonController{
    private $group = array(
        'food'   => array(64428,65879,66081,74212,81580,81666,82398,85158,90653,90665,90710,91079,91093,91115,91246,91373,91374,91376,91382,91467,91482,91528,91556,91490),
        'hotpot' => array(91630,91627,61306,62657,66074,70760,76210,81092,84474,87905,88220,88226,91093,91528,91556),
        'buffet' => array(65207,92245,82229,92248,90223,90226,90616,90797,91471,88869),
        'play'   => array(29250,84328,90323,81203,90817,90818),
        'photo'  => array(91717, 91714, 91687, 91306, 91305),
    );
    public function index() {
        $city = $this->_getCityInfo();;
        if($city['id'] != 1){
            redirect(U('Index/index'));
        }
        $team_where = array('t.city_id' => $city['id'], 't.team_type' => 'normal', 't.begin_time' => array('elt', time()), 't.end_time' => array('gt', time()));
        $group      = $this->group;
        foreach ($group as $key => $val) {
            $team_where['t.id'] = array('in', $val);
            $team[$key]         = M('team')->alias('t')->join('left join partner p ON p.id = t.partner_id')->field('t.id,t.image,t.title,t.product,p.address,p.phone,t.team_price,t.market_price')->where($team_where)->select();
            foreach ($team[$key] as &$teamInfo) {
                $teamInfo['image'] = getImagePath($teamInfo['image']);
            }
        }
        $this->assign('team',$team);
        $this->display();
    }

}