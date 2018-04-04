<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-3-26
 * Time: 下午3:29
 */

namespace Operate\Controller;


/**
 * 阿里文件存储操作类
 *
 * @package Operate\Controller
 */

class OpOSSController extends CommonController
{

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
                if($this->_threadState) {
                    $res = $this->_opDataThread('saveFileToOSS', array($info['fileName'], $info['type']));
                } else {
                    $fileData = $info['fileName'];
                    $type     = $info['type'];
                    $res = $this->_saveFileToOSS($fileData,$type);
                }
            }
            //TODO 处理数据
            $this->__opInfo($redis, $key);
        } else {
            return true;
        }
    }
}