<?php
/**
 * OTS结构化处理操作类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-3-10
 * Time: 上午10:50
 */

namespace Operate\Controller;

class OpPushMessageController extends CommonController {
    
    /**
     * 递归处理方式，将数据向阿里OSS服务处理
     *
     * @param $redis redis
     * @param $key   channel名称
     *
     * @return bool
     */
    public function __opInfo($redis, $key)
    {
        $result = $redis::$redis->sCard($key);
        if ($result !== 0) {
            $info = $redis::$redis->sPop($key);
            $info = json_decode($info, true);
            if ($info) {
                $method = $info['method'];
                $params = $info['params'];
                if($this->_threadState) {
                    $this->_opDataThread('opDataPushMessage', array($method,$params));
                } else {
                    $this->_opDataPushMessage($method,$params);
                }
            }
            //TODO 处理数据
            $this->__opInfo($redis, $key);
        } else {
            return true;
        }
    }
}