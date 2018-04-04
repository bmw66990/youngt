<?php

namespace Api\Controller;

class TeamController extends CommonController {

    protected $checkUser = true;

    protected $limit_num = 21;

    const POPULAR_CATE_GORY_NUM = 8;
    
    /**
     * 搜索下拉提示
     */
    public function searchKeyDropTip(){
        $query=I('get.query','','trim');
        $limit=I('get.limit','10','trim');
        if(!$query){
            $this->outPut(array(), 0);
        }
        
        $data = $this->_searchKeyDropTip($query, $limit);
        if($data){
            $this->outPut($data, 0);
        }
        $this->outPut(array(), 0);
        
    }

    /**
     * 团单搜索
     */
    public function teamSearch() {

        // 接收参数
        $data = array(
            'pt_query' => I('get.pt_query', '', 'strval'), // 搜索关键字
            'query' => I('get.query', '', 'strval'), // 搜索关键字
            'cityId' => I('get.city_id', 0, 'intval'), // 城市id
            'type' => I('get.type', '', 'strval'), // 团单类型 如果有子类型则 1@2
            'streetId' => I('get.streetId', '', 'strval'), // 如果街道有多层则 1@2
            'lng' => I('get.lng', '', 'doubleval'),
            'lat' => I('get.lat', '', 'doubleval'),
            'distance' => I('get.distance', 0, 'doubleval'),
            'order' => I('get.order', '-@sort_order', 'strval'), // 排序字段 默认升序，升序： +@sort_order,降序-@sort_order
            'lastId' => I('get.lastId', ''),
            'end_id' => I('get.end_id', ''),
        );
        //2016.6.8屏蔽苹果错误
        if(trim($data['lng']) && trim($data['lng']) && !trim($data['distance']) && trim($data['type'])==0){
            $data['distance']=3;
        }
        //结束
        $cache_key = $this->_getCacheKey('teamSearch');
        $cache_data = $this->apiCache($cache_key);
        $hasnext = ternary($cache_data['hasnext'], '0');
        $pdata = ternary($cache_data['list'], '0');
        if (!$pdata && !is_array($pdata)) {

            $team = D('Team');
            // 如果query不为空 则以表单为主返回数据
            if (isset($data['query']) && trim($data['query'])) {
                list($query, $where, $sort) = $team->getSearchWhere($data);
                $res = $this->_Search($query, $where, $sort,0,$this->limit_num);
            
                if (!$res) {
                    // 从数据库搜索
                    $res = $team->teamSearch($data,$this->limit_num);
                }
                
                $this->setHasNext(false, $res, $this->limit_num - 1);

                // 整理数据
                $res = $team->dealTeamData($res);
                $this->outPut($res, 0);
            }

            $pdata = array();
            $limit_num = 9;
            $data['query'] = $data['pt_query'];
            if (isset($data['order']) && trim($data['order']) && strpos($data['order'], 'comment_avg_num') !== false) {

                // 按照分类搜索
                $pdata = $team->getTeamSearchPartnerOrderCommentNum($data, $limit_num);

                // 整理数据
                $category = $this->_getCategoryList('group');
                $team->dealPartnerData($pdata, $category);
            } else {

                // 按照分类搜索
                $pdata = $team->getTeamSearchPartner($data, $limit_num);
            }

            $hasnext = $this->setHasNext(false, $pdata, $limit_num - 1);

            // 处理数据
            $cache_data = array('hasnext' => $hasnext, 'list' => $pdata);
            if (!$pdata) {
                if (!trim($data['lng']) && !trim($data['lat'])) {
                    $this->apiCache($cache_key, $cache_data);
                }
                $this->outPut(array(), 0);
            }
            // 根据商户信息获取团单信息
            if ($pdata) {
                $nowTime = time();
                foreach ($pdata as $key => $v_data) {
                    $partnerId = $v_data['partner_id'];
                    list($query, $where, $sort) = $team->getSearchWhere($data);
                    $query=str_replace("team_type:'normal' OR team_type:'newuser'","team_type:'normal' OR team_type:'newuser' OR team_type='limited'  OR team_type='newguser'",$query);//2016.3.30 增加分类搜索里面可以显示限量团单
                   
                    $res = $this->_Search("$query AND partner_id:'$partnerId'", "begin_time<$nowTime AND end_time>$nowTime", $sort,0,50);

                    if (!$res) {
                        // 去数据库查询
                        list($where, $sort) = $team->getMysqlWhere($data);
                        $where=str_replace("team_type='normal' OR team_type='newuser'","team_type='normal' OR team_type='newuser' OR team_type='limited'  OR team_type='newguser'",$where);//2016.3.30 增加分类搜索里面可以显示限量团单
                        $where['partner_id'] = $partnerId;
                        $res = $team->samePartnerOtherTeam($where, $sort, 50);
                    }
                    if (!$res) {
                        unset($pdata[$key]);
                        continue;
                    }
                    $res = $team->dealTeamData($res, false, false);
                    $v_data['teamList'] = $res;
                    $pdata[$key] = $v_data;
                }
            }
            if (!trim($data['lng']) && !trim($data['lat'])) {
                $cache_data = array('hasnext' => $hasnext, 'list' => $pdata);
                $this->apiCache($cache_key, $cache_data);
            }
        }

        $this->setHasNext($hasnext);

        $this->outPut(array_values($pdata), 0);
    }

    /**
     * 附近团单
     */
    public function nearByTeam() {

        // 接收参数
        $city_id = I('get.city_id', 0, 'intval');
        $lastId = I('get.lastId', 0, 'doubleval');
        $lng = I('get.lng', 0, 'doubleval');
        $lat = I('get.lat', 0, 'doubleval');
        $distance = I('get.distance', 1, 'doubleval');
        $end_id = I('get.end_id', '', 'strval');

        if (!trim($lng) || !trim($lat)) {
            $this->outPut(null, -1, null, '定位失败，请开启定位权限！');
        }

        // 根据经纬度获取商户值
        $team = D('Team');
        $limit_num = 6;
        $data = $team->nearByTeam($city_id, $lng, $lat, $distance, $lastId, $end_id, $limit_num);
        $hasnext = $this->setHasNext(false, $data, $limit_num - 1);
        // 处理数据
        if (!$data) {
            $this->outPut(array(), 0);
        }

        // 根据商户信息获取团单信息
        if ($data) {
            $nowTime = time();
            foreach ($data as $key => $v_data) {
                $partnerId = $v_data['partner_id'];
                list($query, $where, $sort) = $team->getSearchWhere(array());
                $res = $this->_Search("$query AND partner_id:'$partnerId'", "begin_time<$nowTime AND end_time>$nowTime", array('id' => '+'));
                if (!$res) {
                    // 去数据库查询
                    list($where, $sort) = $team->getMysqlWhere(array());
                    $where['partner_id'] = $partnerId;
                    $res = $team->samePartnerOtherTeam($where, '', 20);
                }
                if (!$res || isset($res['error'])) {
                    unset($data[$key]);
                    continue;
                }
                $res = $team->dealTeamData($res, false, false);
                $v_data['teamList'] = $res;
                $data[$key] = $v_data;
            }
        }

        $this->outPut(array_values($data), 0);
    }

    /**
     * 今日推荐团单
     */
    public function todayRecommendTeam() {

        // 参数处理
        $lastId = I('get.lastId', '');
        $order = I('get.order', 'sort_order', 'strval');
        $cityId = I('get.cityId', 1, 'intval');
        $streetId = I('get.streetId', '', 'strval'); // 如果街道有多层则 1@2
        $end_id = I('get.end_id', '', 'strval'); //
        // 经纬度
        $lng = I('get.lng', '', 'trim');
        $lat = I('get.lat', '', 'trim');

        $start_time = strtotime('-7 day ' . date('Y-m-d'));
        $now_time = time();
        $team = D('Team');

        // 根据街道id 获取商户id
        $partnerIds = $team->getPartnerByWhere(array('streetId' => $streetId));

        // 从搜索服务中获取 begin_time>=$start_time  AND 
        $where = "end_time>$now_time AND begin_time<$now_time";
        $query = "(team_type:'normal' OR team_type:'newuser' OR team_type:'limited' OR team_type:'newguser')";//2016.3.30增加前端显示限量活动单子
        if (trim($lastId) != '') {
            $orderWhere = $team->getSearchSortWhere('begin_time', $lastId, $end_id, '-');
            if (trim($order) == 'sort_order') {
                $orderWhere = $team->getSearchSortWhere('sort_order', $lastId, $end_id, '-');
            }
            $where = "$where AND $orderWhere";
        }

//        if (trim($order) == 'begin_time') {
//            // $where = "$where AND begin_time>=$start_time";
//        }
        // 城市id
        if (trim($cityId)) {
            $query = "$query AND city_id:'$cityId'";
        }
        // 商户id过滤
        if ($partnerIds) {
            $partneridsWhere = '';
            $count = count($partnerIds);
            foreach ($partnerIds as $k => $v) {
                if ($k + 1 == $count) {
                    $partneridsWhere.="partner_id:'$v'";
                    continue;
                }
                $partneridsWhere.="partner_id:'$v' OR ";
            }
            $query = "$query AND ($partneridsWhere)";
        }

        $sort = array($order => '-', 'id' => '-');
        $res = $this->_Search($query, $where, $sort, 0, 30);
        // 从数据库获取 
        if (!$res) {
            $where = array('end_time' => array('GT', $now_time), '_string' => "(team_type='normal' OR team_type='newuser' OR team_type='limited' OR team_type='newguser') AND begin_time<$now_time");
            if (trim($cityId)) {
                $where['city_id'] = $cityId;
            }
            if ($partnerIds) {
                $where['partner_id'] = array('in', $partnerIds);
            }
//            if (trim($order) == 'begin_time') {
//                $where['begin_time'] = array('EGT', $start_time);
//            }
            $res = $team->todayRecommendTeam($where, $lastId, $end_id, $order, 30);
        }

        // 处理数据
        $res = $team->dealTeamData($res);

        // 如果有经纬度，则返回距离
        if ($lng && $lat) {
            $team->getTeamDistance($res, array('lng' => $lng, 'lat' => $lat));
        }
        $this->outPut($res, 0);
    }

    /**
     * 秒杀团单
     */
    public function secondKillTeam() {

        // 参数处理
        $cityId = I('get.cityId', 0, 'intval');
        $lastId = I('get.lastid', 0, 'intval');

        // 从搜索服务中获取
        $team = D('Team');
        $now_time = time();
        $end = time();
        $where = "(end_time>$now_time)";
        $query = "team_type:'timelimit'";
        if (trim($lastId)) {
            $where .= "AND id > $lastId";
        }
        if (trim($cityId)) {
            $query .= "AND city_id:'$cityId'";
        }
        $sort = array('id' => '+');
        $res = $this->_Search($query, $where, $sort);
        // 从数据库获取
        if (!$res) {
            $where = array('team_type' => 'timelimit');
            if (trim($cityId)) {
                $where['city_id'] = $cityId;
            }
            if (trim($lastId)) {
                $where ['id'] = array('GT', $lastId);
            }
            $res = $team->secondKillTeam($where);
        }

        // 整理数据
        $res = $team->dealTeamData($res);

        $this->outPut($res, 0);
    }

    /**
     * 限量团单
     */
    public function limitTeam() {

        // 参数处理
        $lastid = I('get.lastid', 0, 'intval');
        $cityId = I('get.cityId', 0, 'intval');

        // 从搜索服务中获取
        $team = D('Team');
        $now_time = time();
        $where = "end_time>$now_time";
        $query = "team_type:'limited'";
        if (trim($lastid)) {
            $where .= "AND id > $lastid";
        }
        if (trim($cityId)) {
            $query .= "AND city_id:'$cityId'";
        }
        $sort = array('id' => '+');
        $res = $this->_Search($query, $where, $sort);

        // 从数据库获取
        if (!$res) {
            $where = array(
                'team_type' => 'limited',
                'end_time' => array('GT', $now_time),
            );
            if (trim($cityId)) {
                $where['city_id'] = $cityId;
            }
            if (trim($lastid)) {
                $where['id'] = array('GT', $lastid);
            }
            $res = $team->getTeamListByWhere($where);
        }

        // 整理数据
        $res = $team->dealTeamData($res);

        $this->outPut($res, 0);
    }

    /**
     * 根据类型获取团单类型
     */
    public function getTeamListByType() {
        $this->_checkblank(array('city_id', 'type'));
        $city_id = I('get.city_id', '', 'trim');
        $lastId = I('get.lastid', '', 'trim');
        $end_id = I('get.end_id', '', 'trim');
        $type = I('get.type', '', 'trim');
        $is_show = I('get.is_show', '0', 'trim');

        // 经纬度
        $lng = I('get.lng', '', 'trim');
        $lat = I('get.lat', '', 'trim');
        
        if(!$city_id){
            if($is_show){
                $this->outPut(array('is_show_model' => 'N'), 0);
            }
            $this->outPut(array(), 0);
        }

        // 从搜索服务中获取 begin_time>=$start_time  AND 
        $team = D('Team');
        $now_time = time();
        $where = "end_time>$now_time AND begin_time<$now_time";
        $query = "team_type:'$type'";
        if (trim($lastId) != '') {
            $orderWhere = $team->getSearchSortWhere('sort_order', $lastId, $end_id, '-');
            $where = "$where AND $orderWhere";
        }

        // 城市id
        if (trim($city_id)) {
            $query = "$query AND city_id:'$city_id'";
        }
        $sort = array('sort_order' => '-', 'id' => '-');
        $limit_num = $this->limit_num;
        $res = $this->_Search($query, $where, $sort, 0, $limit_num);
        if (!$res) {
            $where = array('end_time' => array('GT', $now_time), 'begin_time' => array('lT', $now_time), 'team_type' => $type);
            if (trim($city_id)) {
                $where['city_id'] = $city_id;
            }
            if (trim($lastId) != '') {
                $orderWhere = $team->getMysqlSortWhere('sort_order', $lastId, 'id', $end_id, '-');
                if ($orderWhere) {
                    $where['_string'] = $orderWhere;
                }
            }
            $res = $team->getTeamListByWhere($where, array('sort_order' => 'desc', 'id' => 'desc'), $limit_num);
        }
        $this->setHasNext(false, $res, $limit_num - 1);

        if ($is_show) {
            $is_show_model = 'N';
            if ($res) {
                $is_show_model = 'Y';
            }
            $this->outPut(array('is_show_model' => $is_show_model), 0);
        }

        $res = $team->dealTeamData($res);
        // 如果有经纬度，则返回距离
        if ($lng && $lat) {
            $team->getTeamDistance($res, array('lng' => $lng, 'lat' => $lat));
        }

        $this->outPut($res, 0);
    }

    /**
     * 获取活动分类
     */
    public function getActivityType() {
        $cityId = I('get.city_id', 0, 'intval');

        $where = array(
            'cityid' => array('IN', array('0', $cityId)),
            'type' => array('IN', array('timelimit', 'limited', 'special_selling'))
        );
        $adRes = M('admanage')->where($where)->select();
        $data = array();
        foreach ($adRes as $v) {
            if (isset($v['type'])) {
                $data[$v['type']] = array(
                    'name' => ternary($v['textarr'], ''),
                    'image' => isset($v['picarr']) ? getImagePath($v['picarr']) : '',
                    'type' => ternary($v['type'], ''),
                );
            }
        }

        // 过滤类型
        foreach ($data as $k => $v) {
            $res = $this->__getTeamListByType($cityId, $k);
            if (!$res) {
                unset($data[$k]);
            }
        }

        $this->outPut(array_values($data), 0);
    }

    /**
     * 获取特卖团单列表
     */
    public function getSpecialSellingTeamList() {
        $city_id = I('get.city_id', '', 'intval');
        $type = I('get.type', '', 'trim');
        $lastId = I('get.lastid', 0, 'intval');
        $now_time = time();

        $where_string = "(city_id=957 OR group_id=1618 OR team_type='limited' OR team_type='timelimit') AND end_time>={$now_time}";
        $where_city_id = "city_id=957";
        if ($city_id) {
            $where_city_id = "$where_city_id OR city_id={$city_id}";
        }
        $where = array(
            '_string' => "$where_string AND ($where_city_id)"
        );

        if ($lastId) {
            $where['id'] = array('lt', $lastId);
        }
        if ($type) {
            $where['group_id'] = $type;
            if (strpos($type, '@') !== false) {
                @list($groupId, $subId) = explode('@', $type);
                if (!trim($groupId)) {
                    $where['group_id'] = $subId;
                } else {
                    $where['group_id'] = $groupId;
                    trim($subId) && $where['sub_id'] = $subId;
                }
            }
        }
        $team = D('Team');
        $limit_num = $this->limit_num;
        $data = $team->getTeamListByWhere($where, 'id desc', $limit_num);
        $this->setHasNext(false, $data, $limit_num - 1);
        $data = $team->dealTeamData($data);
        $this->outPut($data, 0);
    }

    /***
     * 根据城市获取所有团单
     */
    public function getTeamAllcity() {
        $city_id = I('get.city_id', '', 'intval');
        $lastId = I('get.lastid', 0, 'intval');
        $end_id = I('get.end_id', '', 'strval'); //
        $now_time = time();

        // 从搜索服务中获取 begin_time>=$start_time  AND
        // $query = "(team_type:'normal' OR team_type:'newuser' OR team_type:'limited' OR team_type:'newguser')";
        $team = D('Team');
        $now_time = time();
        $where = "end_time>=$now_time AND begin_time<$now_time";
        $query = "team_type:'normal' or team_type:'limited' or team_type:'newuser' or team_type:'newguser'";
        if (trim($lastId) != '') {
            $orderWhere = $team->getSearchSortWhere('sort_order', $lastId, $end_id, '-');
            $where = "$where AND $orderWhere";
        }

        // 城市id
        if (trim($city_id)) {
            $query = "$query AND city_id:'$city_id'";
        }
        $sort = array('sort_order' => '-', 'id' => '-');
        $limit_num = $this->limit_num;
        $res = $this->_Search($query, $where, $sort, 0, $limit_num);
        if(!$res){
            $where = array(
                'team_type'=>array('in',array('normal','limited','newuser','newguser')),
                'end_time'=>array('egt',$now_time),
                'begin_time'=>array('lt',$now_time),
            );

            if($city_id){
                $where['city_id']=$city_id;
            }

            if ($lastId) {
                $_last_id = $team->where(array('id'=>$lastId))->getField('sort_order');
                $end_id = $lastId;
                $order_where = $team->getMysqlSortWhere('sort_order', $_last_id, 'id', $end_id, '-');
                if ($order_where) {
                    if (isset($where['_string']) && trim($where['_string'])) {
                        $order_where = "({$where['_string']}) and $order_where";
                    }
                    $where['_string'] = $order_where;
                }
            }
            $res = $team->getTeamListByWhere($where, 'sort_order desc,id desc', $limit_num);
        }

        $this->setHasNext(false, $res, $limit_num - 1);
        $res = $team->dealTeamData($res);
        $this->outPut($res, 0);
    }
    /**
     * 获取特卖团单列表[包含新版邮购]
     */
    public function getSSTeamListIncludeGoods() {
        $city_id = I('get.city_id', '', 'intval');
        $query = I('get.query', '', 'trim');
        $type = I('get.type', '', 'trim');
        $lastId = I('get.lastid', 0, 'intval');
        $now_time = time();
        
        $where = array(
            'team_type'=>array('in',array('goods','limited','timelimit')),
            'end_time'=>array('egt',$now_time),
            'city_id'=>957,
        );
        
        $team = D('Team');
        
        if($city_id){
            $where['city_id'] = array('in',array(957,$city_id));
        }
        

        if ($query){
            $res = list($where_string, $sort_) = $team->getMysqlWhere(array('query'=>$query));
            if(isset($where_string['_string']) && trim($where_string['_string'])){
                $where['_string'] = ternary($where_string['_string'], '');
            }
        }
        
        if ($lastId) {
            $_last_id = $team->where(array('id'=>$lastId))->getField('sort_order');
            $end_id = $lastId;
            $order_where = $team->getMysqlSortWhere('sort_order', $_last_id, 'id', $end_id, '-');
            if ($order_where) {
                if (isset($where['_string']) && trim($where['_string'])) {
                    $order_where = "({$where['_string']}) and $order_where";
                }
                $where['_string'] = $order_where;
            }
        }
        
        if ($type) {
            $where['group_id'] = $type;
            if (strpos($type, '@') !== false) {
                @list($groupId, $subId) = explode('@', $type);
                if (!trim($groupId)) {
                    $where['group_id'] = $subId;
                } else {
                    $where['group_id'] = $groupId;
                    trim($subId) && $where['sub_id'] = $subId;
                }
            }
        }
        
        $limit_num = $this->limit_num;
        $data = $team->getTeamListByWhere($where, 'sort_order desc,id desc', $limit_num);
        $this->setHasNext(false, $data, $limit_num - 1);
        $data = $team->dealTeamData($data);
        $this->outPut($data, 0);
    }

    /**
     * 获取热门分类
     */
    public function getPopularCateGory() {
        $category = $this->_getCategoryList('group');
        $data = array();
        foreach ($category as $v) {
            if (isset($v['hot_type']) && $v['hot_type'] == 'Y') {
                array_push($data, array(
                    'id' => ternary($v['id'], ''),
                    'name' => ternary($v['name'], ''),
                    'fid' => ternary($v['fid'], ''),
                ));
                $count = count($data);
                if ($count >= self::POPULAR_CATE_GORY_NUM) {
                    break;
                }
            }
        }
        $this->outPut($data, 0);
    }

    /**
     * 团单详情
     */
    public function teamDetail() {

        // 参数接收
        $this->_checkblank('teamId');
        $teamId = I('get.teamId', 0, 'intval');
        $lng = I('get.lng', '', 'trim');
        $lat = I('get.lat', '', 'trim');
        $uniq_identify = I('get.uniq_identify', '', 'strval');
        $token = I('get.token', '', 'string');
        if (!trim($token)) {
            $token = I('post.token', '', 'string');
        }
        if (trim($token)) {
            $this->check();
        }
        $cache_key = $this->_getCacheKey('teamDetail', array(), $teamId);
        $res = $this->apiCache($cache_key);
        $team = D('Team');
        if (!$res) {
            // 去ots搜索
            $tableName = 'team';
            $res = $this->_getRowDataToOTS($tableName, array('id' => $teamId));
            if (!$res) {
                $field = 'id,city_id,allowrefund,flv,team_type,notice,promotion,product,partner_id,detail,title,summary,team_price,market_price,ucaii_price,lottery_price,begin_time,end_time,expire_time,permin_number,min_number,max_number,now_number,image,per_number,is_optional_model,activities_id';
                $res = $team->info($teamId, $field);
            }

            if (!$res) {
                $this->_writeDBErrorLog($res, $team, 'api');
                $this->outPut(array(), 3001);
            }
            // 处理数据
            $res = $team->dealTeamData($res,$this->uid, true);
            $this->apiCache($cache_key, $res);
        }

        // 如果用户登录则返回是否收藏该团单
        if (isset($this->uid) && trim($this->uid)) {
            $uniq_identify = trim($this->uid);
            $is_collect = 'N';
            $collectRes = M('collect')->where(array('user_id' => $this->uid, 'team_id' => $teamId))->count('id');
            if ($collectRes && intval($collectRes) > 0) {
                $is_collect = 'Y';
            }
            $res['is_collect'] = $is_collect;
        }

        // 记录浏览数
        if (trim($uniq_identify) != '') {
            // 怀疑导致主库连接数满了 先注释几天看看
            // $this->_recordViewCount($teamId, 'android_ios_app', trim($uniq_identify));
        }
        // 如果有经纬度，则返回距离
        if ($lng && $lat) {
            $team->getTeamDistance($res, array('lng' => $lng, 'lat' => $lat), true);
        }

        //2016.11.08 加判断用户是不是新用户
        $order = M('order');
        $where = array(
            'user_id' => $this->uid,
            'team_id'=>$res['id'],
        );
        
        $where['_string'] = "(state='pay' or rstate<>'normal')";            
        $newUser = $order->where($where)->count();
        if ($newUser == 0 && isset($res['lottery_price']) && $res['lottery_price'] > 0) {
            $res['team_price']=$res['lottery_price'];
        }
        
        $this->outPut($res, 0);
    }

    /**
     * 同商家其他团单
     */
    public function samePartnerOtherTeam() {

        // 参数接收
        $this->_checkblank('partnerId');
        $partnerId = I('get.partnerId', 0, 'intval');
        $ignoreIds = I('get.ignoreIds', '', 'strval');
        $lastId = I('get.lastId', '', 'trim');
        $order = I('get.order', '-@begin_time', 'strval'); // order  升序 +@order 降序-@order

        if (trim($ignoreIds) && is_string($ignoreIds)) {
            $ignoreIds = @explode('@', $ignoreIds);
        }

        $cache_key = $this->_getCacheKey('samePartnerOtherTeam');
        $res = $this->apiCache($cache_key);
        if (!$res) {
            // 处理search忽略的id条件
            $query = "partner_id:'$partnerId'";
            if ($ignoreIds) {
                $ignoreIdsWhere = "";
                foreach ($ignoreIds as $v) {
                    if (!trim($v)) {
                        continue;
                    }
                    $ignoreIdsWhere .= " ANDNOT id:'$v'";
                }
                if (trim($ignoreIdsWhere)) {
                    $query = $query . $ignoreIdsWhere;
                }
            }

            $team = D('Team');
            $limit_num = 11;
            // 去search服务查询
            list($_query, $where, $sort) = $team->getSearchWhere(array('order' => $order, 'lastId' => $lastId));
            $_query="(team_type:'normal' OR team_type:'newuser' OR team_type:'newguser' OR team_type:'limited')";
            $res = $this->_Search("$query AND $_query", $where, $sort, 0, $limit_num);
            if (!$res) {
                // 去数据库查询
                list($where, $sort) = $team->getMysqlWhere(array('order' => $order, 'lastId' => $lastId));
                $where['partner_id'] = $partnerId;
                $where['id'] = array('not in', $ignoreIds);
                $res = $team->samePartnerOtherTeam($where, $sort, $limit_num);
            }

            $this->setHasNext(false, $res, $limit_num - 1);

            // 处理数据
            if (!$res) {
                $this->outPut(array(), 0);
            }

            // 整理数据
            $res = $team->dealTeamData($res, false, false);
            $this->apiCache($cache_key, $res);
        }

        $this->outPut($res, 0);
    }

    /**
     * 评论列表
     */
    public function commentList() {

        // 参数接收
        $teamId = I('get.teamId', 0, 'trim');
        $partner_id = I('get.partner_id', 0, 'trim');
        $lastId = I('get.lastId', 0, 'trim');

        $cache_key = $this->_getCacheKey('commentList');
        $cache_data = $this->apiCache($cache_key);
        $res = ternary($cache_data['list'], array());
        $hasnext = ternary($cache_data['hasnext'], '0');
        if (!$res) {
            if (!$partner_id) {
                if (!$teamId) {
                    $this->outPut(array(), 0);
                }
                $partner_id = M('team')->where(array('id' => $teamId))->getField('partner_id');
            }

            $team = D('Comment');
            $where = $this->setPage('id');
            $where['comment.partner_id'] = $partner_id;
            $where['comment.is_comment'] = 'Y';
            if (trim($lastId)) {
                $where['comment.id'] = array('LT', $lastId);
            }
            $limit_num = $this->limit_num;
            $res = $team->getCommentList($where, '', $limit_num);
            $hasnext = $this->setHasNext(false, $res['list'], $limit_num - 1);
            $cache_data = array('hasnext' => $hasnext, 'list' => $res);
            $this->apiCache($cache_key, $cache_data);
            if (!$res) {
                $this->outPut(array(), 0);
            }
        }
        $this->setHasNext($hasnext);
        $this->outPut($res, 0);
    }

    /**
     * 获取商家评论列表
     */
    public function getPartnerCommentList() {
        // 参数接收
        $partner_id = I('get.partner_id', 0, 'trim');
        $team_id = I('get.team_id', 0, 'trim');
        $limit = I('get.limit', 0, 'trim');
        $lastId = I('get.lastId', 0, 'trim');
        $end_id = I('get.end_id', 0, 'trim');

        if (!$team_id && !$partner_id) {
            $this->outPut(null, -1, null, '团单id和商家id不能同时为空！');
        }

        $cache_key = $this->_getCacheKey('getPartnerCommentList');
        $cache_data = $this->apiCache($cache_key);
        $res = ternary($cache_data['list'], array());
        $hasnext = ternary($cache_data['hasnext'], '0');
        if (!$res) {
            $team = D('Comment');
            $where = array(
                'comment.is_comment' => 'Y',
            );
            if ($team_id) {
                $where['comment.team_id'] = $team_id;
            }
            if ($partner_id) {
                $team_list_res = M('team')->where(array('partner_id' => $partner_id))->select();
                $team_ids = array();
                foreach ($team_list_res as $v) {
                    $team_ids[$v['id']] = $v['id'];
                }
                if (!$team_ids) {
                    $this->outPut(null, -1, null, '该商家没有评论！');
                }
                $where['comment.team_id'] = array('in', $team_ids);
            }

            $order = 'comment.comment_num desc,comment.create_time desc,comment.id desc';
            if (trim($lastId) && trim($end_id)) {
                $where_order = D('Team')->getMysqlSortWhere('comment.create_time', $lastId, 'comment.id', $end_id, '-');
                if ($where_order) {
                    $where['_string'] = $where_order;
                }
            }
            $limit_num = $this->limit_num;
            if (!$limit) {
                $limit = $limit_num;
            }
            $res = $team->getCommentList($where, $order, $limit);
            $hasnext = $this->setHasNext(false, $res['list'], $limit_num - 1);
            $cache_data = array('hasnext' => $hasnext, 'list' => $res);
            $this->apiCache($cache_key, $cache_data);
            if (!$res) {
                $this->outPut(array(), 0);
            }
        }

        $this->setHasNext($hasnext);
        $this->outPut($res, 0);
    }

    /**
     * 商家详情
     */
    public function partnerDetail() {

        // 参数接收
        $this->_checkblank('partnerId');
        $partnerId = I('get.partnerId', 0, 'intval');

        $cache_key = $this->_getCacheKey('partnerDetail', array(), $partnerId);
        $data = $this->apiCache($cache_key);
        if (!$data) {
            $team = D('Partner');
            $res = $team->getPartnerDetail($partnerId);
            if (!$res) {
                $this->outPut(null, 3002);
            }

            // 数据整理
            $data = array(
                'partner_id' => ternary($res['id'], ''),
                'group_id' => ternary($res['group_id'], ''),
                'part_title' => ternary($res['title'], ''),
                'images' => isset($res['image']) && trim($res['image']) ? getImagePath($res['image']) : '',
                'lng' => ternary($res['long'], ''),
                'lat' => ternary($res['lat'], ''),
                'username' => ternary($res['username'], ''),
                'distance' => ternary($res['distance'], ''),
                'cate_name' => ternary($res['catname'], ''),
                'mobile' => ternary($res['mobile'], ''),
                'phone' => ternary($res['phone'], ''),
                'address' => ternary($res['address'], ''),
            );
            $this->apiCache($cache_key, $data);
        }

        $this->outPut($data, 0);
    }

    /**
     * 团单购买
     */
    public function teamBuy() {

        $this->_checkblank(array('id', 'num', 'mobile', 'plat'));
        $id = I('post.id', 0, 'intval'); // 商品id
        $num = I('post.num', 1, 'intval'); // 购买商品的数量
        $mobile = I('post.mobile', '', 'strval'); // 电话
        $plat = I('post.plat', '', 'strval'); // 平台
        $ver = I('post.ver', '', 'strval'); // 平台号
        $uniq_identify = I('post.uniq_identify', '', 'trim'); // 设备唯一标示
        // 校验用户
        $this->check();
        // 非法参数判断
        if (intval(trim($num)) <= 0 || intval(trim($num)) > 500) {
            $this->outPut(null, 3003);
        }
        if (!checkMobile($mobile)) {
            $this->outPut(null, 1023);
        }

        $team = D('Team');
        $res = $team->teamBuy($this->uid, $id, $num, $mobile, $plat, $uniq_identify,$ver);

        // 结果处理
        if (!$res || isset($res['error'])) {
            $this->_writeDBErrorLog($res, $team, 'api');
            $this->outPut(null, 3004, null, ternary($res['error'], ''));
        }

        $this->outPut($res, 0);
    }

    /**
     * 快捷团单购买
     */
    public function quickTeamBuy() {

        // 参数接收
        $this->_checkblank(array('id', 'num', 'mobile', 'plat', 'vCode'));
        $id = I('post.id', 0, 'intval'); // 商品id
        $num = I('post.num', 1, 'intval'); // 购买商品的数量
        $mobile = I('post.mobile', '', 'strval'); // 电话
        $plat = I('post.plat', '', 'strval'); // 平台
        $vCode = I('post.vCode', '', 'strval'); // 平台
        $uniq_identify = I('post.uniq_identify', '', 'trim'); // 设备唯一标示
        //2016.3.12 校验新接口
        $jy = I('post.jy', '');
        if($jy)
        {
            $jy=strtolower($jy);
        }else{
            $jy='old';
        }
        // 非法参数判断
        if (intval(trim($num)) <= 0) {
            $this->outPut(null, 3003);
        }
        if (!checkMobile($mobile)) {
            $this->outPut(null, 1023);
        }

        // 判断该手机号是否注册
        $user = D('User');
        $userRes = $user->isRegister(array('mobile|username|email' => $mobile));
        if ($userRes) {
            $isCode = $user->checkMobileVcode($vCode, $mobile, 'buy',$jy);
            if (!$isCode) {
                $this->outPut(null, 1030);
            }
        } else {
            $userRes = $user->mobileRegister($mobile, '123456', $vCode, 'buy', '', true,$jy);
        }
        if (!$userRes) {
            $this->_writeDBErrorLog($userRes, $user, 'api');
            $this->outPut(null, 3005);
        }
        $uid = $userRes['id'];
        $token = $this->_createToken($uid);

        // 下单
        $team = D('Team');
        $res = $team->teamBuy($uid, $id, $num, $mobile, $plat,$uniq_identify);
        // 结果处理
        if (!$res || isset($res['error'])) {
            $this->_writeDBErrorLog($res, $team, 'api');
            $this->outPut(null, 3005, null, ternary($res['error'], ''));
        }
        $res['token'] = $token;
        $this->outPut($res, 0);
    }

    /**
     * 团单秒杀
     */
    public function teamSecondKill() {

        // 参数接收
        $this->_checkblank(array('id', 'num', 'mobile', 'plat'));
        $id = I('post.id', 0, 'intval'); // 商品id
        $num = I('post.num', 1, 'intval'); // 购买商品的数量
        $mobile = I('post.mobile', '', 'strval'); // 电话
        $plat = I('post.plat', '', 'strval'); // 平台
        $vCode = I('post.vCode', '', 'strval'); // 平台
        $uniq_identify = I('post.uniq_identify', '', 'trim'); // 设备唯一标示
        $token = I('post.token', '', 'strval');
        if (!trim($token)) {
            $token = I('get.token', '', 'strval');
        }

        if (intval($num) <= 0) {
            $this->outPut(null, 3003);
        }
        if (!checkMobile($mobile)) {
            $this->outPut(null, 1023);
        }

        // 参数校验
        $register_token = '';
        if (trim($token)) {
            // 已登录
            $this->check();
        } else {
            // 未登录快捷秒杀下单
            $user = D('User');
            $userRes = $user->isRegister(array('mobile|username|email' => $mobile));
            if ($userRes) {
                $isCode = $user->checkMobileVcode($vCode, $mobile, 'buy');
                if (!$isCode) {
                    $this->outPut(null, 1030);
                }
            } else {
                $userRes = $user->mobileRegister($mobile, '123456', $vCode, 'buy', '', true);
            }
            if (!$userRes) {
                $this->_writeDBErrorLog($userRes, $user, 'api');
                $this->outPut(null, 3005);
            }
            $this->uid = $userRes['id'];
            $register_token = $this->_createToken($this->uid);
        }

        // 获取团单信息
        $team = D('Team');
        $teamRes = $this->_getRowDataToOTS('team', array('id' => $id));
        if (!$teamRes) {
            $teamRes = $team->where(array('id' => $id))->find();
        }
        if (!$teamRes) {
            $this->outPut(null, 3006);
        }

        // 判断是否秒杀团单
        if (isset($teamRes['team_type']) && strtolower(trim($teamRes['team_type'])) != 'timelimit') {
            $this->outPut(null, 3007);
        }

        // 判断购买时间
        $nowTime = time();
        $beginTime = $teamRes['begin_time'];
        $endTime = $teamRes['end_time'];
        if (isset($teamRes['flv']) && strtolower(trim($teamRes['flv'])) == 'y') {
            $beginTime = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $beginTime));
            $endTime = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $endTime));
        }
        if ($nowTime < $beginTime || $nowTime > $endTime) {
            $this->outPut(null, 3008);
        }

        // 判断购买的数量是否合法
        if (isset($teamRes['permin_number']) && intval($teamRes['per_number']) > 0 && intval($teamRes['permin_number']) > $num) {
            $this->outPut(null, 3009);
        }
        if (isset($teamRes['per_number']) && intval($teamRes['per_number']) > 0 && intval($teamRes['per_number']) < $num) {
            $this->outPut(null, 3009);
        }

        $buyCountKey = "order_buy_count_{$id}_" . md5($beginTime . '_' . $endTime, true);

        //连接redis
        $redis = new \Common\Org\phpRedis('pconnect');

        // 判断该人是否可以购买
        $buyUserIDKey = "order_success_{$id}_" . md5($beginTime . '_' . $beginTime, true);
        $buyedcount = $redis::$redis->hIncrBy($buyUserIDKey, $this->uid, $num);
        if (intval($teamRes['per_number']) > 0 && $buyedcount > intval($teamRes['per_number'])) {
            $this->outPut(null, 3010);
        }

        //更新计步器
        $incResult = $this->_IncRedis($redis::$redis, $buyCountKey, intval($teamRes['max_number']), $num);
        if ($incResult === false) {
            $this->outPut(null, 3011);
        } elseif ($incResult === 'poor') {
            $this->outPut(null, 3012);
        } elseif (is_null($incResult)) {
            $incResult = $this->_IncRedis($redis::$redis, $buyCountKey, intval($teamRes['max_number']), $num);
            if ($incResult === false) {
                $this->outPut(null, 3011);
            } elseif ($incResult === 'poor') {
                $this->outPut(null, 3012);
            } elseif (is_null($incResult)) {
                $this->outPut(null, 3013);
            }
        }

        // 将请求消息放入队列中
        $data = array(
            'id' => $id,
            'num' => $num,
            'uid' => $this->uid,
            'mobile' => $mobile,
            'plat' => $plat,
            'begin_time' => $beginTime,
            'uniq_identify' => $uniq_identify,
            'end_time' => $endTime,
            'max_number' => $teamRes['max_number']
        );
        $requireMessageKey = "order_require_message_{$id}_" . md5($beginTime . '_' . $endTime, true);
        $redis::$redis->rPush($requireMessageKey, json_encode($data));

        $this->_opDataToMysqlOrder($data, true);
        $r_data = array();
        if (trim($register_token)) {
            $r_data['token'] = trim($register_token);
        }
        $this->outPut($data, 0, null, '下单成功！请前去支付！');
    }

    /**
     * 获取秒杀的订单
     */
    public function getSecondKillOrder() {
        $this->_checkblank(array('id', 'num', 'mobile', 'plat'));
        $id = I('post.id', 0, 'intval'); // 商品id
        $num = I('post.num', 1, 'intval'); // 购买商品的数量
        $mobile = I('post.mobile', '', 'strval'); // 电话
        $plat = I('post.plat', '', 'strval'); // 平台

        if (intval($num) <= 0) {
            $this->outPut(null, 3003);
        }
        if (!checkMobile($mobile)) {
            $this->outPut(null, 1023);
        }

        // 参数校验
        $this->check();
        $where = array(
            'team_id' => $id,
            'user_id' => $this->uid,
            'mobile' => $mobile,
            'quantity' => $num,
            'state' => 'unpay',
            'rstate' => 'normal'
        );
        $order = M('order');
        $resCount = $order->where($where)->count();
        if (!$resCount || $resCount < 0) {
            $this->outPut(null, 1003);
        }
        $team = D('Team');
        $res = $team->teamBuy($this->uid, $id, $num, $mobile, $plat);
        // 结果处理
        if (!$res || isset($res['error'])) {
            $this->_writeDBErrorLog($res, $team, 'api');
            $this->outPut(null, 3005, null, ternary($res['error'], ''));
        }
        $this->outPut($res, 0);
    }

    /**
     * 团单支付
     */
    public function teamPay() {

        // 接收参数
        $this->_checkblank(array('orderId', 'payAction', 'plat'));
        $orderId = I('post.orderId', 0, 'intval');
        $payAction = I('post.payAction', '', 'strval');
        $plat = I('post.plat', '', 'strval');

        // 校验用户
        $this->check();

        // 验证支付方式
        $team = D('Team');
        $payAction = strtolower(trim($payAction));
        if (!$team->isPayAction($payAction)) {
            $this->outPut(null, 3015);
        }

        // 团单支付
        $res = $team->teamPay($this->uid, $orderId, $payAction, $plat);
        // 结果处理
        if (!$res || isset($res['error'])) {
            $this->_writeDBErrorLog($res, $team, 'api');
            if ($payAction == 'creditpay') {
                $this->outPut(null, 3016, null, ternary($res['error'], ''));
            }
            $this->outPut(null, 3014, null, ternary($res['error'], ''));
        }

        $this->outPut($res, 0);
    }

    /**
     * 其他人也在看的团单
     */
    public function partnerNearTeam() {

        // 接收校验参数
        $this->_checkblank('partnerId');
        $partnerId = I('get.partnerId', 0, 'intval');
        $lng = I('get.lng', 0, 'trim');
        $lat = I('get.lat', 0, 'trim');

        $cache_key = $this->_getCacheKey('partnerNearTeam', array(), $partnerId);
        $data = $this->apiCache($cache_key);
        $team = D('Team');
        if (!$data && !is_array($data)) {
            // 获取商户经纬度
            $partner = D('Partner');
            $partnerRes = $partner->getPartnerDetail($partnerId);

            if (!isset($partnerRes['long']) || !trim($partnerRes['long']) || !isset($partnerRes['lat']) || !trim($partnerRes['lat'])) {
                $this->outPut(array(), 0);
            }
            $city_id = '';
            if (isset($partnerRes['city_id']) && trim($partnerRes['city_id'])) {
                $city_id = $partnerRes['city_id'];
            }
            $lng = $partnerRes['long'];
            $lat = $partnerRes['lat'];
            $distance = 1;

            // 获取该商家附近1km的所有商家
            $lngLatSquarePoint = $team->returnSquarePoint($lng, $lat, $distance);
            $where = array(
                'partner.id' => array('neq', $partnerId),
                'partner.city_id' => intval($city_id),
            );
            $where['_string'] = "partner.`long`>='{$lngLatSquarePoint['left-top']['lng']}' AND partner.lat>='{$lngLatSquarePoint['left-bottom']['lat']}' AND partner.`long`<='{$lngLatSquarePoint['right-bottom']['lng']}' AND partner.`lat`<='{$lngLatSquarePoint['left-top']['lat']}'";
            $distanceField = $team->getMysqlDistanceField($lat, $lng);
            $field = array(
                'partner.id' => 'partner_id',
                $distanceField => 'distance',
            );
            $res = $partner->where($where)->field($field)->order('distance asc')->limit(10)->select();

            if (!$res) {
                $this->outPut(array(), 0);
            }

            // 获取附近商家的团单，每个商家取销量最好的，
            $data = array();
            $field = $team->getTeamField();
            list($where, $_) = $team->getMysqlWhere(array());
            foreach ($res as &$v) {
                if ($v['partner_id']) {
                    $where['partner_id'] = $v['partner_id'];
                    $teamRes = $team->field($field)->where($where)->order('now_number DESC')->find();
                    $teamRes && array_push($data, $teamRes);
                }

                if (count($data) >= 4) {
                    unset($v);
                    break;
                }
            }
            $data = $team->dealTeamData($data, false, true);
            $this->apiCache($cache_key, $data);
        }
        
        // 获取经纬度
        if($lng && $lat){
            $team->getTeamDistance($data, array('lng' => $lng, 'lat' => $lat));
        }
        
        $this->outPut($data, 0);
    }

    /**
     * 选择抵金券 重新生成团单价格
     */
    public function updateTeamBuy() {
        $this->_checkblank(array('order_id'));
        $order_id = I('post.order_id', 0, 'intval'); // 商品id
        $card_id = I('post.card_id', 0, 'intval'); // 购买商品的数量
        // 校验用户
        $this->check();

        $team = D('Team');
        $data = $team->updateTeamBuy($order_id, $card_id, $this->uid);
        // 结果处理
        if (!$data) {
            $this->outPut(null, 3017);
        }
        $this->outPut($data, 0);
    }

    /**
     * 生成代金券
     */
    public function createCard() {
        $this->check();
        $this->_checkblank('order_id');
        $id = I('get.order_id');
        $order = D('Order')->isExistOrder($id, $this->uid);
        if (empty($order)) {
            $this->outPut('', -1, '', '订单不存在');
        }
        $model = D('Card');
        $res = $model->isCreateCard($order);
        if ($res) {
            $result = $model->createCard($this->uid, $order);
            if (isset($result['error'])) {
                $this->outPut('', -1, '', $result['error']);
            } else {
                $this->outPut($result, 0);
            }
        } else {
            $this->outPut('', -1, '', $res['error']);
        }
    }

    /**
     * 获取分店列表
     */
    public function getParnerAllBranchList() {
        $this->_checkblank(array('partner_id'));
        $parner_id = I('get.partner_id', '', 'trim');
        $lng = I('get.lng', '', 'trim');
        $lat = I('get.lat', '', 'trim');

        $w_data = array();
        if ($lng && $lat) {
            $w_data = array(
                'lng' => $lng,
                'lat' => $lat,
            );
        }

        $team = D('Team');
        $data = $team->getParnerAllBranchList($parner_id, $w_data);
        $this->outPut($data, 0);
    }

    /**
     * 根据类型获取团单封装
     * @param type $cityId
     * @param type $type
     * @param type $lastId
     * @return type
     */
    private function __getTeamListByType($cityId, $type, $lastId = '') {
        $team = D('Team');
        $now_time = time();
        $data = array();
        switch ($type) {
            case 'special_selling':
                $query = "group_id:'1618'";
                $where = "end_time>$now_time";
                if (trim($lastId)) {
                    $where .= "AND id > $lastId";
                }
                if (trim($cityId)) {
                    $query .= "AND city_id:'$cityId'";
                }
                $sort = array('id' => '+');
                $data = $this->_Search($query, $where, $sort);
                if (!$data) {
                    $where = array(
                        'group_id' => '1618',
                        'end_time' => array('GT', $now_time),
                    );
                    if (trim($cityId)) {
                        $where['city_id'] = $cityId;
                    }
                    if (trim($lastId)) {
                        $where['id'] = array('GT', $lastId);
                    }
                    $data = $team->getTeamListByWhere($where);
                }
                break;
            case 'limited':
                // 限量团单
                $where = "end_time>$now_time";
                $query = "team_type:'limited'";
                if (trim($lastId)) {
                    $where .= "AND id > $lastId";
                }
                if (trim($cityId)) {
                    $query .= "AND city_id:'$cityId'";
                }
                $sort = array('id' => '+');
                $data = $this->_Search($query, $where, $sort);
                // 从数据库获取
                if (!$data) {
                    $where = array(
                        'team_type' => 'limited',
                        'end_time' => array('GT', $now_time),
                    );
                    if (trim($cityId)) {
                        $where['city_id'] = $cityId;
                    }
                    if (trim($lastId)) {
                        $where['id'] = array('GT', $lastId);
                    }
                    $data = $team->getTeamListByWhere($where);
                }
                break;
            case 'timelimit':
            default:
                // 秒杀
                // 从搜索服务中获取
                $where = "(flv='Y' OR end_time>$now_time)";
                $query = "team_type:'timelimit'";
                if (trim($lastId)) {
                    $where .= "AND id > $lastId";
                }
                if (trim($cityId)) {
                    $query .= "AND city_id:'$cityId'";
                }
                $sort = array('id' => '+');
                $data = $this->_Search($query, $where, $sort);
                // 从数据库获取
                if (!$data) {
                    $where = array('team_type' => 'timelimit');
                    if (trim($cityId)) {
                        $where['city_id'] = $cityId;
                    }
                    if (trim($lastId)) {
                        $where ['id'] = array('GT', $lastId);
                    }
                    $data = $team->secondKillTeam($where);
                }
                break;
        }

        return $data;
    }

    //  =============  邮购相关接口===============

    /**
     * 获取邮购团单相关信息
     */
    public function getMailTeamInfo() {
        $this->_checkblank(array('team_id'));
        $team_id = I('get.team_id', '', 'trim');
        $token = I('get.token', '', 'string');
        if (!trim($token)) {
            $token = I('post.token', '', 'string');
        }

        // 获取团单属性
        $team_attribute = array();
        $team_res = M('team')->where(array('id' => $team_id))->field('id,is_optional_model,max_number')->find();
        if (isset($team_res['is_optional_model']) && trim($team_res['is_optional_model']) == 'Y') {
            $where = array(
                'team_id' => $team_id,
            );
            $team_attribute = M('team_attribute')->where($where)->field('id,name,now_num,max_num')->select();
            if ($team_attribute) {
                foreach ($team_attribute as $k => &$v) {
                    $v['surplus_num'] = '0';
                    if (isset($v['max_num']) && intval($v['max_num']) > 0) {
                        $v['surplus_num'] = strval(ternary($v['max_num'], 0) - ternary($v['now_num'], 0));
                        if (intval($v['surplus_num']) <= 0) {
                            $v['surplus_num'] = 0;
                        }
                        continue;
                    }
                    
                    // 如果不限购 且max_num 为0的  则过滤
                    if(isset($v['max_num']) && intval($v['max_num']) <= 0 && isset($team_res['max_number']) && intval($team_res['max_number'])>0){
                         unset($team_attribute[$k]);
                          continue;
                    }
                }
                unset($v);
            }
        }

        // 获取送货时间
        $delivery_time = array();
        $mail_team_delivery_time = C('MAIL_TEAM_DELIVERY_TIME');
        if ($mail_team_delivery_time) {
            foreach ($mail_team_delivery_time as $k => $v) {
                $delivery_time[$k] = array('id' => $k, 'name' => $v);
            }
        }

        // 默认地址
        $default_address = array();
        if (trim($token)) {
            $this->check();
            if (isset($this->uid) && trim($this->uid)) {
                $where = array(
                    'user_id' => $this->uid,
                    'default' => 'Y',
                );
                $default_address = M('address')->where($where)->find();
                if(!$default_address){
                    // 如果没有地址取最新地址
                    unset($where['default']);
                    $default_address = M('address')->where($where)->order(array('create_time'=>'desc'))->find();
                }
                if ($default_address) {
                    $default_address['mobile_hide'] = substr($default_address['mobile'], 0, 4) . '****' . substr($default_address['mobile'], -4, 4);
                }
            }
        }

        // 返回数据
        $data = array(
            'team_attribute' => $team_attribute ? array_values($team_attribute) : array(),
            'delivery_time' => $delivery_time ? array_values($delivery_time) : array(),
            'default_address' => $default_address ? $default_address : (object) array(),
        );
        $this->outPut($data, 0);
    }

    /**
     * 邮购团单下单提交
     */
    public function mailTeamBuy() {
        $this->_checkblank(array('id', 'num', 'mobile', 'plat', 'address_id'));
        $id = I('post.id', 0, 'intval'); // 商品id
        $num = I('post.num', 1, 'intval'); // 购买商品的总数量
        $mobile = I('post.mobile', '', 'trim'); // 电话
        $plat = I('post.plat', '', 'strval'); // 平台
        $uniq_identify = I('post.uniq_identify', '', 'trim'); // 设备唯一标示

        /**
                * base64_encode[{id:123,name:'红色XL',num:2},...])
                */
        $goods = I('post.goods', '[]', 'trim'); // 购买属性值
        $address_id = I('post.address_id', '', 'trim'); // 地址id
        $delivery_time = I('post.delivery_time', 'work_rest_day', 'trim'); // 希望送货时间
        // 检测用户是否登录
        $this->check();

        if (intval(trim($num)) <= 0 || intval(trim($num)) > 500) {
            $this->outPut(null, 3003);
        }
        if (!checkMobile($mobile)) {
            $this->outPut(null, 1023);
        }

        // 送货地址
        $address_count = M('address')->where(array('id' => $address_id, 'user_id' => $this->uid))->count();
        if (!$address_count || $address_count <= 0) {
            $this->outPut(null, -1, null, '该地址不存在！');
        }

        // 送货时间
        $mail_team_delivery_time = C('MAIL_TEAM_DELIVERY_TIME');
        if (!isset($mail_team_delivery_time[$delivery_time])) {
            $this->outPut(null, -1, null, '期望送货时间不合法！');
        }

        // 属性库存校验
        $goods_arr = @json_decode($goods, true);
        if ($goods_arr === null) {
            $goods = base64_decode(str_replace(array('%2b', ' '), '+', urldecode($goods)));
            $goods_arr = @json_decode($goods, true);
        }
        if ($goods_arr) {
            $team_attribute = M('team_attribute')->where(array('team_id' => $id))->field('id,name,now_num,max_num')->index('id')->select();
            $attr_num = 0;
            foreach ($goods_arr as $k => $v) {

                $attr_num = $attr_num + $v['num'];
                $max_num = ternary($team_attribute[$v['id']]['max_num'], 0);
                $now_num = ternary($team_attribute[$v['id']]['now_num'], 0);
                if (!$v['num'] || $v['num'] <= 0) {
                    unset($goods_arr[$k]);
                    continue;
                }
                if (intval($max_num)>0 && $now_num + $v['num'] > $max_num) {
                    $this->outPut(null, -1, null, $v['name'] . '库存不足！');
                }
            }
            if ($attr_num > 0 && $attr_num != $num) {
                $this->outPut(null, -1, null, '购买的总数量不合法！');
            }
            $goods = @json_encode(array_values($goods_arr));
        }

        $team = D('Team');
        // 普通下单
        $order_res = $team->teamBuy($this->uid, $id, $num, $mobile, $plat, $uniq_identify);
        if (isset($order_res['error']) && trim($order_res['error'])) {
            $this->outPut(null, -1, null, $order_res['error']);
        }

        // 邮购属性更新
        $buy_res = $team->mailTeamBuyUpdateOrder($this->uid, $order_res['order_id'], $address_id, $delivery_time, $goods);
        if (isset($buy_res['error']) && trim($buy_res['error'])) {
            $this->outPut(null, -1, null, $buy_res['error']);
        }

        // 去除抵金券
        $order_res['is_use_card'] = 'N';

        // 返回参数
        $this->outPut($order_res, 0);
    }

}
