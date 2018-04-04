<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-03-18
 * Time: 16:07
 */
namespace Common\Model;

/**
 * 订座模型
 * Class DingzuoModel
 * @package Common\Model
 */
class DingzuoModel extends CommonModel {

    /**
     * 代金券分类id
     */
    private $coupon_id = 1560;

    /**
     * 自动验证
     * @var array
     */
    protected $_validate = array(
        array('partner_id', 'require', '请选择商家'),
        array('partner_id', '', '该商家已开启订座',0,'unique',1),
        array('title', 'require', '商家名称必须填写'),
        array('city_id', 'require', '请选择城市'),
        array('mobile', 'require', '手机必须填写'),
        array('class_id', 'require', '分类必须选择'),
        array('min_num', 'require', '最低人数必须填写'),
        array('max_num', 'require', '最高人数必须填写'),
        array('begin_time', 'require', '开始时间必须填写'),
        array('end_time', 'require', '结束时间必须填写'),
    );

    /**
     * 获取订座列表信息
     * @param array $where : 条件
     * @param array $orderBy : 排序
     * @param array $limit : 分页
     * @return array|bool
     */
    public function getList($where, $orderby, $limit, $field,$having='') {
        if($having){
            $data = $this->field($field)->where($where)->having($having)->limit($limit)->order($orderby)->select();
        }else{
            $data = $this->field($field)->where($where)->limit($limit)->order($orderby)->select();
        }
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return $data;
        }
        if($data){
            $map = array(
                'group_id'   => $this->coupon_id,
                'city_id'    => $where['city_id'],
                'begin_time' => array('elt', time()),
                'end_time'   => array('egt', time())
            );
            foreach ($data as &$val) {
                $map['partner_id'] = $val['partner_id'];
                $count             = D('Team')->getTotal($map);
                $val['image']      = getSmallImage($val['image']);
                $val['is_coupon']  = $count ? Y : N;
                $val['cate_name']  = M('category')->getFieldById($val['class_id'], 'name');
                $comment_where     = array('is_comment' => 'Y', 'partner_id' => $val['partner_id'], 'commnet_display' => 'Y');
                $num               = M('comment')->field('sum(comment_num)/count(id) as comment_nums')->where($comment_where)->select();
                $val['num']        = number_format($num[0]['comment_nums'], 1);
            }
        }
        return $data;
    }

    /**
     * @param $id :订座ID号
     * return array|bool
     */
    public function getDetail($id) {
        $field = 'title,image,partner_id,id,mobile,long,lat';
        $data  = $this->info($id, $field);
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return $data;
        }
        $data['image']   = getSmallImage($data['image']);
        $address         = M('partner')->getFieldById($data['partner_id'], 'address');
        $data['address'] = $address;
        $map             = array(
            'partner_id' => $data['partner_id'],
            'begin_time' => array('elt', time()),
            'end_time'   => array('egt', time()),
            'team_type'  => 'normal'
        );
        $team_field      = 'id,image,product,team_price,market_price';
        $group           = array('coupon' => $this->coupon_id, 'team' => array('neq', $this->coupon_id));
        foreach ($group as $key => $val) {
            $map['group_id'] = $val;
            $team            = D('Team')->getList($map,'','', $team_field);
            if ($team) {
                foreach ($team as &$vals) {
                    $vals['image'] = getSmallImage($vals['image']);
                }
            } else {
                $team = array();
            }
            $data[$key] = $team;
        }
        $comment_where       = array('is_comment' => 'Y', 'partner_id' => $data['partner_id'], 'commnet_display' => 'Y');
        $num                 = M('comment')->field('sum(comment_num)/count(id) as comment_nums')->where($comment_where)->select();
        $data['num']         = number_format($num[0]['comment_nums'], 1);
        $data['comment_num'] = D('Comment')->getTotal($comment_where);
        return $data;
    }

    /**
     * 获取订座评论列表
     * @param $partner_id
     * @return mixed
     */
    public function getComment($partner_id, $limit) {
        $comment_field = 'user_id,create_time,content,comment_num,partner_content';
        $comment_where = array('is_comment' => 'Y', 'partner_id' => $partner_id, 'commnet_display' => 'Y');
        $orderby       = "id desc";
        $data          = D('Comment')->getList($comment_where, $orderby, $limit, $comment_field);
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return $data;
        }
        foreach ($data as &$val) {
            $val['username'] = M('user')->getFieldById($val['user_id'], 'username');
        }
        return $data;
    }

}