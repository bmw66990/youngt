<?php

/**
 * Created by PhpStorm.
 * User: zhoujz
 * Date: 15-3-13
 * Time: 上午11:42
 */

namespace Common\Org;

/**
 * Class Http
 *
 * @package Common\Org
 */
class ExpressQuery {

    const EXPRESS_QUERY_URL = 'http://www.kuaidi100.com/query';
    
    const IP_LOC_SINA_QUERY_URL = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php';

    /**
     * 构造函数
     */
    public function __construct() {
        
    }

    /**
     * 快递跟踪
     * @param type $type
     * @param type $express_no
     * @return boolean
     */
    public function express_query($type = '', $express_no = '') {
        $type = trim($type);
        $express_no = trim($express_no);
        if (!$type && !$express_no) {
            return false;
        }
        $get_data = array(
            'type' => $type,
            'postid' => $express_no,
        );
        $http = new \Common\Org\Http();
        $data = $http->get(self::EXPRESS_QUERY_URL, $get_data);
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        return $data;
    }
    
    /**
     * 根据ip获取城市名称
     * @param type $queryIP
     * @return string
     */
    function getIPLoc_sina($queryIP='') {
        
        if(!trim($queryIP)){
             return '';
        }
        if(function_exists('is_ip')){
            if(!is_ip($queryIP)){
                return '';
            }
        }
        
        $get_data = array(
            'format' => 'json',
            'ip' => trim($queryIP),
        );
        
        $http = new \Common\Org\Http();
        $location = $http->get(self::IP_LOC_SINA_QUERY_URL, $get_data);
        if (is_string($location)) {
            $location = json_decode($location, true);
        }
        if (isset($location['ret']) && $location['ret'] != -1) {
            if (isset($location['desc']) && trim($location['desc'])) {
                return $location['desc'];
            }
            return "{$location['country']} {$location['province']} {$location['city']} {$location['district']} {$location['isp']}";
        }
        return '';
    }

}
