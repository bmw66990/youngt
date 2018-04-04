<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/5/6
 * Time: 10:20
 */

namespace Home\Controller;

/**
 * 团单搜索
 * Class CategoryController
 * @package Home\Controller
 */
class CategoryController extends CommonController {

    /**
     * 当前城市信息
     * @var array
     */
    private $city = array();

    /**
     * @var array
     */
    private $params = array();

    /**
     * 价格搜索配置
     * @var array
     */
    private $search_price = array(
        array('min_price' => '-1', 'max_price' => '50', 'title' => '50元以下'),
        array('min_price' => '50', 'max_price' => '100', 'title' => '50-100元'),
        array('min_price' => '100', 'max_price' => '300', 'title' => '100-300元'),
        array('min_price' => '300', 'max_price' => '500', 'title' => '300-500元'),
        array('min_price' => '500', 'max_price' => '-1', 'title' => '500元以上')
    );

    /**
     * 构造方法
     */
    public function __construct() {
        parent:: __construct();
        //获取当前城市信息
        $this->city = $this->_getCityInfo();

        // 获取一级分类
        $groups = $this->_getCategoryList('group', array('fid' => 0));
        //过滤邮购分类
        /*  foreach ($groups as $key => $val) {
          if ($val['id'] == 16) {
          unset($groups[$key]);
          }
          } */
        $this->assign('groups', $groups);

        //获取当前城市二级分类
        $area = $this->_getCategoryList('district', array('fid' => $this->city['id']));
        $this->assign('areas', $area);
        //价格参数分配
        $this->assign('prices', $this->search_price);
    }

    /**
     * 获取搜索条件
     */
    protected function _createTeamWhere($today = false) {
        $Category = M('category');
        $condition = $this->_getTeamWhere($this->city['id'], '', $today);
        $key = urldecode(sqlReplace(I('get.key', '', 'strval')));
        if ($key) {
            $condition['title|product|sel1|sel2|sel3'] = array('LIKE', '%' . $key . '%');
            $this->params['key'] = $key;
        }
        $group_id = I('get.gid', 0, 'intval');
        if ($group_id) {
            if ($group_id == 16) {
                unset($condition['city_id']);
            }
            $condition['group_id'] = $group_id;
            $this->params['gid'] = $group_id;
            /** 百分点数据 */
            $groups = $this->_getCategoryList('group');
            $this->assign('group', $groups[$group_id]);
            $this->assign('subs', $this->_getCategoryList('group', array('fid' => $group_id)));
        }
        // 二级分类参数
        $sub_id = I('get.sid', 0, 'intval');
        if ($sub_id && $group_id) {
            $condition['sub_id'] = $sub_id;
            $this->params['sid'] = $sub_id;
            /** 百分点数据 */
            $subs = $this->_getCategoryList('group');
            $this->assign('sub', $Category->find($sub_id));
        }
        // 区域参数
        $zone_id = I('get.zid', 0, 'intval');
        if ($zone_id) {
            $this->params['zid'] = $zone_id;
            $this->assign('station', $this->_getCategoryList('station', array('fid' => $zone_id)));
        }
        // 站点参数
        $station_id = I('get.station_id', 0, 'intval');
        if ($zone_id && $station_id) {
            $this->params['station_id'] = $station_id;
        }

        //商家信息搜索
        if ($zone_id || $station_id) {
            $partner_ids = $this->_createPartnerWhere($zone_id, $station_id);
            if ($partner_ids) {
                $condition['partner_id'] = array('in', $partner_ids);
            } else {
                return false;
            }
        }
        // 价格左参数
        $min_price = I('get.lp', 0, 'intval');
        // 价格右参数
        $max_price = I('get.rp', 0, 'intval');
        if ($min_price && $max_price) {
            if ($min_price == '-1') {
                $condition['team_price'] = array('elt', $max_price);
            } else if ($max_price == '-1') {
                $condition['team_price'] = array('egt', $min_price);
            } else {
                $condition['team_price'] = array('between', array($min_price, $max_price));
            }
            $this->params['lp'] = $min_price;
            $this->params['rp'] = $max_price;
            $this->assign('lp', $min_price);
            $this->assign('rp', $max_price);
        }
        //返回查询条件
        return $condition;
    }

    /**
     * 获取搜索服务条件
     * @param bool $today 是否是今日新单
     */
    protected function _createSearchWhere($today = false) {
        $nowTime = time();
        $group_id = I('get.gid', 0, 'intval');
        $query = "(team_type:'normal' OR team_type:'goods')";
        if ($today === false) {
            $where = "(city_id = {$this->city['id']} OR city_id = 957) AND  begin_time <= '{$nowTime}' AND end_time > '{$nowTime}' ";
        } else {
            $start_time = strtotime(date('Y-m-d', strtotime("-7 days")));
            $where = "(city_id = {$this->city['id']} OR city_id = 957) AND team_type = 'normal' AND begin_time < '{$nowTime}' AND begin_time >= '{$start_time}' AND team_price >= 0";
        }
        $key = urldecode(sqlReplace(I('get.key', '', 'strval')));
        if ($key) {
            $query .= "AND title:'{$key}' OR product:'{$key}' OR sel1:'{$key}' OR　sel2:'{$key}' OR sel3:'{$key}'";
            $this->params['key'] = $key;
        }
        if ($group_id) {
            $where .= "AND group_id = $group_id ";
            $this->params['gid'] = $group_id;
        }
        // 二级分类参数
        $sub_id = I('get.sid', 0, 'intval');
        if ($sub_id && $group_id) {
            $where .= "AND sub_id = $sub_id ";
            $this->params['sid'] = $sub_id;
        }
        // 区域参数
        $zone_id = I('get.zid', 0, 'intval');

        // 站点参数
        $station_id = I('get.station_id', 0, 'intval');

        //商家信息搜索
        if ($zone_id || $station_id) {
            $partner_ids = $this->_createPartnerWhere($zone_id, $station_id);
            if ($partner_ids) {
                $tmp_where = '';
                foreach (explode(',', $partner_ids) as $val) {
                    $tmp_where .= "partner_id = $val OR ";
                }
                $tmp_where = substr($tmp_where, 0, -2);
                $where = "$where AND ($tmp_where) ";
            } else {
                return false;
            }
            $this->params['zid'] = $zone_id;
            $this->params['station_id'] = $station_id;
        }
        // 价格左参数
        $min_price = I('get.lp', 0, 'intval');
        // 价格右参数
        $max_price = I('get.rp', 0, 'intval');
        if ($min_price && $max_price) {
            if ($min_price == '-1') {
                $where .= "AND team_price <= $max_price ";
            } else if ($max_price == '-1') {
                $where .= "AND team_price >= $min_price ";
            } else {
                $where .= "AND team_price >= $min_price AND team_price <= $max_price";
            }
            $this->params['lp'] = $min_price;
            $this->params['rp'] = $max_price;
        }
        return array($where, $query);
    }

    /**
     * @param $zid
     * @param $sid
     * @return string|void
     */
    protected function _createPartnerWhere($zid, $sid) {
        $partner_ids = '';
        if ($zid) {
            $where['city_id'] = $this->city['id'];
            $where['zone_id'] = $zid;
            if ($sid)
                $where['station_id'] = $sid;
            //获取符合条件的商家 id
            $partner = D('Partner')->getList($where, '', '', 'id');
            if ($partner) {
                foreach ($partner as $val) {
                    $partner_ids .= $val['id'] . ',';
                }
                return substr($partner_ids, 0, -1);
            } else {
                return $partner_ids;
            }
        } else {
            return $partner_ids;
        }
    }

    protected function _createTeamSort() {
        //初始化排序参数
        $sort = array('num' => 'desc', 'price' => 'asc', 'vcnt' => 'desc', 'tvcnt' => 'desc', 'time' => 'desc');
        $odr = I('get.odr', '', 'strval');
        $srt = I('get.srt', '', 'strval');
        if (array_key_exists($odr, $sort)) {
            if ($srt == 'desc' || $srt == 'asc') {
                switch ($odr) {
                    case 'num':
                        $sort['num'] = $srt == 'desc' ? 'asc' : 'desc';
                        $order = 'now_number ' . $srt;
                        break;
                    case 'price':
                        $sort['price'] = $srt == 'desc' ? 'asc' : 'desc';
                        $order = 'team_price ' . $srt;
                        break;
                    case 'vcnt':
                        $sort['vcnt'] = $srt == 'desc' ? 'asc' : 'desc';
                        $order = 'view_count_day ' . $srt;
                        break;
                    case 'tvcnt':
                        $sort['tvcnt'] = $srt == 'desc' ? 'asc' : 'desc';
                        $order = 'view_count ' . $srt;
                        break;
                    case 'time':
                        $sort['time'] = $srt == 'desc' ? 'asc' : 'desc';
                        $order = 'create_time ' . $srt;
                        break;
                    default:
                        $order = 'sort_order DESC, begin_time DESC';
                        break;
                }
                $this->params['odr'] = $odr;
                $this->params['srt'] = $srt;
            }
        }
        $this->assign('sort', $sort);
        return $order ? $order : 'sort_order DESC, begin_time DESC';
    }

    /**
     * 获取排序参数
     */
    protected function _createSearchSort() {
        //初始化排序参数
        $sort = array('num' => 'desc', 'price' => 'asc', 'vcnt' => 'desc', 'tvcnt' => 'desc', 'time' => 'desc');
        $odr = I('get.odr', '', 'strval');
        $srt = I('get.srt', '', 'strval');
        if (array_key_exists($odr, $sort)) {
            if ($srt == 'desc' || $srt == 'asc') {
                switch ($odr) {
                    case 'num':
                        $sort['num'] = $srt == 'desc' ? 'asc' : 'desc';
                        $order['now_number'] = $srt == 'desc' ? '-' : '+';
                        break;
                    case 'price':
                        $sort['price'] = $srt == 'desc' ? 'asc' : 'desc';
                        $order['team_price'] = $srt == 'desc' ? '-' : '+';
                        break;
                    case 'vcnt':
                        $sort['vcnt'] = $srt == 'desc' ? 'asc' : 'desc';
                        $order['view_count_day'] = $srt == 'desc' ? '-' : '+';
                        break;
                    case 'tvcnt':
                        $sort['tvcnt'] = $srt == 'desc' ? 'asc' : 'desc';
                        $order['view_count'] = $srt == 'desc' ? '-' : '+';
                        break;
                    case 'time':
                        $sort['time'] = $srt == 'desc' ? 'asc' : 'desc';
                        $order['create_time'] = $srt == 'desc' ? '-' : '+';
                        break;
                    default:
                        $order['sort_order'] = $srt == 'desc' ? '-' : '+';
                        break;
                }
                $this->params['odr'] = $odr;
                $this->params['srt'] = $srt;
            }
        }
        $this->assign('sort', $sort);
        return $order;
    }

    /**
     * @param string $tpl 输出模板
     */
    protected function _getTeamList($tpl = 'index', $today = false) {
        //获取搜索服务查询条件
        $search_data = $this->_createSearchWhere($today);
        @list($search_where, $search_query) = $search_data;
        if ($search_where === false) {
            $this->display($tpl);
            exit;
        }

        $search_count = $this->_SearchCount($search_query, $search_where);
        $field = 'id,title,image,product,team_price,market_price,now_number,view_count,partner_id,max_number,promotion,begin_time';
        $page = $this->pages($search_count, $this->reqnum);
        $page->setConfig('theme', "%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE%");
        $search_orderBy = $this->_createSearchSort();
        $data = $this->_Search($search_query, $search_where, $search_orderBy, $page->firstRow, $page->listRows);
        if ($data || is_array($data)) {
            $fieldArr = explode(',', $field);
            foreach ($data as &$val) {
                foreach ($val as $keys => $vals) {
                    if (!in_array($keys, $fieldArr)) {
                        unset($val[$keys]);
                    }
                }
            }
        } else {
            $team_where = $this->_createTeamWhere($today);
            $team_count = D('Team')->getTotal($team_where);
            $page = $this->pages($team_count, $this->reqnum);
            $page->setConfig('theme', "%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE%");
            $limit = $page->firstRow . ',' . $page->listRows;
            $team_orderBy = $this->_createTeamSort();
            $data = D('Team')->getList($team_where, $team_orderBy, $limit, $field);
        }
        if ($data) {
            foreach ($data as &$team) {
                $team['image'] = getImagePath($team['image']);
                $promotion = unserialize($team['promotion']);
                $team['not_time'] = 0;
                $team['all_type'] = 0;
                $team['today'] = 0;
                foreach ($promotion as $pro) {
                    if (strtolower($pro) == 'm') {
                        $team['not_time'] = 1;
                    } else if (strtolower($pro) == 'd') {
                        $team['all_type'] = 1;
                    }
                }
                if (date('Y-m-d', $team['begin_time']) == date('Y-m-d')) {
                    $team['today'] = 1;
                }
            }
        }
        $cate_gory_res = $this->_getCategoryList('group');
        if (isset($this->params['gid']) && trim($this->params['gid'])) {
            $gid_name = ternary($cate_gory_res[$this->params['gid']]['name'], '');
            $this->assign('gid_name', $gid_name);
        }
        if (isset($this->params['sid']) && trim($this->params['sid'])) {
            $sid_name = ternary($cate_gory_res[$this->params['sid']]['name'], '');
            $this->assign('sid_name', $sid_name);
        }
        $this->assign('count', $team_count);
        $this->assign('searchParams', $this->params);
        $this->assign('page', $page->show());
        $this->assign('list', $data);
    }

    /**
     * 团单搜索首页
     */
    public function index() {
        $this->_getTeamList();
        $this->_getWebTitle();
        $this->display();
    }

    /**
     * 推荐新单
     */
    public function today() {
        $this->_getTeamList('today', true);
        $this->_getWebTitle(array('title' => '推荐新单'));
        $this->display();
    }

    /**
     * 关键字搜索
     */
    public function search() {
        $this->_getTeamList('search', false);
        $this->_getWebTitle(array('title' => urldecode(strip_tags(I('get.key', '搜索', 'strval')))));
        $this->display();
    }
    
    /**
     * 一元云购 入口
     */
    public function cloud_shoping_team_list() {
        $type = I('get.type', 'hot', 'trim');

        $now_time = time();
        $res = array();
        $page_html = '';
        $count = 0;
        if ($type && $type == 'cloud_result') {
            $where = array(
                'team.team_type' => 'cloud_shopping',
                'cloud_shoping_result.status' => array('gt', 0),
            );
            $count = M('team')->where($where)->join('inner join cloud_shoping_result on cloud_shoping_result.team_id=team.id')->count();
            $page = $this->pages($count, $this->reqnum);
            $page->setConfig('theme', "%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE%");
            $limit = $page->firstRow . ',' . $page->listRows;
            $page_html = $page->show();
            $field = array(
                'team.id' => 'id',
                'team.image' => 'image',
                'team.product' => 'product',
                'team.title' => 'title',
                'cloud_shoping_result.max_number' => 'csr_max_number',
                'cloud_shoping_result.periods_number' => 'csr_periods_number',
                'cloud_shoping_result.winning_cloud_code' => 'csr_winning_cloud_code',
                'cloud_shoping_result.winning_user_id' => 'csr_winning_user_id',
                'cloud_shoping_result.begin_time' => 'csr_begin_time',
            );
            $res = M('team')->where($where)->limit($limit)->order('cloud_shoping_result.begin_time desc')->join('inner join cloud_shoping_result on cloud_shoping_result.team_id=team.id')->field($field)->select();
        } else {
            $where = array(
                'team_type' => 'cloud_shopping',
                'begin_time' => array('lt', $now_time),
                '_string' => "now_periods_number<=max_periods_number and now_number<max_number",
            );
            $count = M('team')->where($where)->count();
            $page = $this->pages($count, $this->reqnum);
            $page->setConfig('theme', "%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE%");
            $limit = $page->firstRow . ',' . $page->listRows;
            $page_html = $page->show();
            $field = array(
                'id',
                'image',
                'product',
                'title',
                'now_periods_number',
                'max_periods_number',
                'now_number',
                'max_number',
            );
            $res = M('team')->where($where)->limit($limit)->field($field)->order('id desc')->select();
        }

        if ($res) {
            $team_ids = $pn_s = $user_ids = array();
            foreach ($res as &$v) {
                if (isset($v['image']) && trim($v['image'])) {
                    $v['image'] = getImagePath($v['image']);
                }
                if (isset($v['csr_winning_user_id']) && trim($v['csr_winning_user_id'])) {
                    $user_ids[$v['csr_winning_user_id']] = $v['csr_winning_user_id'];
                }
                if (isset($v['id']) && trim($v['id'])) {
                    $team_ids[$v['id']] = $v['id'];
                }
                if (isset($v['csr_periods_number']) && trim($v['csr_periods_number'])) {
                    $pn_s[$v['csr_periods_number']] = $v['csr_periods_number'];
                }
                $v['progress'] = 0;
                if (isset($v['now_number']) && trim($v['now_number']) && isset($v['max_number']) && $v['max_number'] > 0) {
                    $v['progress'] = ($v['now_number'] / $v['max_number']) * 100;
                }
            }
            unset($v);
            if ($type && $type == 'cloud_result') {
                $user_info_res = array();
                if ($user_ids) {
                    $user_info_res = M('user')->where(array('id' => array('in', array_keys($user_ids))))->getField('id,username', true);
                }

                $pay_count_info = array();
                if ($user_ids && $team_ids && $pn_s) {
                    $pay_count_where = array(
                        'team_id' => array('in', array_keys($team_ids)),
                        'periods_number' => array('in', array_keys($pn_s)),
                        'user_id' => array('in', array_keys($user_ids)),
                    );
                    $cloud_shoping_code = M('cloud_shoping_code');
                    $pay_count_res = $cloud_shoping_code->where($pay_count_where)->field('user_id,team_id,periods_number,count(id) as pay_count')->group('user_id,team_id,periods_number')->select();
                    if ($pay_count_res) {
                        foreach ($pay_count_res as &$v) {
                            $_key = "{$v['user_id']}_{$v['team_id']}_{$v['periods_number']}";
                            if (!isset($pay_count_info[$_key])) {
                                $pay_count_info[$_key] = 0;
                            }

                            if (isset($v['pay_count']) && trim($v['pay_count'])) {
                                $pay_count_info[$_key] = $v['pay_count'];
                            }
                        }
                        unset($v);
                    }
                }
                foreach ($res as &$v) {
                    $v['csr_winning_user_username'] = '';
                    if (isset($v['csr_winning_user_id']) && trim($v['csr_winning_user_id'])) {
                        $v['csr_winning_user_username'] = ternary($user_info_res[$v['csr_winning_user_id']], '');
                    }
                    $v['csr_winning_user_username_hide'] = '';
                    if (checkMobile($v['csr_winning_user_username'])) {
                        $v['csr_winning_user_username_hide'] = substr($v['csr_winning_user_username'], 0, 4) . '****' . substr($v['csr_winning_user_username'], -4, 4);
                    } else {
                        $v['csr_winning_user_username_hide'] = cutStr($v['csr_winning_user_username'], 1, 0, 0) . '**';
                    }
                    
                    $_key = "{$v['csr_winning_user_id']}_{$v['id']}_{$v['csr_periods_number']}";
                    $v['pay_count'] = ternary($pay_count_info[$_key], '0');
                }
                unset($v);
            }
        }

        $data = array(
            'type' => $type,
            'count' => $count,
            'page' => $page_html,
            'list' => $res,
        );
        $this->assign($data);
        $this->_getWebTitle(array('title' => '一元众筹'));
        $this->display();
    }

}



