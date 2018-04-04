<?php
/**
 * Created by PhpStorm.
 * User: zhoujz
 * Date: 15-3-11
 * Time: 下午4:14
 */


namespace Common\Org;

require_once dirname(__FILE__) . '/lib/SLS/Sls_Autoload.php';

/**
 * 阿里云日志处理类
 * Class log
 *
 * @package Common\Org
 */
class log
{

    /**
     *sls_region_endpoint设置
     */
    const ENDPOINT = 'http://cn-qingdao.sls.aliyuncs.com';

    /**
     *access_key_id
     */
    const ACCESSKEYID = 'js59bamtBN2vGZpk';

    /**
     *access_key
     */
    const ACCESSKEY = 'OUSvmvzIlzxUOaewlzj00WbGTQzCT0';

    /**
     * @var string 处理结果
     */
    public $result = '';

    /**
     * @var string 错误消息
     */
    public $error = '';

    /**
     * @var \Aliyun_Sls_Client|null
     */
    static $client = null;

    /**
     *构造函数
     */
    public function __construct()
    {
        self::$client = new \Aliyun_Sls_Client(self::ENDPOINT, self::ACCESSKEYID, self::ACCESSKEY);
    }


    /**
     * 发布日志
     * @param $contents 发布内容 字符串或者数组形式
     * @param $source   来源
     * @param $project  项目
     * @param $logstore 日志库
     * @param $topic    标记
     *
     * @return \Aliyun_Sls_Models_PutLogsResponse|bool  成功返回结果，失败返回false
     */
    public function putLogs($contents, $topic, $source, $project = 'youngtlogs', $logstore = 'api')
    {
        $logItem = new \Aliyun_Sls_Models_LogItem();
        $logItem->setTime(time());
        $logItem->setContents($contents);
        $logitems = array($logItem);

        $request = new \Aliyun_Sls_Models_PutLogsRequest($project, $logstore, $topic, $source, $logitems);

        try {
            $response = self::$client->putLogs($request);
            $responseHeader = $response->getHeader('_info');
            if ($responseHeader['http_code'] === 200) {
                $this->result = $response->getHeader('_info');

                return true;
            } else {
                return false;
            }
        } catch (\Aliyun_Sls_Exception $ex) {
            $this->error = $ex;

            return false;
        } catch (\Exception $ex) {
            $this->error = $ex;

            return false;
        }
    }
} 