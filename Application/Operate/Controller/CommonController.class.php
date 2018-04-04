<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-3-10
 * Time: 上午10:43
 */

namespace Operate\Controller;

use Common\Controller\CommonBusinessController;

/**
 * Class CommonController
 *
 * @package Operate\Controller
 */
class CommonController extends CommonBusinessController
{
    protected $_threadState;
    protected $_threads = array();

    /**
     *构造函数
     */
    public function __construct()
    {
        parent:: __construct();
        if (!IS_CLI) {
            send_http_status('404');
            exit;
        }
    }

    /**
     * 解析redis通道消息
     *
     * @param $msg
     *
     * @return bool|mixed
     */
    protected function _parseMsg($msg)
    {
        $data = json_decode($msg,true);
        if ($data) {
            return $data;
        } else {
            return false;
            //TODO 获取数据异常处理
        }
    }

    /**
     * 处理数据
     * @param $msg
     * @param $thread 是否开启多线程
     */
    protected function OpData($msg, $thread){
        //解析消息
        $data = $this->_parseMsg($msg);
        if($data!==false){
            $this->_threadState = $thread;
            $key = $data['key'];
            //获取KVStore数据
            $redis = new \Common\Org\phpRedis('connect');
            //$redis::$redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
            $this->__opInfo($redis,$key);

            //多线程同步
            if($this->_threadState && $this->_threads) {
                foreach ($this->_threads as $row) {
                    $row->join();
                }
            }
        }
    }

    /**
     * 多线程
     * @param $method
     * @param $param
     */
    protected function _opDataThread($method, $param) {
        $load   = new \Operate\Org\Load(new \Think\Think());
        $thread = new \Operate\Org\Threads($this, $method, $load, $param);
        $thread->start();
        $this->_threads[] = $thread;
    }

    /**
     * OSS处理
     * @param $file
     * @param $type
     */
    public function saveFileToOSS($file, $type) {
        $this->_saveFileToOSS($file, $type);
    }

    /**
     * log处理
     * @param $type
     * @param $topic
     * @param $contents
     */
    public function writeTOSLS($type, $topic, $contents) {
        $this->_writeTOSLS($type, $topic, $contents);
    }

    /**
     * ots处理
     * @param $method
     * @param $param
     */
    public function opDataToOts($method, $param) {
        $this->_opDataToOts($method, $param);
    }

    /**
     * search处理
     * @param $method
     * @param $data
     * @param $table
     */
    public function opDataToSearch($method,$data,$table) {
        $this->_opDataToSearch($method,$data,$table);
    }
    
     /**
     * mysql表单添加处理
     * @param $method
     * @param $data
     * @param $table
     */
    public function opDataToMysqlOrder($data) {
        $this->_opDataToMysqlOrder($data,false);
    }
    
    /**
     * 推送消息
     * @param $method
     * @param $data
     * @param $table
     */
    public function opDataPushMessage($method,$data) {
        $this->_opDataPushMessage($method,$data);
    }


} 