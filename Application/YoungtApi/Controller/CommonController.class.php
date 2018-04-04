<?php
	
namespace YoungtApi\Controller;
use Common\Controller\CommonBusinessController;

    /**
     * Class CommonAction
     */
    class CommonController extends CommonBusinessController
    {

        /**
         * @var int 错误1级标志
         */
        private $msg = '';
        /**
         * @var int 错误标志
         */
        private $code = 0;
        /**
         * @var int 是否有下一页
         */
        private $data = '';
        /**
         * @var string 返回结果
         */
        private $result = '';

        /**
         * 返回值字段说明
         * @var string
         */
        private $remarks = '';

        /**
         * 帮助文档
         * @var string
         */
        protected $helpInfo = '';

        /**
         * 输出函数，如果启用debug,输出日志
         *
         * @param array  $result     输出数据
         * @param int    $errCode    错误code
         * @param string $proType    处理类型
         */
        public function outPut($result = null,  $errCode, $msg = '',$remarks='')
        {
            $this->setData(is_null($result)?'':$result);
            $this->remarks = $remarks;
            $this->setError($errCode, $msg);
            $this->setResult();
            ob_clean();
            echo json_encode($this->result);
            if (C('SER_LOG')) {
                $this->logOut($result);
            }
            exit;
        }

        /**
         * 设置输出结果
         *
         */
        private function setResult()
        {
            $this->result = array('code' => $this->code, 'msg' => $this->msg,  'data' => $this->data, 'remarks'=>$this->remarks);
        }

        /**
         * 设置数据
         *
         */
        private function setData($data)
        {
            //新增数据过滤转换   daipingshan 2015-04-14
            $data = $this->_checkData($data);
            $this->data = $data;
        }

        /**
         * 设置错误信息
         *
         * @param int    $error      错误code
         * @param string $proType    处理类型
         */
        private function setError($error = 0, $msg = '')
        {
            $this->code = $error;
            $this->msg     =  $msg;
        }

        /**
         * 日志输出
         *
         */
        protected function logOut($result)
        {
            \Think\Log::write('start-------------------------------------------start', \Think\Log::INFO);
            \Think\Log::write('访问页面：' . $_SERVER['PHP_SELF'], \Think\Log::INFO);
            \Think\Log::write('请求方法：' . $_SERVER['REQUEST_METHOD'], \Think\Log::INFO);
            \Think\Log::write('通信协议：' . $_SERVER['SERVER_PROTOCOL'], \Think\Log::INFO);
            \Think\Log::write('请求时间：' . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']), \Think\Log::INFO);
            \Think\Log::write('用户代理：' . $_SERVER['HTTP_USER_AGENT'], \Think\Log::INFO);
            \Think\Log::write('CONTENT_TYPE：' . $_SERVER['CONTENT_TYPE'], \Think\Log::INFO);
            \Think\Log::write('提交数据：', \Think\Log::INFO);
            foreach ($_REQUEST as $key => $value) {
                \Think\Log::write($key . "=" . $value, \Think\Log::INFO);
            }
            \Think\Log::write('输出结果：', \Think\Log::INFO);
            foreach ($result as $key => $value) {
                \Think\Log::write($key . "=" . $value, \Think\Log::INFO);
            }

            if (count($_FILES) != 0) {
                \Think\Log::write('提交文件：', \Think\Log::INFO);
                foreach ($_FILES as $key => $value) {
                    \Think\Log::write($key . "=" . $value['type'], \Think\Log::INFO);
                }
            }

            \Think\Log::write('end-----------------------------------------------end', \Think\Log::INFO);
        }

        /**
         * APi帮助文档
         */
        protected function _help(){
            if (isset($_GET['help'])) {
                header('Content-Type:text/html; charset=utf-8');
                echo '<html>';
                echo '<body>';
                echo '<pre>';
                echo $this->helpInfo;
                echo '</pre>';
                echo '</body>';
                echo '</html>';
                exit();
            }
        }
    }

?>