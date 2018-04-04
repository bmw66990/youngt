<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;

/**
 * 百科公告模块
 * Class TeamController
 * @package Admin\Controller
 */
class EncyclopediasController extends CommonController {

    private $news_type = array(
        '0' => '内部新闻',
        '1' => '外部新闻'
    );
    private $encyclopedias_type_show_plat = array(
        'all' => '所有平台',
        'admin' => '管理后台',
        'manager' => '代理后台',
    );

    public function index() {
        redirect(U('Encyclopedias/qingtuanEncyclopedias'));
    }

    /**
     * 百科首页
     */
    public function qingtuanEncyclopedias() {

        // 获取青团分类
        $where = array(
            'show_plat' => array('in', array('all', 'admin'))
        );
        $order = array('order_sort' => 'asc','id'=>'desc');
        $field = array('id', 'name');
        $encyclopedias_type = M('encyclopedias_type')->field($field)->where($where)->order($order)->select();
        

        // 整理数据
        if ($encyclopedias_type) {
            $field = array('id', 'title');
            $encyclopedias_where = array('begin_time'=>array('lt',time()));
            foreach ($encyclopedias_type as $k => &$v) {
                $encyclopedias_where['type_id']= $v['id'];
                $list = M('encyclopedias')->field($field)->where($encyclopedias_where)->order($order)->limit(10)->select();
                if (!$list) {
                    unset($encyclopedias_type[$k]);
                    continue;
                }
                $v['list'] = $list;
            }
            unset($v);
        }

        $data = array(
            'list' => $encyclopedias_type,
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 百科详情
     */
    public function encyclopediasDetail() {

        $encyclopedias_id = I('get.encyclopedias_id', '', 'trim');
        if (!$encyclopedias_id) {
            $this->redirect_message(U('Encyclopedias/qingtuanEncyclopedias'), array('error' => base64_encode('百科id不能为空!')));
        }

        $encyclopedias_info = M('encyclopedias')->where(array('id' => $encyclopedias_id))->find();
        if (!$encyclopedias_info) {
            $this->redirect_message(U('Encyclopedias/qingtuanEncyclopedias'), array('error' => base64_encode('该百科不存在!')));
        }
        
         $now_time = time();

        // 查找下一篇上一篇文章
        $prev = array();
        $next = array();
        $order_sort = array('order_sort' => 'asc');
        $encyclopedias_list = M('encyclopedias')->where(array('type_id' => $encyclopedias_info['type_id'],'begin_time'=>array('lt',$now_time)))->order($order_sort)->select();
        if ($encyclopedias_list) {
            foreach ($encyclopedias_list as $k => $v) {
                if ($v['id'] == $encyclopedias_info['id']) {

                    // prov
                    if (isset($encyclopedias_list[$k - 1])) {
                        $prev = $encyclopedias_list[$k - 1];
                    }

                    // next
                    if (isset($encyclopedias_list[$k + 1])) {
                        $next = $encyclopedias_list[$k + 1];
                    }

                    break;
                }
            }
        }
       
        $prev_type = array();
        $next_type = array();
        if (!$prev || !$next) {
            $filed = array(
                'encyclopedias_type.id'=>'id',
                'count(encyclopedias.id)'=>'encyclopedias_count',
            );
            $type_where = array(
                   'encyclopedias_type.show_plat' => array('in', array('all', 'admin'))
             );
            $type_list = M('encyclopedias_type')->where($type_where)->order(array('encyclopedias_type.order_sort' => 'asc','encyclopedias_type.id'=>'desc'))->field($filed)
                    ->join("inner join encyclopedias on encyclopedias.type_id=encyclopedias_type.id and encyclopedias.begin_time<{$now_time}")->group('encyclopedias.type_id')->having('encyclopedias_count>0')
                    ->select();
            if ($type_list) {
                foreach ($type_list as $k => $v) {
                    if ($v['id'] == $encyclopedias_info['type_id']) {

                        // prev
                        if (isset($type_list[$k - 1])) {
                            $prev_type = $type_list[$k - 1];
                        }

                        // next
                        if (isset($type_list[$k + 1])) {
                            $next_type = $type_list[$k + 1];
                        }

                        break;
                    }
                }
            }
        }
        if(!$prev && $prev_type){
            $prev = M('encyclopedias')->where(array('type_id' => $prev_type['id'],'begin_time'=>array('lt',$now_time)))->order(array('order_sort' => 'desc'))->find();
        }
        if(!$next && $next_type){
            
            $next = M('encyclopedias')->where(array('type_id' => $next_type['id'],'begin_time'=>array('lt',$now_time)))->order($order_sort)->find();
        }
        
        $data = array(
            'prev'=>$prev,
            'data'=>$encyclopedias_info,
            'next'=>$next,
        );
       
        $this->assign($data);
        $this->display();
    }

    /**
     * 青团百科
     */
    public function youngtEncyclopedias() {
        $where = array();
        $encyclopedias = M('encyclopedias');

        $count = $encyclopedias->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'encyclopedias.id' => 'encyclopedias_id',
            'encyclopedias.title' => 'encyclopedias_title',
            'encyclopedias.type_id' => 'encyclopedias_type_id',
            'encyclopedias.order_sort' => 'encyclopedias_order_sort',
            'encyclopedias.begin_time' => 'encyclopedias_begin_time',
        );
        $list = $encyclopedias->field($field)->order(array('encyclopedias.order_sort' => 'asc', 'encyclopedias.id' => 'desc', 'encyclopedias.begin_time' => 'desc'))
                ->where($where)->limit($page->firstRow . ',' . $page->listRows)
                ->select();

        // 整理数据
        if ($list) {
            $encyclopedias_type_ids = array();
            foreach ($list as &$v) {
                $encyclopedias_type_ids[$v['encyclopedias_type_id']] = $v['encyclopedias_type_id'];
            }
            unset($v);
            $encyclopedias_type_res = array();
            if ($encyclopedias_type_ids) {
                $res = M('encyclopedias_type')->where(array('id' => array('in', array_keys($encyclopedias_type_ids))))->select();
                foreach ($res as &$v) {
                    $encyclopedias_type_res[$v['id']] = $v['name'];
                }
                unset($v);
            }
            foreach ($list as &$v) {
                $v['encyclopedias_type_name'] = ternary($encyclopedias_type_res[$v['encyclopedias_type_id']], '');
            }
            unset($v);
        }

        $data = array(
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );
 
        $this->assign($data);
        $this->display();
    }

    /**
     * 百科分类管理
     */
    public function encyclopediasTypeManage() {
        $encyclopedias_type = M('encyclopedias_type');
        $encyclopedias_type_list = $encyclopedias_type->order(array('order_sort' => 'asc', 'id' => 'desc'))->select();
        $data = array(
            'list' => $encyclopedias_type_list,
            'encyclopedias_type_show_plat' => $this->encyclopedias_type_show_plat,
        );
        $this->assign($data);
        $this->display();
    }

    public function encyclopediasTypeAdd() {
        $name = I('post.name', '', 'trim');
        $order_sort = I('post.order_sort', '', 'trim');
        $show_plat = I('post.show_plat', '', 'trim');
        if (!$name) {
            $this->ajaxReturn(array('code' => -1, 'error' => '名称不能为空！'));
        }
        if (!$order_sort) {
            $this->ajaxReturn(array('code' => -1, 'error' => '排序不能为空！'));
        }
        $where = array('name' => $name);
        $encyclopedias_type = M('encyclopedias_type');
        $encyclopediasTypeCount = $encyclopedias_type->where($where)->count();
        if ($encyclopediasTypeCount && $encyclopediasTypeCount > 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '该类型名称已经存在，不能使用！'));
        }
        $data = array(
            'name' => $name,
            'order_sort' => $order_sort,
            'show_plat' => $show_plat,
            'create_time' => time(),
            'admin_id' => ternary($this->user['id'], '0'),
        );
        $res = $encyclopedias_type->add($data);
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '类型添加失败！'));
        }
        $this->addOperationLogs("操作：添加青团百科类型,name:[{$name}],id:{$res}");
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 青团百科添加
     */
    public function youngtEncyclopediasAdd() {
        $operation_type = I('post.operation_type', '', 'trim');
        if (!$operation_type) {
            // 获取城市
            $encyclopedias_type = M('encyclopedias_type');
            $encyclopedias_type_list = $encyclopedias_type->order(array('order_sort' => 'asc', 'id' => 'desc'))->select();

            $data = array(
                'encyclopedias_type_list' => $encyclopedias_type_list,
                'operation_type' => 'youngtEncyclopediasAdd',
            );
            $this->assign($data);
            $this->display();
            exit;
        }
        $encyclopedias = I('post.encyclopedias', array(), '');
        if (!isset($encyclopedias['title']) || trim($encyclopedias['title']) == '') {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasAdd'), array('error' => base64_encode('标题不能为空!')));
        }
        if (!isset($encyclopedias['type_id']) || trim($encyclopedias['type_id']) == '') {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasAdd'), array('error' => base64_encode('请选择分类!')));
        }
        if (!isset($encyclopedias['content']) || trim($encyclopedias['content']) == '') {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasAdd'), array('error' => base64_encode('内容不能为空!')));
        }
        if (!isset($encyclopedias['order_sort']) || trim($encyclopedias['order_sort']) == '') {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasAdd'), array('error' => base64_encode('排序不能为空!')));
        }
        if (!isset($encyclopedias['begin_time']) || trim($encyclopedias['begin_time']) == '') {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasAdd'), array('error' => base64_encode('时间不能为空!')));
        }

        $data = array(
            'title' => ternary($encyclopedias['title'], ''),
            'type_id' => ternary($encyclopedias['type_id'], '0'),
            'content' => htmlspecialchars(ternary($encyclopedias['content'], '')),
            'order_sort' => ternary($encyclopedias['order_sort'], '1'),
            'begin_time' => strtotime(ternary($encyclopedias['begin_time'], date('Y-m-d'))),
            'create_time' => time(),
            'admin_id' => ternary($this->user['id'], ''),
        );
        $res = M('encyclopedias')->add($data);
        if (!$res) {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasAdd'), array('error' => base64_encode('青团百科添加失败!')));
        }
        $this->addOperationLogs("操作：添加青团百科,title:[{$data['title']}],id:{$res}");
        redirect(U('Encyclopedias/youngtEncyclopedias'));
    }

    /**
     * 青团百科编辑
     */
    public function youngtEncyclopediasEdit() {
        $encyclopedias_id = I('get.encyclopedias_id', '', 'trim');
        $operation_type = I('post.operation_type', '', 'trim');
        if (!$operation_type) {
            if (!$encyclopedias_id) {
                $this->redirect_message(U('Encyclopedias/youngtEncyclopedias'), array('error' => base64_encode('青团百科id不能为空!')));
            }
            $encyclopediasRes = M('encyclopedias')->where(array('id' => $encyclopedias_id))->find();
            if (!$encyclopediasRes) {
                $this->redirect_message(U('Encyclopedias/youngtEncyclopedias'), array('error' => base64_encode('青团百科信息不存在!')));
            }
            $this->assign($encyclopediasRes);
            // 获取城市
            $encyclopedias_type = M('encyclopedias_type');
            $encyclopedias_type_list = $encyclopedias_type->order(array('order_sort' => 'asc', 'id' => 'desc'))->select();
            $data = array(
                'encyclopedias_type_list' => $encyclopedias_type_list,
                'operation_type' => 'youngtEncyclopediasEdit',
            );
            $this->assign($data);
            $this->display('Encyclopedias/youngtEncyclopediasAdd');
            exit;
        }
        $encyclopedias = I('post.encyclopedias', array(), '');
        if (!$encyclopedias_id) {
            $encyclopedias_id = ternary($encyclopedias['id'], '');
        }
        if (!isset($encyclopedias['title']) || trim($encyclopedias['title']) == '') {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasEdit', array('encyclopedias_id' => $encyclopedias_id)), array('error' => base64_encode('标题不能为空!')));
        }
        if (!isset($encyclopedias['type_id']) || trim($encyclopedias['type_id']) == '') {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasEdit', array('encyclopedias_id' => $encyclopedias_id)), array('error' => base64_encode('请选择分类!')));
        }
        if (!isset($encyclopedias['content']) || trim($encyclopedias['content']) == '') {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasEdit', array('encyclopedias_id' => $encyclopedias_id)), array('error' => base64_encode('内容不能为空!')));
        }
        if (!isset($encyclopedias['order_sort']) || trim($encyclopedias['order_sort']) == '') {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasEdit', array('encyclopedias_id' => $encyclopedias_id)), array('error' => base64_encode('排序不能为空!')));
        }
        if (!isset($encyclopedias['begin_time']) || trim($encyclopedias['begin_time']) == '') {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasEdit', array('encyclopedias_id' => $encyclopedias_id)), array('error' => base64_encode('时间不能为空!')));
        }
        $data = array(
            'title' => ternary($encyclopedias['title'], ''),
            'type_id' => ternary($encyclopedias['type_id'], '0'),
            'content' => htmlspecialchars(ternary($encyclopedias['content'], '')),
            'order_sort' => ternary($encyclopedias['order_sort'], '1'),
            'begin_time' => strtotime(ternary($encyclopedias['begin_time'], date('Y-m-d'))),
            'admin_id' => ternary($this->user['id'], ''),
        );
        $res = M('encyclopedias')->where(array('id' => $encyclopedias_id))->save($data);
        if ($res === false) {
            $this->redirect_message(U('Encyclopedias/youngtEncyclopediasEdit', array('encyclopedias_id' => $encyclopedias_id)), array('error' => base64_encode('青团百科更新失败!')));
        }
        $this->addOperationLogs("操作：编辑青团百科,title:[{$data['title']}],id:{$encyclopedias_id}");
        redirect(U('Encyclopedias/youngtEncyclopedias'));
    }

    /**
     * 青团百科删除
     */
    public function youngtEncyclopediasDelete() {
        $encyclopedias_id = I('get.encyclopedias_id', '', 'trim');
        if (!$encyclopedias_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '青团百科id不能为空！'));
        }
        $encyclopedias = M('encyclopedias');
        $encyclopediasCount = $encyclopedias->where(array('id' => $encyclopedias_id))->count();
        if (!$encyclopediasCount || $encyclopediasCount <= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '你要删除的青团百科不存在！'));
        }
        $res = $encyclopedias->where(array('id' => $encyclopedias_id))->delete();
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '删除失败！'));
        }
        $this->addOperationLogs("操作：删除青团百科,id:{$encyclopedias_id}");
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 新闻公告
     */
    public function newsNotice() {

        $where = array('type' => array('in', array('0', '1')));

        $news = M('news');
        $count = $news->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'news.id' => 'news_id',
            'news.title' => 'news_title',
            'news.type' => 'news_type',
            'news.city_id' => 'news_city_id',
            'news.begin_time' => 'news_begin_time',
        );
        $list = $news->field($field)->order(array('news.id' => 'desc', 'news.sort_order' => 'asc', 'news.begin_time' => 'desc'))
                ->where($where)->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        $city_res = $this->_getCategoryList('city');
        if ($list) {//
            foreach ($list as &$v) {
                $v['news_city_name'] = ternary($city_res[$v['news_city_id']]['name'], '未知');
                $v['news_type_name'] = ternary($this->news_type[$v['news_type']], '未知');
            }
            unset($v);
        }
        $data = array(
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );

        $this->assign($data);
        $this->display();
    }

    public function newsNoticeAdd() {
        $operation_type = I('post.operation_type', '', 'trim');
        if (!$operation_type) {
            // 获取城市
            $city_res = $this->_getCategoryList('city');

            $data = array(
                'news_type' => $this->news_type,
                'citys' => $city_res,
                'operation_type' => 'newsNoticeAdd',
            );
            $this->assign($data);
            $this->display();
            exit;
        }
        $news = I('post.news', array(), '');
        if (!isset($news['title']) || trim($news['title']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeAdd'), array('error' => base64_encode('标题不能为空!')));
        }
        if (!isset($news['city_id']) || trim($news['city_id']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeAdd'), array('error' => base64_encode('请选择城市!')));
        }
        if (!isset($news['type']) || trim($news['type']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeAdd'), array('error' => base64_encode('请选择类型!')));
        }
        if (!isset($news['content']) || trim($news['content']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeAdd'), array('error' => base64_encode('内容不能为空!')));
        }
        if (!isset($news['order_sort']) || trim($news['order_sort']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeAdd'), array('error' => base64_encode('排序不能为空!')));
        }
        if (!isset($news['begin_time']) || trim($news['begin_time']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeAdd'), array('error' => base64_encode('时间不能为空!')));
        }

        $data = array(
            'title' => ternary($news['title'], ''),
            'city_id' => ternary($news['city_id'], ''),
            'type' => ternary($news['type'], '0'),
            'detail' => htmlspecialchars(ternary($news['content'], '')),
            'sort_order' => ternary($news['order_sort'], '1'),
            'begin_time' => strtotime(ternary($news['begin_time'], date('Y-m-d'))),
            'user_id' => ternary($this->user['id'], ''),
        );
        $res = M('news')->add($data);
        if (!$res) {
            $this->redirect_message(U('Encyclopedias/newsNoticeAdd'), array('error' => base64_encode('新闻添加失败!')));
        }
        $this->addOperationLogs("操作：添加新闻公告,title：{$data['title']}id:{$res}");
        redirect(U('Encyclopedias/newsNotice'));
    }

    /**
     * 新闻公告编辑
     */
    public function newsNoticeEdit() {
        $news_id = I('get.news_id', '', 'trim');
        $operation_type = I('post.operation_type', '', 'trim');
        if (!$operation_type) {
            if (!$news_id) {
                $this->redirect_message(U('Encyclopedias/newsNotice'), array('error' => base64_encode('新闻id不能为空!')));
            }
            $newsRes = M('news')->where(array('id' => $news_id))->find();
            if (!$newsRes) {
                $this->redirect_message(U('Encyclopedias/newsNotice'), array('error' => base64_encode('新闻信息不存在!')));
            }
            $this->assign($newsRes);
            // 获取城市
            $city_res = $this->_getCategoryList('city');
            $data = array(
                'news_type' => $this->news_type,
                'citys' => $city_res,
                'operation_type' => 'newsNoticeEdit',
            );
            $this->assign($data);
            $this->display('Encyclopedias/newsNoticeAdd');
            exit;
        }

        $news = I('post.news', array(), '');
        if (!$news_id) {
            $news_id = ternary($news['id'], '');
        }
        if (!isset($news['title']) || trim($news['title']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeEdit', array('news_id' => $news_id)), array('error' => base64_encode('标题不能为空!')));
        }
        if (!isset($news['city_id']) || trim($news['city_id']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeEdit', array('news_id' => $news_id)), array('error' => base64_encode('请选择城市!')));
        }
        if (!isset($news['type']) || trim($news['type']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeEdit', array('news_id' => $news_id)), array('error' => base64_encode('请选择类型!')));
        }
        if (!isset($news['content']) || trim($news['content']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeEdit', array('news_id' => $news_id)), array('error' => base64_encode('内容不能为空!')));
        }
        if (!isset($news['order_sort']) || trim($news['order_sort']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeEdit', array('news_id' => $news_id)), array('error' => base64_encode('排序不能为空!')));
        }
        if (!isset($news['begin_time']) || trim($news['begin_time']) == '') {
            $this->redirect_message(U('Encyclopedias/newsNoticeEdit', array('news_id' => $news_id)), array('error' => base64_encode('时间不能为空!')));
        }

        $data = array(
            'title' => ternary($news['title'], ''),
            'city_id' => ternary($news['city_id'], ''),
            'type' => ternary($news['type'], '0'),
            'detail' => htmlspecialchars(ternary($news['content'], '')),
            'sort_order' => ternary($news['order_sort'], '1'),
            'begin_time' => strtotime(ternary($news['begin_time'], date('Y-m-d'))),
            'user_id' => ternary($this->user['id'], ''),
        );
        $res = M('news')->where(array('id' => $news_id))->save($data);
        if ($res === false) {
            $this->redirect_message(U('Encyclopedias/newsNoticeEdit', array('news_id' => $news_id)), array('error' => base64_encode('新闻更新失败!')));
        }
        $this->addOperationLogs("操作：编辑新闻公告,title：{$data['title']}id:{$news_id}");
        redirect(U('Encyclopedias/newsNotice'));
    }

    /**
     * 新闻公告删除
     */
    public function newsNoticeDelete() {
        $news_id = I('get.news_id', '', 'trim');
        if (!$news_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '新闻id不能为空！'));
        }
        $news = M('news');
        $newsCount = $news->where(array('id' => $news_id))->count();
        if (!$newsCount || $newsCount <= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '你要删除的新闻不存在！'));
        }
        $res = $news->where(array('id' => $news_id))->delete();
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '删除失败！'));
        }
        $this->addOperationLogs("操作：删除新闻公告,id:{$news_id}");
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 分站公告
     */
    public function substationNotice() {
        $all_city = $this->_getCategoryList('city');
        $paramArray = array(
            array('content', '', 'like'),
            array('city_id', 0)
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $gonggao = M('gonggao');
        $count = $gonggao->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'gonggao.id' => 'gonggao_id',
            'gonggao.content' => 'gonggao_content',
            'gonggao.city_id' => 'gonggao_city_id',
            'gonggao.creat_time' => 'gonggao_creat_time',
        );
        $list = $gonggao->field($field)->order(array('gonggao.id' => 'desc', 'gonggao.creat_time' => 'desc'))
                ->where($where)->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        $city_res = $this->_getCategoryList('city');
        if ($list) {//
            foreach ($list as &$v) {
                $v['gonggao_city_name'] = ternary($city_res[$v['gonggao_city_id']]['name'], '未知');
                $v['gonggao_content'] = htmlspecialchars_decode($v['gonggao_content']);
            }
            unset($v);
        }
        $data = array(
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
            'displayWhere' => $displayWhere,
            'all_city' => $all_city,
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 分站公告添加
     */
    public function substationNoticeAdd() {
        
    }

    /**
     * 分站公告编辑
     */
    public function substationNoticeEdit() {
        $gonggao_id = I('get.gonggao_id', '', 'trim');
        $operation_type = I('post.operation_type', '', 'trim');
        if (!$operation_type) {
            if (!$gonggao_id) {
                $this->redirect_message(U('Encyclopedias/substationNotice'), array('error' => base64_encode('公告id不能为空!')));
            }
            $gonggaoRes = M('gonggao')->where(array('id' => $gonggao_id))->find();
            if (!$gonggaoRes) {
                $this->redirect_message(U('Encyclopedias/substationNotice'), array('error' => base64_encode('公告信息不存在!')));
            }
            $this->assign($gonggaoRes);
            // 获取城市
            $city_res = $this->_getCategoryList('city');
            $data = array(
                'citys' => $city_res,
                'operation_type' => 'substationNoticeEdit',
            );
            $this->assign($data);
            $this->display();
            exit;
        }

        $gonggao = I('post.gonggao', array(), '');
        if (!$gonggao_id) {
            $gonggao_id = ternary($gonggao['id'], '');
        }
        if (!isset($gonggao['city_id']) || trim($gonggao['city_id']) == '') {
            $this->redirect_message(U('Encyclopedias/substationNoticeEdit', array('gonggao_id' => $gonggao_id)), array('error' => base64_encode('请选择区域!')));
        }
        if (!isset($gonggao['content']) || trim($gonggao['content']) == '') {
            $this->redirect_message(U('Encyclopedias/substationNoticeEdit', array('gonggao_id' => $gonggao_id)), array('error' => base64_encode('内容不能为空!')));
        }
        if (!isset($gonggao['create_time']) || trim($gonggao['create_time']) == '') {
            $this->redirect_message(U('Encyclopedias/substationNoticeEdit', array('gonggao_id' => $gonggao_id)), array('error' => base64_encode('公告时间不能为空!')));
        }

        $data = array(
            'city_id' => ternary($gonggao['city_id'], ''),
            'content' => htmlspecialchars(ternary($gonggao['content'], '')),
            'creat_time' => strtotime(ternary($gonggao['create_time'], date('Y-m-d'))),
        );
        $res = M('gonggao')->where(array('id' => $gonggao_id))->save($data);
        if ($res === false) {
            $this->redirect_message(U('Encyclopedias/substationNoticeEdit', array('gonggao_id' => $gonggao_id)), array('error' => base64_encode('公告更新失败!')));
        }
        $this->addOperationLogs("操作：编辑分站公告,id:{$gonggao_id}");
        redirect(U('Encyclopedias/substationNotice'));
    }

    /**
     * 分站公告删除
     */
    public function substationNoticeDelete() {
        $gonggao_id = I('get.gonggao_id', '', 'trim');
        if (!$gonggao_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '公告id不能为空！'));
        }
        $gonggao = M('gonggao');
        $gonggaoCount = $gonggao->where(array('id' => $gonggao_id))->count();
        if (!$gonggaoCount || $gonggaoCount <= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '你要删除的公告不存在！'));
        }
        $res = $gonggao->where(array('id' => $gonggao_id))->delete();
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '删除失败！'));
        }
        $this->addOperationLogs("操作：删除分站公告,id:{$gonggao_id}");
        $this->ajaxReturn(array('code' => 0));
    }

}
