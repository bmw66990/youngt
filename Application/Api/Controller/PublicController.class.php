<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/14
 * Time: 8:55
 */

namespace Api\Controller;

/**
 * 其他接口数据处理
 * Class PublicController
 * @package Api\Controller
 */
class PublicController extends CommonController {
    protected $signCheck = false;

    /**
     * 激活手机
     */
    public function Active() {
        $Model = D('MobileInfo');
        $where['mac'] = I('post.mac', '', 'trim');
        //判断手机是否激活
        $mobile_info = $Model->getTotal($where);
        if ($mobile_info > 0)
            $this->outPut(null, 4);
        $data = $Model->insert();
        //判断是否激活成功
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'api');
            $error = $Model->getErrorInfo();
            $this->outPut(null, -1, null, $error['info']);
        }
        $this->outPut($data, 0);
    }

    /**
     * 意见反馈
     */
    public function Advice() {
        $Model = D('Feedback');
        $data = $Model->insert();
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'api');
            $error = $Model->getErrorInfo();
            $this->outPut(null, -1, null, $error['info']);
        }
        $this->outPut($data, 0);
    }

    /**
     * 用户反馈
     */
    public function userFeedback() {
        $this->_checkblank('content');
        $content = I('post.content');
        $token = I('param.token');
        $user = array();
        if ($token) {
            $this->check();
            $user = M('user')->field('mobile,username')->find($this->uid);
            $content .= '手机号码: ' . $user['mobile'] . '用户名：' . $user['username'];
        }

        $data = array(
            'content' => $content,
            'category' => 'suggest',
            'city_id' => 0,
            'phone' => ternary($user['mobile'], ''),
            'create_time' => time()
        );
        $model = D('Feedback');
        if ($model->add($data)) {
            $this->outPut('', 0);
        } else {
            $this->outPut(null, -1, null, '反馈提交失败');
        }
    }

    /**
     * 检查更新
     */
    public function checkUpdate() {
        $plat = I('get.plat', '', 'trim');
        $app_ver = I('get.ver', '', 'trim');
        $config = C('AppUpdateAndroid');
        if ($plat == 'ios') {
            $config = C('AppUpdateIos');
        }
        $service_ver = ternary($config['ver'], '');
        $is_upgrade = 'N';
        if ($app_ver && strcmp($service_ver, $app_ver) > 0) {
            $is_upgrade = 'Y';
        }
        $data = array(
            'version' => $service_ver,
            'is_force' => ternary($config['is_force'], ''),
            'is_upgrade' => $is_upgrade,
            'description' => ternary($config['description'], ''),
            'download_url' => ternary($config['url'], ''),
        );
        $this->outPut($data, 0);
    }

    /**
     * 团单图文详情H5页面
     */
    public function getTeamInfo() {
        $id = I('get.id', 0, 'intval');
        if (!$id) {
            $this->assign('error', '获取失败!');
            $this->display();
            exit;
        }
        $team_info = M('team')->getFieldById($id, 'id,detail,systemreview,notice');
        $data = array('detail' => $team_info[$id]['detail'], 'systemreview' => $team_info[$id]['systemreview'], 'notice' => $team_info[$id]['notice']);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 帮助H5页面
     */
    public function Help() {
        $this->display();
    }

    /**
     * 用户协议页面
     */
    public function UserAgree() {
        $this->display();
    }

    /**
     * 退款帮助H5页面
     */
    public function refundHelp() {
        $this->display();
    }

    /**
     * 分站加盟
     */
    public function Station() {
        $Model = D('Feedback');
        $data = $Model->insert();
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'api');
            $error = $Model->getErrorInfo();
            $this->outPut(null, -1, null, $error['info']);
        }
        $this->outPut($data, 0);
    }

    /**
     * 商家入驻
     */
    public function Partner() {
        $Model = D('Feedback');
        $data = $Model->insert();
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'api');
            $error = $Model->getErrorInfo();
            $this->outPut(null, -1, null, $error['info']);
        }
        $this->outPut($data, 0);
    }

    /**
     * 首页广告
     */
    public function Admanage() {
        $city_id = I('get.city_id', 0, 'intval');
        if (!$city_id)
            $this->outPut(null, 1001);
        $Model = M('admanage');
        $where = array('type' => 'app', 'cityid' => array('in', array($city_id, 957)), 'end_time' => array('gt', time()));
        $rows = $Model->field('picarr,linkarr,textarr')->where($where)->order('sort_order desc')->select();
        $data = array();
        if ($rows) {
            foreach ($rows as $key => $val) {
                $data[$key]['pic'] = getImagePath($val['picarr']);
                $data[$key]['url'] = $val['linkarr'];
                $data[$key]['title'] = $val['textarr'];

                // 链接处理
                $href = $val['linkarr'];
                if (strpos($href, 'm.youngt.com') !== false) {
                    if (strpos($href, '?') === false) {
                        $href = "$href?city_id=$city_id";
                    } else {
                        $href = "$href&city_id=$city_id";
                    }
                }
                $data[$key]['url'] = $href;
            }
        }
        //$data[] = array('pic'=>'http://jscss.youngt.com/ggg.jpg','url'=>'');
        $this->outPut(array_values($data), 0);
    }

    /**
     * 首页活动
     */
    public function getActivitiesList() {
        $city_id = I('get.city_id', 0, 'intval');
        $plat = I('get.plat', 0, 'trim');
        if($plat){
            $isNEW = true;
        }
        if (!$city_id) {
            $this->outPut(null, 1001);
        }

        $admanage = D('Admanage');
        $data = $admanage->getActivitiesList($city_id);
        if ($data) {
            $team = D('Team');
            // 活动团单
            list($where, $sort) = $team->getMysqlWhere(array());
            unset($where['_string']);
            $where['city_id'] = array('in', array($city_id, 957));

            // 新用户独享
            $now_time = time();
            $new_user_where = "end_time>$now_time AND begin_time<$now_time AND city_id={$city_id}";
            $new_user_query = "team_type:'newuser'";
            $new_user_where_db = array(
                'team_type' => 'newuser',
                'city_id' => $city_id,
                'end_time' => array('gt', $now_time),
                'begin_time' => array('lt', $now_time),
            );
            if($isNEW){
                // 新客立减
                $now_time = time();
                $new_guser_where = "end_time>$now_time AND begin_time<$now_time AND city_id={$city_id}";
                $new_guser_query = "team_type:'newguser'";
                $new_guser_where_db = array(
                    'team_type' => 'newguser',
                    'city_id' => $city_id,
                    'end_time' => array('gt', $now_time),
                    'begin_time' => array('lt', $now_time),
                );
            }

            foreach ($data as $k => &$v) {

                // 如果是  一元云购活动 则直接放行
                if (isset($v['href']) && trim($v['href']) && strpos(strtolower($v['href']), '/cloud/') !== false) {
                    continue;
                }

                // 修改原来活动地址到伪静态地址
                /*if (isset($v['href']) && trim($v['href']) && strpos(strtolower($v['href']), '/activities/') !== false) {
                    $v['href']='http://yangling.youngt.com/Wap/active-'.$v['id'].'-'.$city_id.'.html';
                }*/

                // 新用户独享
                if (isset($v['href']) && trim($v['href']) && strpos(strtolower($v['href']), 'new_user_team') !== false) {
                    $now_user_team_count = $this->_SearchCount($new_user_query, $new_user_where);
                    if (!$now_user_team_count) {
                        $now_user_team_count = $team->where($new_user_where_db)->count();
                    }
                    if (!$now_user_team_count || intval($now_user_team_count) <= 0) {
                        unset($data[$k]);
                    }
                    continue;
                }
                if($isNEW){
                    // 新客立减
                    if (isset($v['href']) && trim($v['href']) && strpos(strtolower($v['href']), 'new_guser_team') !== false) {
                        $now_guser_team_count = $this->_SearchCount($new_guser_query, $new_guser_where);
                        if (!$now_guser_team_count) {
                            $now_guser_team_count = $team->where($new_guser_where_db)->count();
                        }
                        if (!$now_guser_team_count || intval($now_guser_team_count) <= 0) {
                            unset($data[$k]);
                        }
                        continue;
                    }
                }else{
                    if (isset($v['href']) && trim($v['href']) && strpos(strtolower($v['href']), 'new_guser_team') !== false) {
                        unset($data[$k]);
                        continue;
                    }
                }


                // 如果不是普通活动 直接返回
                if (!isset($v['href']) || !trim($v['href']) || strpos(strtolower($v['href']), '/activities/') === false) {
                    continue;
                }


                $where['activities_id'] = intval($v['id']);
                $team_count = $team->where($where)->count();
                if (!$team_count || intval($team_count) <= 0) {
                    unset($data[$k]);
                }
            }

            //2016.3.30加去除三个那种情况
            if($isNEW){
                $cout = count($data);
                if ($cout && $cout == 3) {
                    foreach ($data as $k => &$v) {
                        if (isset($v['title']) && trim($v['title']) && strpos(strtolower($v['title']), '9.9包邮') !== false) {
                            unset($data[$k]);
                            continue;
                        }
                    }
                }
            }else{
                /*$cout = count($data);
                if ($cout && $cout == 4) {
                    unset($data[0]);
                }*/
            }

        }
        if(false){
            $news=array(
                array(
                    'id'=>'',
                    'image'=>'',
                    'href'=>'http://m.youngt.com',
                    'title'=>'青团网开通新站了，你知道了吗！',
                    'show_type'=>'news',
                    'begin_time'=>''
                ),
                array(
                    'id'=>'',
                    'image'=>'',
                    'href'=>'http://m.youngt.com',
                    'title'=>'恭喜杨凌站！',
                    'show_type'=>'news',
                    'begin_time'=>''
                )
            );
            $data=array_merge($data,$news);
        }
        $this->outPut(array_values($data), 0);
    }

    /**
     * 收藏
     */
    public function Collect() {
        $this->_checkblank('team_id');
        $this->check();
        $_POST['user_id'] = $this->uid;
        $Model = D('Collect');
        //检查团单是否已经收藏
        $team_id = I('post.team_id', 0, 'intval');
        $res = $Model->checkIsCollect($team_id, $this->uid);
        if ($res) {
            $this->outPut(null, -1, '', '该项目已收藏');
        }
        $data = $Model->insert();
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'api');
            $error = $Model->getErrorInfo();
            $this->outPut(null, -1, null, $error['info']);
        }
        $this->outPut($data, 0);
    }

    /**
     * 取消收藏
     */
    public function delCollect() {
        $this->_checkblank('team_id');
        $this->check();
        $team_id = I('post.team_id', 0, 'intval');
        $where = array('user_id' => $this->uid, 'team_id' => $team_id);
        $res = M('collect')->where($where)->delete();
        if ($res === false) {
            $this->_writeDBErrorLog($res, M('collect'), 'api');
            $this->outPut(null, -1, null, M('collect')->getError());
        }
        $this->outPut($res, 0);
    }

    /**
     * 批量取消收藏
     */
    public function BatchDelCollect() {
        $this->_checkblank('team_id');
        $this->check();        
        $team_id = I('post.team_id', '', 'trim');
        $team_id = explode(',', rtrim($team_id, ','));
        $map = array(
            'team_id' => array('IN', $team_id),
            'user_id' => $this->uid
        );
        $res = M('collect')->where($map)->delete();
        if ($res == '0') {
            $this->_writeDBErrorLog($res, M('collect'), 'api');            
            $this->outPut(null, -1, '', '操作有误！');
        }
        $this->outPut($res, 0);
    }

    /**
     * 代金券使用说明
     */
    public function cardHelp() {
        $this->display();
    }

}
