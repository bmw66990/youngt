<?php
/**
 * Created by PhpStorm.
 * User: runtoad
 * Date: 15-3-11
 * Time: 上午11:22
 */

return array(
    'CHANNEL' => array(
        'OTS'        => array( //OTS结构化数据
            'OPEN'       => true, //是否打开
            'THREAD'     => true,  //是否开启多线程
            'CONTROLLER' => 'OpOTS', //处理controller
            'ACTION'     => 'OpData' //处理action
        ),
        'LOG'        => array( //日志处理
            'OPEN'       => true,
            'THREAD'     => true,
            'CONTROLLER' => 'OpLOG',
            'ACTION'     => 'OpData'
        ),
        'SPIKE_TYPE' => array( //秒杀
            'OPEN'       => true,
            'THREAD'     => true,
            'CONTROLLER' => 'OpSpikeType',
            'ACTION'     => 'OpData'
        ),
        'OSS'        => array( //日志处理
            'OPEN'       => true,
            'THREAD'     => false,
            'CONTROLLER' => 'OpOSS',
            'ACTION'     => 'OpData'
        ),
        'search'     => array( //日志处理
            'OPEN'       => true,
            'THREAD'     => true,
            'CONTROLLER' => 'OpSearch',
            'ACTION'     => 'OpData'
        ),
        'MySql'      => array( //秒杀订单异步添加处理
            'OPEN'       => true,
            'THREAD'     => true,
            'CONTROLLER' => 'OpMySql',
            'ACTION'     => 'OpData'
        ),
         'PUSH_MESSAGE'      => array( //消息推送
            'OPEN'       => true,
            'THREAD'     => true,
            'CONTROLLER' => 'OpPushMessage',
            'ACTION'     => 'OpData'
        ),
    )
);