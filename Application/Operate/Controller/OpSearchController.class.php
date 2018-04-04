<?php
/**
 * 搜索服务处理类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-3-25
 * Time: 下午8:06
 */

namespace Operate\Controller;


/**
 * Class OpSearchController
 *
 * @package Operate\Controller
 */
class OpSearchController extends CommonController {

    /**
     * search可以使用的方法
     * @var array
     */
    private $methods = array('add','addAll','update','delete');

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
                $method = $info['method'];
                $data   = $info['data'];
                $table  = $info['table'];
                if(in_array($method,$this->methods)){
                    if($this->_threadState) {
                        $result = $this->_opDataThread('opDataToSearch', array($method, $data, $table));
                    } else {
                        $result = $this->_opDataToSearch($method,$data,$table);
                    }
                    if($result===false){
                        //TODO 操作失败处理
                    }
                }else{
                    //TODO 非法操作处理
                }
            }
            //TODO 处理数据
            $this->__opInfo($redis,$key);
        }else{
            return true;
        }
    }
}