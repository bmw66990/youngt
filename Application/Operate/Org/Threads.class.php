<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-04-02
 * Time: 09:25
 */
namespace Operate\Org;

class Threads extends \Thread {

    public function __construct($controller, $method, $loader, $param) {
        $this->controller = $controller;
        $this->method = $method;
        $this->loader = $loader;
        $this->param = $param;
    }

    public function run() {
        $this->loader->register();
        if(is_object($this->controller)) {
            call_user_func_array(array($this->controller, $this->method), $this->param);
        }
    }
}