<?php

namespace Wap\Controller;

class IndexController extends CommonController {

    private $city_id = 1;
    private $city_name = '杨凌';
    private $order_type = array(
        '-@sort_order' => '默认排序',
        '-@begin_time' => '最新发布',
        '-@view_count' => '人气最高',
        'team_price' => '价格最低',
        '-@team_price' => '价格最高',
        '-@now_number' => '销量最高',
    );
    private $category_type = array(
        array(
            'id' => '255',
            'type' => '255',
            'name' => '美食',
            'icon' => 'hotnav',
        ),
        array(
            'id' => '12',
            'type' => '12',
            'name' => '娱乐',
            'icon' => 'hotnav hotnav-yule',
        ),
        array(
            'id' => '420',
            'type' => '12@420',
            'name' => '电影',
            'icon' => 'hotnav hotnav-dy',
        ),
        array(
            'id' => '419',
            'type' => '419',
            'name' => '生活服务',
            'icon' => 'hotnav hotnav-shfw',
        ),
        array(
            'id' => '404',
            'type' => '404',
            'name' => '旅游酒店',
            'icon' => 'hotnav hotnav-lyjd',
        ),
        array(
            'id' => '14',
            'type' => '14',
            'name' => '美容保健',
            'icon' => 'hotnav hotnav-mrbj',
        ),
        array(
            'name' => '今日新单',
            'order' => '-@begin_time',
            'icon' => 'hotnav hotnav-jrxd',
        ),
        array(
            'id' => '16',
            'type' => '16',
            'name' => '精品网购',
            'icon' => 'hotnav hotnav-jpwg',
        ),
    );

    /**
     * 构造方法
     */
    public function __construct() {
        parent:: __construct();
        $this->city_id = $this->_getCityId();
        $city = $this->_getCityInfo();
        $this->city_name = ternary($city['name'], 0);
    }

    public function index() {

        $now_time = time();

        // 获取分类
        $cate_gory = M('app_category')->where(array('city_id' => $this->city_id))->find();
        if ($cate_gory) {
            $cate_gory = unserialize(ternary($cate_gory['cate']));
        }
        $category = array();
        if ($cate_gory) {
            foreach ($cate_gory as $v) {
                $key = ternary($v['id'], '');
                if (isset($v['fid']) && trim($v['fid'])) {
                    $key = "{$v['fid']}@{$key}";
                }
                $category[] = array(
                    'name' => ternary($v['name'], ''),
                    'type' => $key,
                    'image_path' => $this->_getImgUrl(ternary($v['id'], ''))
                );
            }
        } else {
            $category = $this->category_type;
            foreach ($category as $key => &$v) {
                if (isset($v['id']) && trim($v['id'])) {
                    $v['image_path'] = $this->_getImgUrl(ternary($v['id'], ''));
                }
            }
        }

        // 在search服务中搜索
        $query = "team_type:'normal' AND city_id:'{$this->city_id}'";
        $where = "begin_time<{$now_time} AND end_time>{$now_time}";
        $sort = array('sort_order' => '-', 'team_price' => '+');
        $res = $this->_Search($query, $where, $sort);
        if (!$res) {
            $where = array(
                'begin_time' => array('lt', $now_time),
                'end_time' => array('gt', $now_time),
                'team_type' => 'normal',
                'city_id' => $this->city_id,
            );
            $sort = array(
                'sort_order' => 'DESC',
                'team_price' => 'ASC',
            );
            $field = array(
                'id', 'image', 'product', 'title', 'team_price', 'market_price', 'now_number',
            );
            $res = M('team')->where($where)->field($field)->order($sort)->limit(20)->select();
        }

        // 整理数据
        foreach ($res as &$line) {
            if (strpos($line['team_price'], '.') !== false) {
                $line['team_price'] = $line['team_price'] > 0 ? rtrim(rtrim($line['team_price'], '0'), '.') : '0';
            }
            if (strpos($line['market_price'], '.') !== false) {
                $line['market_price'] = $line['market_price'] > 0 ? rtrim(rtrim($line['market_price'], '0'), '.') : '0';
            }
        }

        $data = array(
            'team' => $res,
            'category' => $category
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 搜索
     */
    public function search() {
        $this->display();
    }

    /**
     * 团单搜索列表
     */
    public function teamSearchList() {

        // 接收参数
        $data = array(
            'query' => I('get.query', '', 'strval'), // 搜索关键字
            'cityId' => I('get.city_id', $this->city_id, 'intval'), // 城市id
            'type' => I('get.type', '', 'strval'), // 团单类型 如果有子类型则 1@2
            'streetId' => I('get.streetId', '', 'strval'), // 如果街道有多层则 1@2
            'order' => I('get.order', '-@sort_order', 'strval'), // 排序字段 默认升序，升序： +@sort_order,降序-@sort_order
            'lastId' => I('get.lastId', ''),
            'end_id' => I('get.end_id', ''),
        );
        if (!isset($data['order']) || !trim($data['order'])) {
            $data['order'] = '-@sort_order';
        }
        $team = D('Team');
        $data['plat'] = 'wap';
     
        // 按照分类搜索
        $partnerRes = $team->getTeamSearchPartner($data, 5);

        // 整理数据
        $pdata = array();
        $category = $this->_getCategoryList('group');
        foreach ($partnerRes as $res) {
            $pdata[] = array(
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
            );
        }

        // 根据商户信息获取团单信息
        if ($pdata) {
            $nowTime = time();
            foreach ($pdata as $key => $v_data) {
                $partnerId = $v_data['partner_id'];
                $res = list($query, $where, $sort) = $team->getSearchWhere($data);
                $res = $this->_Search("$query AND partner_id:'$partnerId'", "begin_time<$nowTime AND end_time>$nowTime", $sort);
                if (!$res) {
                    // 去数据库查询
                    list($where, $sort) = $team->getMysqlWhere($data);
                    $where['partner_id'] = $partnerId;
                    $res = $team->samePartnerOtherTeam($where, $sort, 20);
                }
                if (!$res) {
                    unset($pdata[$key]);
                    continue;
                }

                // 整理数据
                $res = $team->dealTeamData($res, false, false);
                // 获取类型名称
                $team_end = end($res);
                if (isset($team_end['id']) && trim($team_end['id'])) {
                    $team_category = $team->where(array('id' => $team_end['id']))->field('group_id,sub_id')->find();
                    $v_data['cate_name'] = ternary($category[$team_category['group_id']]['name'], '');
                    if (trim($team_category['sub_id'])) {
                        $v_data['cate_name'] = $v_data['cate_name'] . '-' . ternary($category[$team_category['sub_id']]['name'], '');
                    }
                }

                $v_data['teamList'] = $res;
                $count = count($res) - 2;
                $v_data['count'] = $count > 0 ? $count : 0;
                $pdata[$key] = $v_data;
            }
        }
        // 获取分类
        $groups = array();
        $group_sort = array();
        if ($category) {
            foreach ($category as $key => $v) {
                if (!isset($v['fid']) || !trim($v['fid'])) {
                    if (!isset($groups[$key])) {
                        $groups[$key] = ternary($v, array());
                        $groups[$key]['subs'] = array();
                        $group_sort[] = ternary($v['sort_order'], 0);
                    }
                }
                if (isset($v['fid']) && trim($v['fid']) && isset($category[$v['fid']])) {
                    if (!isset($groups[$v['fid']])) {
                        $groups[$v['fid']] = ternary($category[$v['fid']], array());
                        $groups[$v['fid']]['subs'] = array();
                        $group_sort[] = ternary($category[$v['fid']]['sort_order'], 0);
                    }
                    array_push($groups[$v['fid']]['subs'], $v);
                }
            }
        }
        array_multisort($group_sort, SORT_DESC, $groups);

        // 获取区域
        $district = $this->_getCategoryList('district');


        // 类型默认值
        $default_value = array(
            'default_type' => array(
                'name' => '全部',
                'id' => $data['type']
            ),
            'default_zone' => array(
                'name' => $this->city_name,
                'id' => $data['streetId']
            ),
            'default_order' => array(
                'name' => '默认排序',
                'id' => $data['order']
        ));
        if (isset($data['type']) && trim($data['type'])) {
            if (isset($category[$data['type']]['name']) && trim($category[$data['type']]['name'])) {
                $default_value['default_type']['name'] = $category[$data['type']]['name'];
                $default_value['default_type']['type_one'] = $data['type'];
            }
            if (strpos($data['type'], '@') !== false) {
                list($g, $s) = @explode('@', $data['type'], 2);
                if ($g && isset($category[$g]['name']) && trim($category[$g]['name'])) {
                    $default_value['default_type']['name'] = $category[$g]['name'];
                    $default_value['default_type']['type_one'] = $g;
                }
                if ($s && isset($category[$s]['name']) && trim($category[$s]['name'])) {
                    $default_value['default_type']['name'] = $category[$s]['name'];
                    $default_value['default_type']['type_two'] = $s;
                }
            }
        }
        if (isset($data['streetId']) && trim($data['streetId'])) {
            $default_value['default_zone']['name'] = ternary($district[$this->city_id][$data['streetId']]['name'], $this->city_name);
        }
        if (isset($data['order']) && trim($data['order'])) {
            $default_value['default_order']['name'] = ternary($this->order_type[$data['order']], '默认排序');
        }

        // 获取最后一条数据
        $end_data = end($pdata);

        $_data = array(
            'default_value' => $default_value,
            'list' => $pdata,
            'groups' => $groups,
            'zones' => ternary($district[$this->city_id], array()),
            'order' => $this->order_type,
            'last_data' => array(
                'data_count' => count($pdata),
                'last_id' => ternary($end_data['sort'], ''),
                'end_id' => ternary($end_data['partner_id'], ''),
            )
        );
        $this->assign($_data);
        $this->display();
    }

    /**
     * 根据id获取分类图标
     * @param type $id
     * @return type
     */
    protected function _getImgUrl($id) {
        $url = "http://pic.youngt.com/static/cateimg";
        $url = "http://pic.youngt.com/static/cateimg1";
        switch ($id) {
            case 255:
                $img = "{$url}/meishi.png";
                break;
            case 1560:
                $img = "{$url}/daijingquan.png";
                break;
            case 12:
                $img = "{$url}/xiuxianyule.png";
                break;
            case 419:
                $img = "{$url}/shenghuofuw.png";
                break;
            case 14:
                $img = "{$url}/meirongbaojian.png";
                break;
            case 404:
                $img = "{$url}/lvyoujiudian.png";
                break;
            case 16:
                $img = "{$url}/jingpinwanggou.png";
                break;
            case 72:
                $img = "{$url}/qita.png";
                break;
            case 587:
                $img = "{$url}/zizhucan.png";
                break;
            case 589:
                $img = "{$url}/shaokao.png";
                break;
            case 418:
                $img = "{$url}/huoguo.png";
                break;
            case 256:
                $img = "{$url}/ktv.png";
                break;
            case 412:
                $img = "{$url}/sheying.png";
                break;
            case 417:
                $img = "{$url}/xican.png";
                break;
            case 608:
                $img = "{$url}/flower.png";
                break;
            case 420:
                $img = "{$url}/dianying.png";
                break;
            case 425:
                $img = "{$url}/zuyu.png";
                break;
            default :
                $img = "{$url}/qita.png";
                break;
        }
        return $img;
    }

    public function testShare(){
        $wxShare = new \Common\Org\wxShare();
        $data = $wxShare->getSignPackage();
        $this->assign('data',$data);
        $this->display();
    }

}
