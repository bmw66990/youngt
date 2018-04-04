<?php
/**
 * 日志处理处理操作类
 * Created by PhpStorm.
 * User: runtoad
 * Date: 15-3-25
 * Time: 下午8:05
 */

namespace Operate\Controller;


class OpLOGController extends CommonController {

    /**
     * 递归处理方式，将数据向阿里SSL服务处理
     * @param $redis redis
     * @param $key  channel名称
     *
     * @return bool
     */
    public function __opInfo($redis,$key){
        $result = $redis::$redis->sCard($key);
        if($result!==0){
            $info = $redis::$redis->sPop($key);
            $info = json_decode($info,true);
            if($info){
                $contents = $info['contents'];
                $level   = $info['level'];
                $source  = $info['source'];

                if($this->_threadState) {
                    $result = $this->_opDataThread('writeTOSLS', array($level, $source, $contents));
                } else {
                    $result = $this->_writeTOSLS($level,$source,$contents);
                }
                if($result===false){
                    //TODO 操作失败处理
                }
            }
            //TODO 处理数据
            $this->__opInfo($redis,$key);
        }else{
            return true;
        }
    }
} 