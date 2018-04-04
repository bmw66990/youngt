<?php

/**
 * Created by JetBrains PhpStorm.
 * User: runtoad
 * Date: 15-3-12
 * Time: 上午10:54
 * To change this template use File | Settings | File Templates.
 */

namespace Common\Model;

use Think\Model;

class CommonModel extends Model {

    /**
     * 记录错误信息
     * @var array
     */
    protected $errorInfo = array();

    /**
     * 获取错误信息
     * @return array
     */
    public function getErrorInfo() {
        return $this->errorInfo;
    }

    /**
     * 实例化 table 对象
     */
    public function getTab($table) {
        return M('table');
    }

    /**
     * 获取数据列表
     * @param $where
     * @param $order
     * @param string $limit
     * @param string $field
     * @return mixed
     */
    public function getList($where, $order = '', $limit = '', $field = '*') {
        if (empty($order)) {
            $order = $this->getPk() . ' DESC';
        }
        $data = $this->field($field)->where($where)->order($order)->limit($limit)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }

    /**
     * 获取数据总条数
     * @param $where
     * @return mixed
     */
    public function getTotal($where = '') {
        $count = $this->where($where)->count('id');
        if ($count === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $count;
    }



    /**
     * 获取单条数据信息
     * @param $id 主键id
     * @param null $field 字段
     * @return mixed
     */
    public function info($id, $field = null) {
        if (empty($id))
            return false;
        if (is_null($field)) {
            $vo = $this->find($id);
        } else {
            $vo = $this->field($field)->find($id);
        }
        if ($vo === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $vo;
    }

    /**
     * 新增数据
     * @return bool|string
     */
    public function insert() {
        if ($this->create() === false) {
            $this->errorInfo['info'] = $this->getError();
            return false;
        }
        if ($_POST['detail'] || $_POST['notice'] || $_POST['summary'] || $_POST['systemreview']) {
            $this->notice       = I('post.notice', '', false);
            $this->detail       = I('post.detail', '', false);
            $this->summary      = I('post.summary', '', false);
            $this->systemreview = I('post.systemreview', '', false);
        }
        if (($id = $this->add()) === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            return $id;
        }
    }

    /**
     * 更新数据
     * @return bool|string
     */
    public function update() {
        if ($this->create() === false) {
            $this->errorInfo['info'] = $this->getError();
            return false;
        }
        if ($_POST['detail'] || $_POST['notice'] || $_POST['summary'] || $_POST['systemreview']) {
            $this->notice       = I('post.notice', '', false);
            $this->detail       = I('post.detail', '', false);
            $this->summary      = I('post.summary', '', false);
            $this->systemreview = I('post.systemreview', '', false);
        }
        if ($this->save() === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取单条数据信息
     * @param $where
     * @param bool|null $field 字段
     * @return mixed
     */
    public function getDetail($where, $field = true) {
        if (empty($where))
            return false;
        $data = $this->where($where)->field($field)->find();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }

    /**
     * 通过微信公众平台发送coupon
     * @param $data
     * @param string $act
     */
    protected function _WeiXinSendCoupon($data, $act = 'buy') {
        $opend_id = M('weixin_user')->where('username="' . $data['user_id'].'"')->getField('openid');
        if ($opend_id) {
            $token_where = array('expire_time' => array('gt', time()), 'id' => 1);
            $access_token = M('token')->where($token_where)->getField('access_token');
            $WeiXin = new \Common\Org\WeiXin();
            if (!$access_token) {
                $access_token = $WeiXin->getAccessToken();
                if ($access_token !== false) {
                    $save_token = array('access_token' => $access_token, 'expire_time' => time() + 7200, 'id' => 1);
                    M('token')->save($save_token);
                }
            }
            if ($access_token) {
                $data['token_id'] = $access_token;
                $data['wxuser'] = $opend_id;
                if ($act == 'buy') {
                    $WeiXin->sendBuyCoupon($data);
                } else {
                    $WeiXin->sendVerifyCoupon($data);
                }
            } else {
                $this->_writeLog('access_token', 'ALERT', 'api');
            }
        }
    }


    /**
     * 自动表单处理
     * @access public
     * @param array $data 创建数据
     * @param string $type 创建类型
     * @return mixed
     */
    private function autoOperation(&$data,$type) {
        if(!empty($this->options['auto'])) {
            $_auto   =   $this->options['auto'];
            unset($this->options['auto']);
        }elseif(!empty($this->_auto)){
            $_auto   =   $this->_auto;
        }
        // 自动填充
        if(isset($_auto)) {
            $ignoreField = array();
            foreach ($_auto as $auto){
                if(in_array($auto[0], $ignoreField)) {
                    unset($data[$auto[0]]);
                    break;
                }
                // 填充因子定义格式
                // array('field','填充内容','填充条件','附加规则',[额外参数])
                if(empty($auto[2])) $auto[2] =  self::MODEL_INSERT; // 默认为新增的时候自动填充
                if( $type == $auto[2] || $auto[2] == self::MODEL_BOTH) {
                    if(empty($auto[3])) $auto[3] =  'string';
                    switch(trim($auto[3])) {
                        case 'function':    //  使用函数进行填充 字段的值作为参数
                        case 'callback': // 使用回调方法
                            $args = isset($auto[4])?(array)$auto[4]:array();
                            if(isset($data[$auto[0]])) {
                                array_unshift($args,$data[$auto[0]]);
                            }
                            if('function'==$auto[3]) {
                                $data[$auto[0]]  = call_user_func_array($auto[1], $args);
                            }else{
                                $data[$auto[0]]  =  call_user_func_array(array(&$this,$auto[1]), $args);
                            }
                            break;
                        case 'field':    // 用其它字段的值进行填充
                            $data[$auto[0]] = $data[$auto[1]];
                            break;
                        case 'ignore': // 为空忽略
                            if($auto[1]===$data[$auto[0]]) {
                                unset($data[$auto[0]]);
                                $ignoreField[] = $auto[0];
                            }
                            break;
                        case 'string':
                        default: // 默认作为字符串填充
                            $data[$auto[0]] = $auto[1];
                    }
                    if(isset($data[$auto[0]]) && false === $data[$auto[0]] )   unset($data[$auto[0]]);
                }
            }
        }
        return $data;
    }

}
