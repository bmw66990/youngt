<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-3-10
 * Time: 上午10:42
 */
namespace Operate\Controller;

/**
 * Class RouteController
 *服务路由处理类
 * @package Operate\Controller
 */
class RouteController extends CommonController
{

    /**
     * @var string
     */
    static $channel = '';

    /**
     *初始化函数
     */
    public function _initialize()
    {
        //读取频道信息
        self::$channel = C('CHANNEL');
    }

    /**
     *接入函数
     */
    public function index()
    {
        set_time_limit(0);
        ini_set('default_socket_timeout', -1);

        //以长连接方式连接redis
        $redis = new \Common\Org\phpRedis('pconnect');
        //$redis::$redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);

        //获取频道列表并订阅
        if (is_array(self::$channel) && count(self::$channel) !== 0) {
            $redis::$redis->subscribe(array_keys(self::$channel), function ($redis, $channel, $msg){
                if (isset(self::$channel[$channel])) {
                    $cnf = self::$channel[$channel];
                    if($cnf['OPEN']===true){
                        $controller = A('Operate/'.$cnf['CONTROLLER']);

                        if($controller!==false){
                            $controller->{$cnf['ACTION']}($msg, $cnf['THREAD']);
                        }else{
                            //TODO::log处理
                        }
                    }
                    $controller = null;
                    $cnf = null;
                } else {
                    //TODO::log处理
                }
            });
        }
    }

} 