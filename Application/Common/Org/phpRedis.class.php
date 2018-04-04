<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-3-10
 * Time: 上午9:58
 */

namespace Common\Org;

/**
 * Class phpRedis redis基本操作类
 * @package Common\Org
 */
/**
 * Class phpRedis
 *
 * @package Common\Org
 */
class phpRedis {

    /**
     *redis服务器地址
     */
    const HOST = '22136812c65311e4.m.cnhza.kvstore.aliyuncs.com';
    //const HOST = '127.0.0.1';//本地测试

    /**
     * redis服务器端口
     */
    const PORT =  6379;
    
    /**
     * redis服务器用户名
     */
    const USER = '22136812c65311e4';
    
    /**
     * redis服务器密码
     */
    const PWD = 'youngtKV1';

    /**
     * @var phpRedis|null redis实例
     */
    static $redis = null;

    /**
     * @var string 错误信息
     */
    private $errMsg  = '';

    /**
     * @var string 连接类型  connect：普通连接 pconnect：长连接
     */
    private $contentType = 'connect';


    /**
     * 析构函数
     * @param string $contentType 连接类型 connect:短连接 pconnect:长连接
     */
    function __construct($connectType='connect'){
        $this->contentType  = $connectType==='pconnect'?$connectType:'connect';;
        self::$redis = new \Redis();
        $connect = $this->connect();
        if($connect===false){
            $this->errMsg = '连接失败！';
            //TODO 连接失败后的异常处理
        }
    }

    /**
     * redis连接
     * @return bool true：成功 false：失败
     */
    private function connect(){
        if (self::$redis->{$this->contentType}(self::HOST, self::PORT) == false) {
            $this->errMsg = self::$redis->getLastError();
            return false;
        }
        if(trim(self::USER) && trim(self::PWD) ){
            $this->auth();
        }
        return true;
    }

    /**
     * 验证
     * @return bool true：成功 false：失败
     */
    private function auth(){
        if (self::$redis->auth(self::USER . ":" . self::PWD) == false) {
            $this->errMsg = self::$redis->getLastError();
            return false;
        }

        return true;
    }

    /**
     *关闭redis
     */
    public function close(){
        self::$redis->close();
    }

    /**
     *析构redis
     */
    public function __destruct(){
        self::$redis->close();
    }

} 