<?php

namespace Manage\Controller;

use Manage\Controller\CommonController;

/**
 * 百科公告模块
 * Class TeamController
 * @package Admin\Controller
 */
class EncyclopediasController extends CommonController {

    /**
     * 百科首页
     */
    public function qingtuanEncyclopedias() {

        // 获取青团分类
        $where = array(
            'show_plat' => array('in', array('all', 'manager'))
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
            $this->error('百科id不能为空!');
        }

        $encyclopedias_info = M('encyclopedias')->where(array('id' => $encyclopedias_id))->find();
        if (!$encyclopedias_info) {
            $this->error('该百科不存在!');
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
            $type_where = array('show_plat' => array('in', array('all', 'manage')));
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
}
