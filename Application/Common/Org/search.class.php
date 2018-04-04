<?php
/**
 * Created by PhpStorm.
 * User: runtoad
 * Date: 15-3-12
 * Time: 下午2:27
 */

namespace Common\Org;

require_once dirname(__FILE__) . '/lib/search/CloudsearchSearch.php';
require_once dirname(__FILE__) . '/lib/search/CloudsearchIndex.php';
require_once dirname(__FILE__) . '/lib/search/CloudsearchClient.php';
require_once dirname(__FILE__) . '/lib/search/CloudsearchDoc.php';
require_once dirname(__FILE__) . '/lib/search/CloudsearchAnalysis.php';
require_once dirname(__FILE__) . '/lib/search/CloudsearchSuggest.php';

/**
 * Class search
 *
 * @package Common\Org
 */
class search
{

    /**
     *ACCESSKEYID
     */
    const ACCESSKEYID = 'js59bamtBN2vGZpk';

    /**
     *SECRET
     */
    const SECRET = 'OUSvmvzIlzxUOaewlzj00WbGTQzCT0';

    /**
     *KEY_TYPE
     */
    const KEY_TYPE = 'aliyun';

    /**
     *应用名称
     */
    const APP_NAME = 'youngt';
    
    /**
     * 搜索下拉提示规则名称
     */
    const SUGGEST_NAME = 'ces';

    /**
     *访问地址
     */
    const HOST = 'http://opensearch-cn-hangzhou.aliyuncs.com';

    /**
     * @var \CloudsearchClient|null 客户端连接
     */
    static $client = null;
    
    /**
     * @var string  程序返回结果
     */
    public $result = '';

    /**
     * @var string  错误信息
     */
    public $error = '';

    /**
     * 构造函数
     */
    public function __construct()
    {
        self::$client = new\ CloudsearchClient(self::ACCESSKEYID, self::SECRET, array('host' => self::HOST,self::KEY_TYPE), self::KEY_TYPE);
    }

    /**
     * 向某个表中添加单条数据
     * @param $data json字符串，或者是数组
     * @param $table 被添加的表名
     *
     * @return string
     */
    public function add($data, $table)
    {
        if (is_array($data)) {

            $requestData = $this->__opDataForOne($data,'ADD');
            return $this->__opSearch('add',$requestData, $table);
        } else {
            $this->error = '参数错误';

            return false;
        }
    }

    /**
     * 向数据表中添加多条数据
     * @param $data 多条数据的二维数组
     * @param $table
     *
     * @return bool
     */
    public function addAll($data, $table)
    {
        if (is_array($data)) {
            $requestData = $this->__opDataForMuli($data,'ADD');
            return $this->__opSearch('add',$requestData, $table);
        } else {
            $this->error = '参数错误';

            return false;
        }
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function __opDataForOne($data,$method){

        $requestData[] = array(
            'cmd'       => $method,
            'timestamp' => time(),
            'fields'    => $data
        );

        return $requestData;
    }

    /**
     * @param $data
     *
     * @return array|bool
     */
    private function __opDataForMuli($data,$method){
        $requestData = array();
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $this->error = '参数错误';
                return false;
            }
            $requestData[] = array(
                'cmd'       => $method,
                'timestamp' => time(),
                'fields'    => $value
            );
        }

        return $requestData;
    }

    /**
     * 添加数据处理
     * @param $data
     * @param $table
     *
     * @return bool
     */
    private function __opSearch($method,$data, $table)
    {
        $doc          = new \CloudsearchDoc(self::APP_NAME, self::$client);
        $result       = $doc->{$method}($data, $table);
        $this->result = json_decode($result, true);
        if ($this->result['status'] == 'OK') {
            return true;
        } else {
            $this->error = $this->result['errors'];

            return false;
        }
    }

    /**
     * 更新某个表的数据
     * @param $data  数组，必须含有主键
     * @param $table 被修改的表名
     *
     * @return string
     */
    public function update($data, $table)
    {
        if (is_array($data)) {

            $requestData = $this->__opDataForOne($data,'UPDATE');
            return $this->__opSearch('update',$requestData, $table);
        } else {
            $this->error = '参数错误';

            return false;
        }
    }

    /**
     * 更新多条数据
     * @param $data 数组，必须含有主键
     * @param $table 被修改的表名
     *
     * @return bool
     */
    public function updateAll($data, $table)
    {
        if (is_array($data)) {
            $requestData = $this->__opDataForMuli($data,'UPDATE');
            return $this->__opSearch('update',$requestData, $table);
        } else {
            $this->error = '参数错误';

            return false;
        }
    }

    /**
     * 更新某个表的数据
     * @param $data  数组，必须含有主键
     * @param $table 被修改的表名
     *
     * @return string
     */
    public function delete($data, $table)
    {
        if (is_array($data)) {

            $requestData = $this->__opDataForOne($data,'DELETE');
            return $this->__opSearch('remove',$requestData, $table);
        } else {
            $this->error = '参数错误';

            return false;
        }
    }

    /**
     * 更新多条数据
     * @param $data 数组，必须含有主键
     * @param $table 被修改的表名
     *
     * @return bool
     */
    public function deleteAll($data, $table)
    {
        if (is_array($data)) {
            $requestData = $this->__opDataForMuli($data,'DELETE');
            return $this->__opSearch('remove',$requestData, $table);
        } else {
            $this->error = '参数错误';

            return false;
        }
    }

    /**
     * 搜索数据
     * @param $queryString  搜索字符串
     * @param $startHit 起始点
     * @param $hits 每页数量
     *
     * @return bool
     */
    public function search($queryString,$filter='',$sort=array(),$startHit,$hits=20){

        $search = new \CloudsearchSearch(self::$client);
        // 添加指定搜索的应用：
        $search->addIndex(self::APP_NAME);
        // 指定搜索的关键词，
        $search->setQueryString($queryString);
        
        // 添加过滤规则
        if(trim($filter)){
            $search->addFilter($filter);
        }
        
        // 添加排序字段
        if($sort && is_array($sort)){
            foreach($sort as $key=>$val){
                if(!trim($key)){
                    continue;
                }
                $sortChar = \CloudsearchSearch::SORT_INCREASE;
                if(trim($val) == '-' || strtolower(trim($val)) == 'desc'){
                    $sortChar = \CloudsearchSearch::SORT_DECREASE;
                }
                $search->addSort($key, $sortChar);
            }
        }

        //设置搜索偏移量
        $search->setStartHit($startHit);

        //设置每页记录数
        $search->setHits($hits);

        // 指定搜索返回的格式。
        $search->setFormat('json');
        // 返回搜索结果。
        $reuslt = $search->search();

        $this->result = json_decode($reuslt,true);
        //var_dump($this->result);
        if($this->result['status']=='OK'){
			return $this->result;
        }else{
            $this->error = $this->result['errors'];
            return false;
        }
    }
    
    /**
     * 搜索提示
     * @param type $query  关键字  返回行数
     * @param type $hit
     * @return type
     */
    public function searchKeyDropTip($query,$hit=10){
        $suggest = new \CloudsearchSuggest(self::$client);

        $suggest->setIndexName(self::APP_NAME);
        $suggest->setSuggestName(self::SUGGEST_NAME);
        $suggest->setHits($hit);
        $suggest->setQuery($query);
        
        $result = @json_decode($suggest->search(), true);
        if (!isset($result['errors'])) {
		if (isset($result['suggestions']) && !empty($result['suggestions'])) {
			return $result['suggestions'];
		}
	}
        return array();
        
    }
}