<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/10
 * Time: 17:22
 */

namespace Api\Controller;

/**
 * 订座相关接口
 */
class DingZuoController extends CommonController {
    /*
     * 获取订座首页数据
     */
    public function index() {
        $city_id = abs(I('get.city_id', 0, 'intval'));
        if (!$city_id) $this->outPut(null, 1001);
        $Model = D('Dingzuo');
        $where = array('city_id' => $city_id,'status'=>'Y');
        $count = $Model->getTotal($where);
        if ($count === false) {
            $this->_writeDBErrorLog($count, $Model, 'api');
            $this->outPut(null, 1002);
        }
        $zone     = 'class';
        $subClass = $this->_getCategoryList($zone);
        if ($subClass === false) $this->outPut(null, 1002);
        $data = array('count' => $count, 'subClass' => array_values($subClass), 'time' => time());
        $this->outPut($data, 0);
    }

    /*
     * 获取订座列表信息
     */
    public function getList() {
        //构造 where 条件
        $paramArray    = array(
            array('city_id'),
            array('week', '', 'like'),
            array('zone_id'),
            array('class_id'),
            array('station_id'),
            array('title', '', 'like'),
        );
        $map           = $this->createSearchWhere($paramArray);
        $num           = abs(I('get.num', 0, 'intval'));
        $time          = abs(I('get.time', 0, 'intval'));
        $order         = trim(I('get.orderby'));
        $long          = trim(I('get.long', 0, 'doubleval'));
        $lat           = trim(I('get.lat', 0, 'doubleval'));
        $map['status'] = 'Y'; //商家必须开启订座功能；
        if ($num) $map[] = "min_num<={$num} and max_num>={$num}";
        if ($time) $map[] = "begin_time <= {$time} and end_time >= {$time}";
        $whereArr = $this->setPage($order);
        $limit    = $this->reqnum;
        if($order == 'range'){
            $orderby = '`range` ASC, id DESC';
        }elseif($order == 'sort_order'){
            $orderby = 'sort_order DESC, id DESC';
        }else{
            $orderby = 'sort_order DESC, id DESC';
        }
        if (!trim($long) || !trim($lat)) {
            $this->outPut(null, -1, null, '用户当前地理位置不能为空！');
        }
        $Model = D('Dingzuo');
        $range = D('Team')->getMysqlDistanceField($lat, $long);
        $field = 'id,title,image,class_id,sort_order,partner_id,' . $range . ' as `range`';
        //距离排序特殊处理
        $having = '';
        if ($order == 'range') {
            $where  = $map;
            $having = $whereArr['_string'];
        }else{
            $where = array_merge($whereArr, $map);
        }
        $data = $Model->getList($where, $orderby, $limit,$field,$having);
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'api');
            $this->outPut(null, 1002);
        }
        $this->getHasNext($Model, 'id', $data, $whereArr);
        $this->outPut($data, 0);
    }

    /**
     * 获取订座详情
     */
    public function getDetail() {
        $id = abs(I('get.id', 0, 'intval'));
        if (!$id) $this->outPut(null, 1001);
        $Model = D('Dingzuo');
        $data  = $Model->getDetail($id);
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'api');
            $this->outPut(null, 1002);
        }
        $data['lng'] = ternary($data['long'],0);
        $this->outPut($data, 0);
    }

    /**
     * 获取订单评论
     */
    public function getComment() {
        $partner_id = I('get.partner_id', 0, 'intval');
        if (!$partner_id) $this->outPut(null, 1001);
        $Model    = D('Dingzuo');
        $whereArr = $this->setPage('id');
        $limit    = $this->reqnum;
        $data     = $Model->getComment($partner_id, $limit);
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'api');
            $this->outPut(null, 1002);
        }
        $this->getHasNext($Model, 'id', $data, $whereArr);
        $this->outPut($data, 0);
    }

    /**
     * 提交订座订单
     */
    public function buy() {
        $Model = D('DzOrder');
        $res   = $Model->insert();
        if ($res === false) {
            $this->_writeDBErrorLog($res, $Model, 'api');
            $this->outPut(null, -1, null, $Model->getError());
        }
        $data             = $Model->info($res);
        $dingzuo          = M('dingzuo')->getFieldById($data['dz_id'], 'id,title,telphone');
        $data['title']    = $dingzuo[$data['dz_id']]['title'];
        $data['telphone'] = $dingzuo[$data['dz_id']]['telphone'];
        $num              = I('post.num', 0, 'intval');
        $mobile           = I('post.mobile');
        $create_time      = I('post.create_time');
        $remarks          = I('post.remarks', '', 'trim');
        $sex              = I('post.sex', 1, 'intval');
        $username         = I('post.username', '', 'trim');
        if (checkMobile($data['telphone'])) {
            $beizhu   = $remarks ? "备注信息：{$remarks}" : '';
            $username = $sex == 0 ? "{$username}女士" : "{$username}先生";
            $text     = "尊敬的" . $data['title'] . "商户你好，青团用户：{$username}在青团预定你家{$num}人套餐请及时回复对接，订座人电话：{$mobile}，预定时间：{$create_time}，" . $beizhu;
            $this->_sms($data['telphone'], $text);
        }
        $this->outPut($data, 0);
    }

    /**
     * 获取订座订单
     */
    public function getOrder() {
        $mobile = I('get.mobile');
        if (!$mobile) $this->outPut(null, 1001);
        $Model              = D('DzOrder');
        $whereArr           = $this->setPage('id');
        $whereArr['mobile'] = $mobile;
        $limit              = $this->reqnum;
        $orderby            = $this->sort;
        $field              = 'id,dz_id,partner_id,num,create_time,username,mobile,remarks,sex,other_mobile,other_username';
        $data               = $Model->getList($whereArr, $orderby, $limit, $field);
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'api');
            $this->outPut(null, 1002);
        }
        foreach ($data as &$val) {
            $val['title']       = M('dingzuo')->getFieldById($val['dz_id'], 'title');
            $val['create_time'] = strtotime($val['create_time']);
            $val['time']        = time();
        }
        $this->getHasNext($Model, 'id', $data, $whereArr);
        $this->outPut($data, 0);
    }

    /**
     * 获取订座订座订单详情
     */
    public function getOrderDetail() {
        $id = I('get.id', 0, 'intval');
        if (!$id) $this->outPut(null, 1001);
        $field = 'id,dz_id,partner_id,num,create_time,username,mobile';
        $Model = D('DzOrder');
        $data  = $Model->info($id, $field);
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'api');
            $this->outPut(null, 1002);
        }
        $data['title'] = M('dingzuo')->getFieldById($data['dz_id'], 'title');
        $this->outPut($data, 0);
    }

    /**
     * 删除订座订单
     */
    public function delOrder() {
        $id = I('get.id', 0, 'intval');
        if (!$id) $this->outPut(null, 1001);
        $Model = D('DzOrder');
        $data  = $Model->delete($id);
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'api');
            $this->outPut(null, 1002);
        }
        $this->outPut($data, 0);
    }
    
    /**
     * 订座相关初始化信息获取
     */
    public function initInfo(){
         $this->_checkblank('city_id');
         $city_id = I('get.city_id','','trim');
         if(!$city_id){
            $this->outPut(null, 1001);
         }
         $dingzuo_is_show = 'N';
         $city_model_show = M('city_model_show');
         $where = array(
             'city_id'=>$city_id,
             'model_name'=>'dingzuo',
             'is_show'=>'Y',
         );
         $city_model_show_count = $city_model_show->where($where)->count();
         if($city_model_show_count && $city_model_show_count>0){
             $dingzuo_is_show='Y';
         }
         
         $this->outPut(array('dingzuo_is_show'=>$dingzuo_is_show), 0);
    }

}