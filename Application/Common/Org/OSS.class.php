<?php
/**
 * Created by PhpStorm.
 * User: zhoujz
 * Date: 15-3-13
 * Time: 上午11:42
 */


namespace Common\Org;

require_once dirname(__FILE__) . '/lib/OSS/sdk.class.php';

/**
 * Class OSS
 *
 * @package Common\Org
 */
class OSS
{

    /**
     *ACCESSKEYID
     */
    const ACCESSKEYID = 'js59bamtBN2vGZpk';

    /**
     *ACCESSSECRET
     */
    const ACCESSSECRET = 'OUSvmvzIlzxUOaewlzj00WbGTQzCT0';

    /**
     * 阿里云bucket
     * @var string
     */
    private $BUCKET = 'ytimg';

    /**
     * ALIOSS客户端
     * @var \ALIOSS|null
     */
    static $client = null;

    /**
     * 处理结果
     * @var string
     */
    public $result = '';

    /**
     * 错误信息
     * @var string
     */
    public $error = '';

    /**
     *构造函数
     */
    public function __construct()
    {
        self::$client = new \ALIOSS(self::ACCESSKEYID, self::ACCESSSECRET);
    }

    /**
     * 向阿里OSS保存文件
     * @param        $file      文件名称
     * @param        $source    文件资源
     * @param string $type      文件类型
     * @param string $dir       文件地址（阿里云上bucket下首目录）
     * @param string $bucket    阿里云bucket
     *
     * @return bool
     */
    public function save($file, $source, $type = 'img',$dir='static', $bucket = '')
    {
        try {
            if (empty($bucket)) {
                $bucket = $this->BUCKET;
            }
            $pathArr = pathinfo($file);

            $fileDir  = $pathArr['dirname'];
            $fileName = $pathArr['basename'];
            $fileExt  = $pathArr['extension'];

            if ($type === 'img') {
               // echo $fileExt;
                if (!in_array(strtolower($fileExt), array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'))) {
                    $this->error = '文件类型错误';

                    return false;
                }
            }
            //echo $dir.'/'.$fileDir . '/' . $fileName;
            //echo $source;
            $response = self::$client->upload_file_by_file($bucket, $dir.'/'.$fileDir . '/' . $fileName, $source);
            if ($response->status == 200) {
                return true;
            } else {
                return false;
            }

        } catch (\OSS_Exception $e) {

            $this->error = $e->getMessage();

            return false;

        }

    }

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return \call_user_func(array(self::$client, $method), $args[0]);
    }


}