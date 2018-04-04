<?php
/**
 * User: wzb
 * Date: 2015/9/6
 * Time: 13:41
 */
namespace Common\Org;

/**
 * Class MovieTicket
 * @package Common\Org
 */
class MovieTicket {

    // host
    protected $_host;

    // appid
    protected $_appId = 'tctest';

    // appkey
    protected $_appKey = 'TCTASVDLE3JZ81RZ8N';

    // 商家id
    protected $_partnerId;

    // 验证点id
    protected $_vid;

    //日志地址
    protected $_log = '/tmp/movieticker.log';

    protected $_debug = false;

    public function __construct() {
        if($this->_debug) {
            $this->_host = 'http://115.28.177.99:8090/Interface_YJQ/spIdexService.ashx';
        } else {
            $this->_host = 'http://115.28.177.99/Interface_YJQ/spIdexService.ashx';
        }
    }

    /**
     * 下发电子凭证
     * @param $option
     * @return bool|mixed
     */
    public function create($option) {

        $data = array(
            'action' => 'SendVoucherV4',
            'Appid' => $this->_appId,
            'spid' => $this->_partnerId,
            'themeId' => 29,
            'title' => $option['title'],
            'userNumber' => 1,
            'uniqueCode' => $option['id'],
            'ImageType' => 1,
            'denomination' => isset($option['price']) ? $option['price'] : 0,
            'amount' => isset($option['price']) ? $option['price'] : 0,
            'StartTime' => date('Y/m/d H:i:s', $option['begin_time']),
            'EndTime' => date('Y/m/d H:i:s', $option['expire_time']),
            'instruction' => isset($option['desc']) ? $option['desc'] : '',
            'RFType' => 3,
            'phone' => $option['mobile'],
            'IssuedWay' => 0,
            'out_trade_no' => $option['id'],
            'goodsIds' => 125
        );

        $data['sign'] = $this->_sign($data);
        $res = $this->_post($this->_host, $data);
        return $this->_getResponse($res);
    }

    /**
     * 批量创建凭证
     * @param $data
     * @return mixed
     */
    public function createAll($data) {
        foreach($data['coupon'] as $row) {
            $row['title'] = $data['title'];
            $row['desc'] = $data['desc'];
            $row['begin_time'] = $data['begin_time'];
            $row['mobile'] = $data['mobile'];
            $row['price'] = $data['price'];
           
            $res = $this->create($row);
            if($res['ErrCode'] != 0) {
                //记录日志
                file_put_contents($this->_log, $row['id'] . '创建失败' . "\r\n", FILE_APPEND);
            }
        }
        return true;
    }

    /**
     *  查询电子凭证
     * @param $id
     * @return bool|mixed
     */
    public function query($id) {
        $data = array(
            'action' => 'SelectRFIDInfo',
            'Appid' => $this->_appId,
            'uniqueCode' => $id,
            'vid' => $this->_vid
        );
        $data['sign'] = $this->_sign($data);
        $res = $this->_post($this->_host, $data);
        return $this->_getResponse($res);
    }

    /**
     * 验证电子凭证
     * @param $id
     * @return bool|mixed
     */
    public function verify($id) {
        $data = array(
            'action' => 'ValidateRFIDV3',
            'Appid' => $this->_appId,
            'uniquecode' => $id,
            'vid' => $this->_vid,
            'useNumber' => 1
        );
        $data['sign'] = $this->_sign($data);
        $res = $this->_post($this->_host, $data);
        return $this->_getResponse($res);
    }

    /**
     * 批量验证
     * @param $data
     * @return bool
     */
    public function verifyAll($data) {
        foreach($data as $row) {
            $res = $this->verify($row['id']);
            if($res['ErrCode'] != 0) {
                // 记录日志
                file_put_contents($this->_log, $row['id'] . '验证失败' . "\r\n", FILE_APPEND);
            }
        }
        return true;
    }

    /**
     * 作废电子凭证
     * @param $id
     * @return bool|mixed
     */
    public function invalid($id) {
        //首先获取到  identifyCode
        $result = $this->query($id);
        if($result['ErrCode'] == 0) {
            $data = array(
                'action' => 'StatisticsCancel',
                'Appid' => $this->_appId,
                'uniquecode' => $id,
                'identifyCode' => $result['items'][0]['IdentifyCode']
            );
            $data['sign'] = $this->_sign($data);
            $res = $this->_post($this->_host, $data);
            return $this->_getResponse($res);
        } else {
            file_put_contents($this->_log, $id . '查询失败' . "\r\n", FILE_APPEND);
            return false;
        }
    }

    /**
     * 批量作废凭证
     * @param $data
     * @return bool
     */
    public function invalidAll($data) {
        foreach($data as $row) {
            $res = $this->invalid($row['id']);
            if($res['ErrCode'] != 0) {
                //记录日志
                file_put_contents($this->_log, $row['id'] . '作废失败' . "\r\n", FILE_APPEND);
            }
        }
        return true;
    }

    /**
     * 获取流水信息
     * @param $stime
     * @param $etime
     * @return bool|mixed
     */
    public function getFlow($stime, $etime) {
        $data = array(
            'action' => 'SelectRFIDExchangeInfoByVidV2',
            'Appid' => $this->_appId,
            'startTime' => date('Y-m-d H:i:s', $stime),
            'endTime' => date('Y-m-d H:i:s', $etime),
            'vid' => $this->_vid
        );
        $data['sign'] = $this->_sign($data);
        $res = $this->_post($this->_host, $data);
        return $this->_getResponse($res);
    }

    /**
     * 签名
     * @param $data
     * @return string
     */
    protected function _sign($data) {
        ksort($data, SORT_NATURAL | SORT_FLAG_CASE);
        $str = '';
        foreach($data as $key=>$val) {
            $str .= $key . '=' . $val;
        }
        $str .= $this->_appKey;
        return strtolower(md5($str));
    }

    /**
     * post数据
     * @param string $url
     * @param array $data
     * @return array|mixed
     */
    protected function _post($url = '', $data = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept-Charset: utf-8"));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);

/*        $str = '';
        foreach($data as $k=>$v) {
            $str .= $k .'=' . $v . '&';
        }
        $str = substr($str, 0, -1);*/
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // 发送
        $result = curl_exec($ch);
        if (@curl_errno($ch)) {
            $result = array('error' => '错误提示：' . curl_error($ch));
        }
        return $result;
    }

    /**
     * 解析response
     * @param $res
     * @return bool|mixed
     */
    protected function _getResponse($res) {
        if($res) {
            //var_dump($res);
            file_put_contents($this->_log, $res . "\r\n", FILE_APPEND);
            $res = json_decode($res, true);
            return $res[0];
        } else {
            return false;
        }
    }

    /**
     * 设置partnerId
     * @param $id
     */
    public function setPartnerId($id) {
        $this->_partnerId = $id;
    }

    /**
     * 设置vid
     * @param $id
     */
    public function setVid($id) {
        $this->_vid = $id;
    }
}