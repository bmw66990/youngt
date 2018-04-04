<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-08-07
 * Time: 09:51
 */

namespace Admin\Controller;

class WorkbenchController extends CommonController {

    /**
     * 最新动态
     */
    public function newDynamic() {
        $task_log_model   = M('workbench_task_log');
        $group_user_model = M('workbench_group_user_relation');
        $user_model = M('user');
        $group_model = M('workbench_group');
        $task_model = M('workbench_task');

        //获取当前用户所在小组id
        $workbench_group_id_arr = $group_user_model->field('workbench_group_id')->where(array('user_id' => $this->uid))->select();

        if ($workbench_group_id_arr) {
            foreach ($workbench_group_id_arr as $val) {
                $workbench_group_id[] = $val['workbench_group_id'];
            }
            unset($val);
            if (isset($workbench_group_id) && $workbench_group_id) {
                $where = array(
                    'workbench_group_id' => array('in', $workbench_group_id),
                );
            }

            //获取有关小组的所有记录总记录数
            if (isset($where) && isset($where['workbench_group_id'])) {
                $count = $task_log_model->where($where)->count('id');
                $page  = $this->pages($count, $this->reqnum);
                $limit = $page->firstRow . ',' . $page->listRows;
                $order = 'create_time desc,id desc';
                $field = 'admin_id,remark_content,workbench_task_id,workbench_group_id,create_time';
                $data  = $task_log_model->field($field)->where($where)->order($order)->limit($limit)->select();
                if ($data) {
                    foreach ($data as $val) {
                        $group_ids[$val['workbench_group_id']] = $val['workbench_group_id'];
                        $user_ids[$val['admin_id']]            = $val['admin_id'];
                        $task_ids[$val['workbench_task_id']]   = $val['workbench_task_id'];
                    }
                    unset($val);
                    $user_info = $group_info = $task_info = array();
                    if (isset($group_ids) && $group_ids) {
                        $group_info = $group_model->field('id,username')->index('id')->where(array('id' => array('in', array_keys($group_ids))))->select();
                    }
                    if (isset($user_ids) && $user_ids) {
                        $user_info = $user_model->field('id,realname')->index('id')->where(array('manager' => 'Y', 'id' => array('in', array_keys($user_ids))))->select();
                    }
                    if (isset($task_ids) && $task_ids) {
                        $task_info = $task_model->field('id,title')->index('id')->where(array('id' => array('in', array_keys($task_ids))))->select();
                    }
                    foreach ($data as &$val) {
                        $val['admin_name'] = $user_info[$val['admin_id']]['realname'];
                        $val['task_title'] = $task_info[$val['workbench_task_id']]['title'];
                        $val['group_name'] = $group_info[$val['workbench_group_id']]['username'];
                    }
                }

                $assign = array(
                    'pages' => $page->show(),
                    'data'  => $data,
                );
                $this->assign($assign);
            }
        }
        $this->display();
    }

    /**
     * 我的任务
     */
    public function myTask() {
        $task_user_model = M('workbench_task_user_relation');
        $group_model = M('workbench_group');
        $task_model = M('workbench_task');
        $task_id_data = $task_user_model->field('workbench_task_id')->where(array('user_id'=>$this->uid))->select();
        $status = I('get.status',1,'intval');
        if($task_id_data){
            foreach ($task_id_data as $val){
                $task_ids[] = $val['workbench_task_id'];
            }
            unset($val);
            if(isset($task_ids) && $task_ids){
                $wheres['id'] =  array('in',$task_ids);
            }
        }
        if(isset($wheres['id'])){
            $wheres['admin_id'] = $this->uid;
            $wheres['_logic'] = 'or';
            $where['_complex'] = $wheres;
        }else{
            $where['admin_id'] = $this->uid;
        }
        $count = $task_model->where($where)->count('id');
        $page  = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $order = 'create_time desc,id desc';
        $field = 'id,workbench_group_id,admin_id,create_time,title';
        $data  = $task_model->field($field)->where($where)->order($order)->limit($limit)->select();
        if($data){
            foreach ($data as $val){
                $group_ids[$val['workbench_group_id']] = $val['workbench_group_id'];
                $work_task_ids[$val['id']] = $val['id'];
            }
            unset($val);
            $group_info = $task_user_res = $task_user_info = array();
            if(isset($group_ids) && $group_ids){
                $group_info = $group_model->field('id,username')->index('id')->where(array('id'=>array('in',array_keys($group_ids))))->select();
            }
            if(isset($work_task_ids) && $work_task_ids){
                $task_user_res = $task_user_model->alias('w_t')->join('user u ON u.id = w_t.user_id')->field('w_t.workbench_task_id as id,w_t.user_id as user_id,w_t.status as status,u.realname as name,u.avatar')->where(array('w_t.workbench_task_id'=>array('in',array_keys($work_task_ids))))->select();
            }
            foreach ($task_user_res as $k => $v) {
                    $task_user_info[$v['id']][$v['user_id']] = $v;
            }
            foreach ($data as $val){
                $val['group_name'] = $group_info[$val['workbench_group_id']]['username'];
                $val['user']       = $task_user_info[$val['id']];
                if(isset($task_user_info[$val['id']][$this->uid]) && $task_user_info[$val['id']][$this->uid]['status'] == $status){
                    $val['status'] = $task_user_info[$val['id']][$this->user['id']]['status'];
                    $newData[date('Y-m-d',$val['create_time'])][]=$val;
                }else{
                    if($val['admin_id'] == $this->uid && isset($task_user_info[$val['id']][$this->uid]) === false){
                        $newData[date('Y-m-d',$val['create_time'])][]=$val;
                    }
                }
            }
        }
        $assign = array(
            'pages' => $page->show(),
            'data'  => isset($newData) ? $newData : array(),
            'status'=> $status
        );
        $this->assign($assign);
        $this->display();
    }

    /**
     * 任务详情
     */
    public function taskDetail(){
        $task_model = M('workbench_task');
        $group_model = M('workbench_group');
        $task_log_model   = M('workbench_task_log');
        $task_user_model = M('workbench_task_user_relation');
        $id = I('get.id',0,'intval');
        if($id){
            $task_info = $task_model->find($id);
            $task_log_data = $task_user_data = array();
            if($task_info){
                $group_name = $group_model->getFieldById($task_info['workbench_group_id'],'username');
                $task_log_data  = $task_log_model->alias('t_l')->join('user u ON u.id = t_l.admin_id')->field('u.realname as name,u.avatar,t_l.*')->where(array('t_l.workbench_task_id'=>$id,'t_l.status'=>3))->select();
                $task_user_data = $task_user_model->alias('t_u')->join('user u ON u.id = t_u.user_id')->field('u.realname as name,t_u.user_id as user_id,u.avatar,t_u.status as status')->where(array('t_u.workbench_task_id'=>$id))->select();
            }
            foreach ($task_user_data as $val){
                if($val['user_id'] == $this->uid){
                    $task_info['status'] = $val['status'];
                    break;
                }else{
                    $task_info['status'] = 3;
                }
            }
            $assign = array(
                'task_info'=>$task_info,
                'task_log_data'=>$task_log_data,
                'task_user_data'=>$task_user_data,
                'group_name'=> isset($group_name) ? $group_name : '未设置小组名称',
                'uid' =>$this->uid,
                'user_info' => $this->user
            );
            $this->assign($assign);
        }
        $this->display();
    }

    /**
     * 异步修改任务信息
     */
    public function ajaxUpdateTask(){
        if(IS_AJAX){
            $data = array(
                'id' => I('post.id',0,'intval'),
                'title'=> I('post.title',''),
                'end_time' => strtotime(I('post.end_time')),
                'update_time'=> time(),
                'desc' => I('post.desc')
            );
            $task_info = M('workbench_task')->find($data['id']);
            if($data['id']){
                M('workbench_task')->save($data);
                //添加动态日志
                $this->_addTaskLog(array(
                    'workbench_task_id' => $data['id'],
                    'workbench_group_id' => $task_info['workbench_group_id'],
                    'remark_content' =>  '修改任务名称：将“'.$task_info['title'].'”修改为“'.$data['title'].'”',
                    'status' => 2
                ));
                $this->ajaxReturn(getPromptMessage('','success',1));
            }else{
                $this->ajaxReturn(getPromptMessage('请求非法'));
            }
        }else{
            $this->ajaxReturn(getPromptMessage('请求非法'));
        }
    }

    /**
     * ajax 发表评论
     */
    public function ajaxComment(){
        if(IS_AJAX){
            //添加动态日志
            $this->_addTaskLog(array(
                'workbench_task_id' => I('post.id',0,'intval'),
                'workbench_group_id' => I('post.group_id',0,'intval'),
                'remark_content' =>  I('post.remark_content'),
                'status' => 3
            ));
            $this->ajaxReturn(getPromptMessage('','success',1));
        }else{
            $this->ajaxReturn(getPromptMessage('请求非法'));
        }
    }

    /**
     * ajax 上传附件
     */
    public function uploadFile(){
        $file = $_FILES['file'];
        if(isset($file['error']) && $file['error'] == 4){
            $this->error('请选择附件后在上传');
        }
        list($file_name,$ext) = explode('.',$file['name']);
        $save_name = time().rand(1000,9999).''.$ext;
        $save_path = './Uploads/'.$save_name;
        if(move_uploaded_file($file['tmp_name'],$save_path)){
            $dir =  'file/' . date('Y') . '/' . date('md');
            $oss_data = array(
                'savename' => $save_name,
                'savepath' => './Uploads',
                'newpath' => $dir
            );
            $result = $this->_saveFileToOSS($oss_data, 'file');
            if($result === false ){
                $this->error('上传阿里云OSS失败');
            }
            $data = array(
                'workbench_task_id' => I('post.id',0,'intval'),
                'workbench_group_id' => I('post.group_id',0,'intval'),
                'remark_content' =>  $file['name'],
                'file_url' => $dir.'/'.$save_name,
                'file_size'=>$file['size'],
                'status' => 1
            );
            $this->_addTaskLog($data);
            ob_clean();
            $this->success('上传成功');
        }else{
            $this->error('上传失败');
        }
    }

    /**
     * 获取分组其他成员
     */
    public function addPerse(){
        $id = I('get.id',0,'intval');
        $task_info = M('workbench_task')->find($id);
        if($id && $task_info){
            $task_user = M('workbench_task_user_relation')->index('user_id')->field('user_id')->where(array('workbench_task_id'=>$id))->select();;
            $task_user_ids = array_keys($task_user);
            $group_user = M('workbench_group_user_relation')->index('user_id')->field('user_id')->where(array('workbench_group_id'=>$task_info['workbench_group_id']))->select();;
            $group_user_ids = array_keys($group_user);
            $user_ids = array_diff($group_user_ids,$task_user_ids);
            if($user_ids){
                $data = M('user')->field('id,realname,avatar')->where(array('id'=>array('in',$user_ids)))->select();
                $this->assign('id',$id);
                $this->assign('data',$data);
            }else{
                $this->assign('error','该任务已经将该组人员全部添加，无可添加人员');
            }
        }else{
            $this->assign('error','该任务存在，无法添加成员');
        }
        $this->display();
    }

    /**
     * 任务添加人员
     */
    public function ajaxAddTaskPerse(){
        $id = I('post.id',0,'intval');
        $task_info = M('workbench_task')->find($id);
        $user_ids = I('post.user_ids');
        if($id && $task_info && $user_ids){
            $data = array();
            if(strpos('-',$user_ids)){
                $user_id_arr = explode('-',$user_ids);
                foreach($user_id_arr as $val){
                    $data[] = array(
                        'workbench_group_id' => $task_info['workbench_group_id'],
                        'user_id'            => $val,
                        'workbench_task_id'  => $id,
                        'create_time'        => time()
                    );
                }
            }else{
                $data[] = array(
                    'workbench_group_id' => $task_info['workbench_group_id'],
                    'user_id'            => $user_ids,
                    'workbench_task_id'  => $id,
                    'create_time'        => time()
                );
            }
            $res = M('workbench_task_user_relation')->addAll($data);
            if($res){
                $this->ajaxReturn(getPromptMessage('','success',1));
            }else{
                $this->ajaxReturn(getPromptMessage('该任务存在，无法添加成员'));
            }
        }else{
            $this->ajaxReturn(getPromptMessage('该任务存在，无法添加成员'));
        }
    }

    /**
     * 日志回复
     */
    public function replyComment(){
        $id = I('get.id',0,'intval');
        $log_info = M('workbench_task_log')->find($id);
        $log_admin_info = M('user')->field('realname,avatar')->find($log_info['admin_id']);
        $log_data = array_merge($log_info,$log_admin_info);
        if($id && $log_info){
            $assign = array(
                'log_data'=>$log_data,
                'user_info' =>$this->user,
            );
            $this->assign($assign);
        }else{
            $this->assign('error','该评论不存在无法回复');
        }
        $this->display();
    }

    /**
     * 回复评论
     */
    public function ajaxReplyComment(){
        $id = I('post.id',0,'intval');
        $log_info = M('workbench_task_log')->find($id);
        $log_info['realname'] = M('user')->getFieldById($log_info['admin_id'],'realname');
        if(IS_AJAX && $id && $log_info){
            //添加动态日志
            $this->_addTaskLog(array(
                'workbench_task_id' => $log_info['workbench_task_id'],
                'workbench_group_id' => $log_info['workbench_group_id'],
                'remark_content' =>  "回复{$log_info['realname']}:".I('post.remark_content'),
                'status' => 3
            ));
            $this->ajaxReturn(getPromptMessage('','success',1));
        }else{
            $this->ajaxReturn(getPromptMessage('非法请求'));
        }
    }

    /**
     * 删除日志
     */
    public function ajaxDelLog(){
        $id = I('post.id',0,'intval');
        $task_log_info = M('workbench_task_log')->find($id);
        if(IS_AJAX && $id && $task_log_info){
            $res = M('workbench_task_log')->delete($id);
            if($res){
                $this->ajaxReturn(getPromptMessage('','success',1));
            }else{
                $this->ajaxReturn(getPromptMessage('删除失败'));
            }
        }else{
            $this->ajaxReturn(getPromptMessage('非法请求'));
        }
    }

    /**
     * 任务状态处理
     */
    public function ajaxTaskRecycle(){
        $id = I('post.id',0,'intval');
        $status = I('post.status',3,'intval');
        $task_info = M('workbench_task')->find($id);
        $task_user_info = M('workbench_task_user_relation')->where(array('workbench_task_id'=>$id,'user_id'=>$this->uid))->find();
        if(IS_AJAX && $id && $task_user_info){
            $res = M('workbench_task_user_relation')->save(array('id'=>$task_user_info['id'],'status'=>$status));
            if($res){
                if($status == 2){
                    $content = $this->user['realname'].'将任务：“'.$task_info['title'].'"已完成';
                }else{
                    $content = $this->user['realname'].'将任务：“'.$task_info['title'].'"已归档';
                }
                $this->_addTaskLog(array(
                    'workbench_task_id' => $id,
                    'workbench_group_id' => $task_info['workbench_group_id'],
                    'remark_content' => $content,
                    'status' => 2
                ));
                $this->ajaxReturn(getPromptMessage('','success',1));
            }else{
                $this->ajaxReturn(getPromptMessage('操作失败'));
            }
        }else{
            if($task_info['admin_id'] == $this->uid){
                $this->ajaxReturn(getPromptMessage('您创建小组的小组，您个人不在小组内，无法操作'));
            }else{
                $this->ajaxReturn(getPromptMessage('非法请求'));
            }
        }
    }

    /**
     * 我的小组
     */
    public function myGroup() {
        $map = array(
            'm.status' => array('gt', 0),
        );

        $uid = $this->_getUserId();
        $map['_string'] = "(m.admin_id={$uid} or a.user_id={$uid})";
        $data = M('workbench_group')->alias('m')
                ->field('m.*')
                ->where($map)
                ->join('workbench_group_user_relation as a on m.id=a.workbench_group_id')
                ->group('m.id')
                ->order('m.id desc')
                ->select();

        //echo M('workbench_group')->_sql();

        if($data) {
            if(function_exists('array_column')) {
                $gids = array_column($data, 'id');
            } else {
                $gids = array();
                foreach($data as $row) {
                    $gids[] = $row['id'];
                }
            }
            $where = array(
                'workbench_group_id' => array('IN', $gids)
            );
            $nums = M('workbench_group_user_relation')->where($where)->group('workbench_group_id')->order('NULL')->getField('workbench_group_id,count(*) num');

            // 重组置顶小组和普通小组
            $topData = array();
            foreach ($data as $key => &$row) {
                $row['num'] = ternary($nums[$row['id']], 0);
                if ($row['status'] == 2) {
                    $topData[] = $row;
                    unset($data[$key]);
                }
            }
            unset($row);
            $this->assign('topData', $topData);
        }

        $this->assign('data', $data);
        $this->assign('uid', $uid);
        $this->display();
    }

    /**
     * 创建小组
     */
    public function addGroup() {
        if (!IS_POST) {
            $this->display();
        } else {
            $data = array(
                'username' => I('post.username'),
                'status' => 1,
                'admin_id' => $this->_getUserId(),
                'create_time' => time()
            );

            $gid = M('workbench_group')->add($data);
            if ($gid) {
                // 添加关系表数据,默认添加创建者
                $user = I('post.user');
                $relationData = array();
                foreach ($user as $row) {
                    if ($row && intval($row) && $row != $this->_getUserId()) {
                        $relationData[] = array(
                            'workbench_group_id' => $gid,
                            'user_id' => $row,
                            'create_time' => time()
                        );
                    }
                }
                $relationData[] = array(
                    'workbench_group_id' => $gid,
                    'user_id' => $this->_getUserId(),
                    'create_time' => time()
                );
                $res = M('workbench_group_user_relation')->addAll($relationData);
                if ($res) {
                    $this->success('小组添加成功');
                } else {
                    M('workbench_group')->delete($gid);
                    $this->error('小组添加失败');
                }
            } else {
                $this->error('小组添加失败！');
            }
        }
    }

    /**
     * 选择用户
     */
    public function selectUser() {
        $gid = I('get.gid', 0, 'intval');
        if($gid) {
            $data = $this->_getGroupUser($gid);
        } else {
            $data = $this->_getUserList();
        }
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 设置小组状态
     */
    public function setGroupStatus() {
        if(IS_AJAX) {
            $st = I('get.st', 1, 'intval');
            $id = I('get.id', 0, 'intval');

            if(!in_array($st, array(1, 2))) {
                $this->error('非法参数');
            }

            $group = M('workbench_group')->find($id);
            if(!$group) {
                $this->error('小组不存在！');
            }

            if($group['admin_id'] != $this->_getUserId()) {
                $this->error('此小组不是你创建，无法执行此操作！');
            }

            $res = M('workbench_group')->where('id=' . $id)->setField('status', $st);
            if($res) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 退出小组
     */
    public function exitGroup() {
        $this->_checkblank('gid');
        $gid = I('get.gid', 0, 'intval');
        $group = M('workbench_group')->find($gid);

        if(!$group) {
            $this->error('小组不存在');
        }

        if($group['admin_id'] == $this->_getUserId()) {
            $this->error('你是小组创建者，无法退出！');
        }

        //退出小组
        $map = array(
            'workbench_group_id' => $gid,
            'user_id' => $this->_getUserId()
        );

        $res = M('workbench_group_user_relation')->where($map)->delete();
        if($res) {
            $this->success('退出小组成功！');
        } else {
            $this->error('退出小组失败！');
        }
    }

    /**
     * 获取小组任务的筛选条件
     * @return array
     */
    protected function _getGroupTaskWhere() {
        $map = array(
            'a.workbench_group_id' => I('get.gid', 0, 'intval')
        );

        // 成员检索
        $uid = I('get.uid', 0, 'intval');
        if($uid) {
            $map['a.user_id'] = $uid;
            $searchValue['uid'] = $uid;
        }

        // 类型检索 [1分配自己的任务  2自己创建的任务]
        $type = I('get.type', 0, 'intval');
        $cuid = $this->_getUserId();
        switch($type) {
            case 1:
                $map['a.user_id'] = $cuid;
                break;
            case 2:
                $map['m.admin_id'] = $cuid;
                break;
            default:
                //$map['_string'] = "(a.user_id={$cuid} or m.admin_id={$cuid})";
        }

        $searchValue['type'] = $type;

        $state = I('get.state', 'done', 'trim');

        switch($state) {
            case 'done':
                $map['m.status'] = 1;
                break;
            case 'expire':
                $map['m.status'] = 1;
                $map['m.end_time'] = array('gt', time());
                break;
            case 'complete':
                $map['m.status'] = 2;
                break;
        }
        $searchValue['state'] = $state;

        $this->assign('searchValue', $searchValue);
        return $map;
    }

    /**
     * 小组任务
     */
    public function groupTask() {
        $this->_checkblank('gid');
        $gid = I('get.gid', 0, 'intval');
        $group = M('workbench_group')->find($gid);

        $model = M('workbench_task');
        $map = $this->_getGroupTaskWhere();

/*        $count = $model->alias('m')
                        ->field('m.id')
                        ->where($map)
                        ->join('workbench_task_user_relation as a on m.id=a.workbench_task_id')
                        ->group('m.id')
                        ->order('NULL')
                        ->select();*/
        $count = $model->alias('m')
                ->where($map)
                ->join('workbench_task_user_relation as a on m.id=a.workbench_task_id')
                ->count();

        $data = array();
        if($count) {
            $page = $this->pages($count, $this->reqnum);
            $limit = $page->firstRow . ',' . $page->listRows;
            $list = $model->alias('m')
                            ->where($map)
                            ->field('m.*,a.user_id')
                            ->join('workbench_task_user_relation as a on m.id=a.workbench_task_id')
                            ->order('m.id desc')
                            ->limit($limit)
                            ->select();

            if($list) {
                // 获取任务对应的用户
                foreach($list as $k=>$row) {
                    if(array_key_exists('user', $data[$row['id']])) {
                        array_push($data[$row['id']]['user'], $row['user_id']);
                    } else {
                        $data[$row['id']] = $row;
                        $data[$row['id']]['user'] = array($row['user_id']);
                    }
                    unset($list[$k]);
                }
            }
            $this->assign('pages', $page->show());
        }

        $userList = $this->_getGroupUser($gid);
        $this->assign('userList', $userList);
        $this->assign('data', $data);
        $this->assign('groupInfo', $group);
        $this->assign('gid', $gid);
        $this->display();
    }

    /**
     * 添加任务
     */
    public function addTask() {
        $this->_checkblank('gid');
        $gid = I('param.gid', 0, 'intval');
        $group = M('workbench_group')->find($gid);
        if(!$group) {
            $this->error('小组不存在！');
        }
        if(!IS_POST) {
            $this->assign('gid', $gid);
            $this->display();
        } else {
            $data = array(
                'title' => I('post.title'),
                'status' => 1,
                'workbench_group_id' => $gid,
                'admin_id' => $this->_getUserId(),
                'create_time' => time()
            );

            $tid = M('workbench_task')->add($data);
            if($tid) {
                // 添加关系表数据
                $user = I('post.user');
                $relationData = array();
                foreach($user as $row) {
                    if($row && intval($row)) {
                        $relationData[] = array(
                            'workbench_group_id' => $gid,
                            'user_id'            => $row,
                            'workbench_task_id'  => $tid,
                            'create_time'        => time()
                        );
                    }
                }
                $res = M('workbench_task_user_relation')->addAll($relationData);
                if($res) {
                    $content = "{$this->user['realname']} 添加了任务[{$data['title']}]向你所在的[{$group['username']}]组";
                    $this->sendMessage($content,$gid);
                    //添加动态日志
                    $this->_addTaskLog(array(
                        'workbench_task_id' => $tid,
                        'workbench_group_id' => $gid,
                        'remark_content' => '新建任务',
                        'status' => 2
                    ));

                    $this->success('任务添加成功');
                } else {
                    M('workbench_task')->delete($tid);
                    $this->error('任务添加失败');
                }
            } else {
                $this->error('任务添加失败！');
            }
        }
    }

    /**
     * 添加任务动态信息
     * @param $data
     */
    protected function _addTaskLog($data) {
        $data['admin_id'] = $this->_getUserId();
        $data['create_time'] = time();
        M('workbench_task_log')->add($data);
    }

    /**
     * 获取管理员列表
     * @return mixed
     */
    protected function _getUserList() {
        $map = array(
            'manager' => 'Y'
        );
        //$data = M('user')->where($map)->getField('id,realname', true);
        $data = M('user')->where($map)->field('id,avatar,realname')->select();
        return $data;
    }

    /**
     * 获取小组所有用户
     * @param $gid
     * @return array
     */
    protected function _getGroupUser($gid) {
        $map = array(
            'workbench_group_id' => $gid
        );
        $data = M('workbench_group_user_relation')->where($map)->getField('user_id', true);
        $result = array();
        if($data) {
            $where = array(
                'id' => array('IN', array_unique($data))
            );
            $result = M('user')->index('id')->field('id,avatar,realname')->where($where)->select();
        }
        return $result;
    }
    
    /**
     * 分享文件
     */
    public function sharedFile() {
        $action = I('get.action', '', 'trim');

        $where = array(
            'workbench_task_log.status' => 1,
        );
        if ($action == 'myfiles') {
            $group_ids_res = M('workbench_group_user_relation')->where(array('user_id' => $this->uid))->field('workbench_group_id')->select();
            $group_ids = array();
            if ($group_ids_res) {
                foreach ($group_ids_res as $v) {
                    $group_ids[$v['workbench_group_id']] = $v['workbench_group_id'];
                }
            }
            if ($group_ids) {
                $where['workbench_task_log.workbench_group_id'] = array('in', array_keys($group_ids));
            }
        } else {
            $where['workbench_task_log.admin_id'] = $this->uid;
        }

        $count = 0;
        $list = array();
        $page_str = '';
        if (isset($where['workbench_task_log.admin_id']) || isset($where['workbench_task.workbench_group_id'])) {
            $count = M('workbench_task_log')->where($where)->count();
            $page = $this->pages($count, $this->reqnum);
            $page_str = $page->show();
            $field = array(
                'workbench_task_log.id' => 'id',
                'workbench_task_log.remark_content' => 'filename',
                'workbench_task_log.file_url' => 'file_url',
                'workbench_task_log.admin_id' => 'admin_id',
                'workbench_task_log.create_time' => 'create_time',
                'workbench_task_log.workbench_group_id' => 'workbench_group_id',
                'workbench_task_log.file_size' => 'file_size',
            );
         $list = M('workbench_task_log')->field($field)->where($where)->limit($page->firstRow . ',' . $page->listRows)
                ->select();
         if($list){
             $groups_ids = array();
             $user_ids = array(); 
             foreach($list as $v){
                 $groups_ids[$v['workbench_group_id']] = $v['workbench_group_id'];
                 $user_ids[$v['admin_id']] = $v['admin_id'];
             }
             unset($v);
             $user_res = M('user')->where(array('manager'=>'Y','id'=>array('in',  array_keys($user_ids))))->field('id,realname,username')->select();
             $groups_res = M('workbench_group')->where(array('id'=>array('in',  array_keys($groups_ids))))->field('id,username')->select();
             $user_info = $group_info = array();
             foreach(array('user_info'=>$user_res,'group_info'=>$groups_res) as $k=>$v){
                 foreach($v as $_v){
                     ${$k}[$_v['id']] = $_v;
                 }
             }
            unset($v);
             
             foreach($list as &$v){
                
                 // 用户名
                 $v['admin_name']=  ternary($user_info[$v['admin_id']], '');
                 
                 // 组名
                 $v['workbench_group_name']=  ternary($group_info[$v['workbench_group_id']], '');
                 
                 // 上传时间
                 $v['upload_time'] = date('Y-m-d',$v['create_time']);

             }
             unset($v);   
         }
        }

        $data = array(
            'action' => $action,
            'count' => $count,
            'list' => $list,
            'page' => $page_str,
        );
        $this->assign($data);
        $this->display();
    }

}
