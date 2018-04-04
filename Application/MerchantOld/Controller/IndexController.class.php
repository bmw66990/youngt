<?php
namespace Merchant\Controller;
use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class IndexController extends CommonController {
   

    public function __construct() {
        parent::__construct();
    }
    
    public function index(){
       $this->redirect('Coupon/index');
    }

   
}