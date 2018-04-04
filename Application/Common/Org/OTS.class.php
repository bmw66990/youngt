<?php

/**
 * 结构化数据
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-3-10
 * Time: 上午10:51
 */

namespace Common\Org;

require_once __DIR__ . '/lib/OTS/OTSClient.class.php';

class OTS {

    const REQUESTURL = 'http://youngt.cn-hangzhou.ots.aliyuncs.com';
    const X_OTS_APIVERSION = '2014-08-08';
    const ACCESSKEYID = 'js59bamtBN2vGZpk';
    const ACCESSSECRET = 'OUSvmvzIlzxUOaewlzj00WbGTQzCT0';
    const INSTANCENAME = 'youngt';

    /**
     * 临时数据存储
     */
    const TMP_DATA_FILE = '/lib/OTS/data/save_tmp_data.db';

    /**
     * 数据类型
     * @var type 
     */
    private $columnType = array(
        'inf_min' => \ots\protocolbuffer\ColumnType::INF_MIN,
        'inf_max' => \ots\protocolbuffer\ColumnType::INF_MAX,
        'integer' => \ots\protocolbuffer\ColumnType::INTEGER,
        'int' => \ots\protocolbuffer\ColumnType::INTEGER,
        'string' => \ots\protocolbuffer\ColumnType::STRING,
        'boolean' => \ots\protocolbuffer\ColumnType::BOOLEAN,
        'bool' => \ots\protocolbuffer\ColumnType::BOOLEAN,
        'double' => \ots\protocolbuffer\ColumnType::DOUBLE,
        'binary' => \ots\protocolbuffer\ColumnType::BINARY,
    );

    /**
     * 返回数据的类型
     * @var type 
     */
    private $valueType = array(
        '2' => 'int',
        '3' => 'string',
        '4' => 'bool',
        '5' => 'double',
        '6' => 'binary',
    );

    /**
     * ots 客户端
     * @var type 
     */
    static $ots_client = null;

    /**
     * 构造函数
     */
    public function __construct() {
        self::$ots_client = new \OTSClient(self::REQUESTURL, self::ACCESSKEYID, self::ACCESSSECRET, self::INSTANCENAME, self::X_OTS_APIVERSION);
    }

    /**
     * 发送请求
     * @param type $operation OTS操作名称
     * @param type $request 发送请求body数据
     * @return type
     */
    private function __otsClientSend($operation = '', $request) {
        if (!self::$ots_client) {
            self::$ots_client = new \OTSClient(self::REQUESTURL, self::ACCESSKEYID, self::ACCESSSECRET, self::INSTANCENAME, self::X_OTS_APIVERSION);
        }
        return self::$ots_client->send($operation, $request);
    }

    /**
     * 根据value的类型 获取对应设置值得方法
     * @param type $value
     * @return string
     */
    private function __getSetMethod($value) {
        $type = strtolower(gettype($value));
        switch (trim($type)) {
            case 'integer':
                $type = 'int';
                break;
            case 'boolean':
                $type = 'bool';
                break;
        }
        $setMethod = 'setV' . ucfirst($type);
        return array($type, $setMethod);
    }

    /**
     * 将ots查询出来的对象转化为数组
     * @param type $data
     * @return type
     */
    private function __otsObjToArr($data) {

        // 定义返回的数据
        $_data = array();

        // 如果为对象转化为数组
        $is_one_line = false;
        if (is_object($data)) {
            $is_one_line = true;
            $data = array($data);
        }

        // 将对象转化为数组
        foreach ($data as $v_data) {

            // 获取主键数据 和 其他字段数据
            $_lineData = array();
            $columns_key = array_merge($v_data->getPrimaryKeyColumns(), $v_data->getAttributeColumns());
            foreach ($columns_key as $k => $v) {
                $name = $v->getName();
                $c_val = $v->getValue();
                $type = $c_val->getType();
                $getMethod = 'getV' . ucfirst($this->valueType[$type]);
                $value = $c_val->$getMethod();
                $_lineData[$name] = $value;
            }
            $_data[] = $_lineData;

            // 如果单行则直接赋值单行数据
            if ($is_one_line) {
                $_data = $_lineData;
            }
        }

        return $_data;
    }

    /**
     * ots异常时，保存临时数据
     * 注：只处理网络异常和认证失败时的数据临时存储处理，其他逻辑业务异常不处理。
     * @param type $key
     * @param type $value
     * @param type $flag 更新，删除数据时发生逻辑异常时，是否需要判断因该数据未加入而导致。
     * @return boolean
     */
    private function __putTmpData($key = '', $value = '',$flag=false) {

        // 非法参数判断
        if (!trim($key)) {
            return false;
        }

        // 数据初始化
        $file_path = __DIR__ . self::TMP_DATA_FILE;
        $data = array();
        if (file_exists($file_path)) {
            $data = @json_decode(file_get_contents($file_path), true);
        }
        
        // 更新，删除数据时发生逻辑异常时，是否需要判断因该数据未加入而导致
        if($flag){
            list($method,$dataId) = explode('__', $key);
            if(!isset($data['putRow__'.$dataId])){
                return false;
            }
        }

        // 赋值
        $data[$key] = $value;
        
        // 保存数据
        @file_put_contents($file_path, json_encode($data), LOCK_EX);

        return true;
    }

    /**
     * 将临时数据保存到ots中
     * @return boolean
     */
    public function saveTmpDataToOts() {

        // 判断临时数据是否存在
        $file_path = __DIR__ . self::TMP_DATA_FILE;
        if (!file_exists($file_path)) {
            return false;
        }

        // 获取临时数据并保存
        $data = @json_decode(file_get_contents($file_path), true);
        if (!$data || !is_array($data)) {
            return false;
        }
        foreach ($data as $key => $param) {
            if (strpos($key, '__') === false) {
                continue;
            }
            @list($method, ) = explode('__', $key);
            if (method_exists($this, $method)) {
                $res = @call_user_func_array(array($this, $method), $param);
                if (is_object($res) || !isset($res['error'])) {
                    unset($data[$key]);
                }
            }
        }

        // 将未处理成功的数据保存
        file_put_contents($file_path, json_encode($data), LOCK_EX);
        return true;
    }                                                                                                                                                                                                                                                                                                                                                                                                                                                              

    /**
     * 创建表
     * @param type $tableName string 表名
     * @param type $primaryKey array 主键 array('gid'=>'integer','uid'=>'integer','key'=>'string')
     * @return type
     */
    public function createTable($tableName = '', $primaryKey = array()) {

        // 参数判断
        if (!trim($tableName)) {
            return array('error' => '表名称不能为空！');
        }
        if (!$primaryKey) {
            return array('error' => '表的主键不能为空!');
        }

        // 表信息数据初始化
        $operation = "/CreateTable";
        $tableMeta = new \ots\protocolbuffer\TableMeta();
        $tableMeta->setTableName($tableName);

        // 设置主键
        if (is_array($primaryKey)) {
            foreach ($primaryKey as $k => $v) {
                $v = strtolower($v);
                if (!isset($this->columnType[$v])) {
                    // 非法表数据类型
                    return array('error' => '非法表数据类型:' . $v);
                }
                $columnSchema = new \ots\protocolbuffer\ColumnSchema();
                $columnSchema->setName($k);
                $columnSchema->setType($this->columnType[$v]);
                $tableMeta->appendPrimaryKey($columnSchema);
            }
        }

        // 设置表可读可写
        $capacityUnit = new \ots\protocolbuffer\CapacityUnit();
        $capacityUnit->setRead(100);
        $capacityUnit->setWrite(100);
        $reservedThroughput = new \ots\protocolbuffer\ReservedThroughput();
        $reservedThroughput->setCapacityUnit($capacityUnit);

        // 创建请求数据
        $request = new \ots\protocolbuffer\CreateTableRequest();
        $request->setTableMeta($tableMeta);
        $request->setReservedThroughput($reservedThroughput);

        // 发送请求
        $res = $this->__otsClientSend($operation, $request);

        // 处理结果
        if (isset($res['error'])) {
            return $res;
        }
        if ($res) {
            $err = \ots\protocolbuffer\Error::parseFromString($res);
            $message = $err->getMessage();
            if (trim($message)) {
                return array('error' => $message);
            }
        }

        return array('message' => 'create table success!');
    }

    /**
     * 获取所有表
     * @return \ots\protocolbuffer\ListTableResponse
     */
    public function listTable() {

        // 发送数据初始化
        $operation = "/ListTable";
        $request = new \ots\protocolbuffer\ListTableRequest();

        // 发送请求
        $res = $this->__otsClientSend($operation, $request);

        // 结果处理
        if (isset($res['error'])) {
            return $res;
        }
        if (trim($res)) {
            $response = \ots\protocolbuffer\ListTableResponse::parseFromString($res);
            return $response;
        }

        return new \ots\protocolbuffer\ListTableResponse();
    }

    /**
     * 删除表
     * @param type $tableName string 表名
     * @return type
     */
    public function deleteTable($tableName = '') {

        // 参数非法判断
        if (!trim($tableName)) {
            return array('error' => '需要删除的表明不能为空！');
        }

        // 发送数据初始化
        $operation = "/DeleteTable";
        $request = new \ots\protocolbuffer\DeleteTableRequest();
        $request->setTableName($tableName);

        // 发送请求
        $res = $this->__otsClientSend($operation, $request);

        // 结果处理
        if (isset($res['error'])) {
            return $res;
        }
        if (trim($res)) {
            $err = \ots\protocolbuffer\Error::parseFromString($res);
            $message = $err->getMessage();
            if (trim($message)) {
                return array('error' => $message);
            }
        }

        return array('message' => 'delete table success!');
    }

    /**
     * 向表中添加一行数据
     * @param type $tableName string 表名称
     * @param type $primaryKey array 主键数据 array('uid'=>'2','gid'=>'123')
     * @param type $attributeColumn array 其他属性 array('name'=>'zhangsan','age'=>1)
     * @return type
     */
    public function putRow($tableName = '', $primaryKey = array(), $attributeColumn = array()) {

        // 参数判断
        if (!trim($tableName)) {
            return array('error' => '操作的表名不能为空！');
        }
        if (!$primaryKey) {
            return array('error' => '主键不能为空！');
        }

        // 发送请求数据初始化
        $operation = '/PutRow';
        $request = new \ots\protocolbuffer\PutRowRequest();
        $request->setTableName($tableName);

        // 添加主键 和 其他属性
        if ($primaryKey && is_array($primaryKey) && is_array($attributeColumn)) {
            foreach (array('appendPrimaryKey' => $primaryKey, 'appendAttributeColumns' => $attributeColumn) as $method => $v_primary_columns) {
                foreach ($v_primary_columns as $key => $value) {

                    // 根据数据类型获取赋值方法：$setMethod
                    list($type, $setMethod) = $this->__getSetMethod($value);

                    // 创建 ColumnValue
                    $ColumnValue = new \ots\protocolbuffer\ColumnValue();
                    $ColumnValue->setType($this->columnType[$type]);
                    $ColumnValue->$setMethod($value);

                    $column = new \ots\protocolbuffer\Column();
                    $column->setName($key);
                    $column->setValue($ColumnValue);
                    $request->$method($column);
                }
            }
        }

        // 设置Condition
        $condition = new \ots\protocolbuffer\Condition();
        $condition->setRowExistence(\ots\protocolbuffer\RowExistenceExpectation::EXPECT_NOT_EXIST);
        $request->setCondition($condition);

        // 发送请求
        $res = $this->__otsClientSend($operation, $request);

        // 结果处理
        if (isset($res['error'])) {
            // 网络访问异常 保存临时数据
            $key = 'putRow__' . md5($tableName . json_encode($primaryKey));
            $this->__putTmpData($key, array($tableName, $primaryKey, $attributeColumn));
            return $res;
        }

        $err = \ots\protocolbuffer\Error::parseFromString($res);
        $message = $err->getMessage();
        $code = $err->getCode();
        if (strtolower(trim($code)) == 'otsauthfailed') {
            // 如果认证失败，则保存临时数据
            $key = 'putRow__' . md5($tableName . json_encode($primaryKey));
            $this->__putTmpData($key, array($tableName, $primaryKey, $attributeColumn));
        }
        if (trim($message)) {
            return array('error' => $message);
        }

        // 返回正常结果
        $response = \ots\protocolbuffer\PutRowResponse::parseFromString($res);
        return $response;
    }

    /**
     * 更新一行数据
     * @param type $tableName string 表名
     * @param type $primaryKey array 修改数据的主键条件 array('uid'=>'2','gid'=>'123')
     * @param type $attributeColumn array 需要修改的属性 array('name'=>'zhangsan','age'=>1)
     * @return type
     */
    public function updateRow($tableName = '', $primaryKey = array(), $attributeColumn = array()) {

        // 参数判断
        if (!trim($tableName)) {
            return array('error' => '操作的表名不能为空！');
        }
        if (!$primaryKey) {
            return array('error' => '主键不能为空！');
        }

        // 参数初始化
        $operation = '/UpdateRow';
        $request = new \ots\protocolbuffer\UpdateRowRequest();
        $request->setTableName($tableName);

        // 添加主键和其他属性
        if ($primaryKey && is_array($primaryKey) && is_array($attributeColumn)) {
            foreach (array('appendPrimaryKey' => $primaryKey, 'appendAttributeColumns' => $attributeColumn) as $method => $v_primary_columns) {
                foreach ($v_primary_columns as $key => $value) {

                    // 根据数据类型获取赋值方法：$setMethod
                    list($type, $setMethod) = $this->__getSetMethod($value);

                    // 创建ColumnValue
                    $ColumnValue = new \ots\protocolbuffer\ColumnValue();
                    $ColumnValue->setType($this->columnType[$type]);
                    $ColumnValue->$setMethod($value);

                    // 主键处理
                    if ($method == 'appendPrimaryKey') {
                        $column = new \ots\protocolbuffer\Column();
                        $column->setName($key);
                        $column->setValue($ColumnValue);
                        $request->$method($column);
                        continue;
                    }

                    $column = new \ots\protocolbuffer\ColumnUpdate();
                    $column->setName($key);
                    $column->setValue($ColumnValue);
                    $column->setType(\ots\protocolbuffer\OperationType::PUT);
                    $request->$method($column);
                }
            }
        }

        // 设置Condition
        $condition = new \ots\protocolbuffer\Condition();
        $condition->setRowExistence(\ots\protocolbuffer\RowExistenceExpectation::EXPECT_EXIST);
        $request->setCondition($condition);

        // 发送请求
        $res = $this->__otsClientSend($operation, $request);

        // 结果处理
        $key = 'updateRow__' . md5($tableName . json_encode($primaryKey));
        if (isset($res['error'])) {
            // 网络访问异常 保存临时数据
            $this->__putTmpData($key, array($tableName, $primaryKey, $attributeColumn));
            return $res;
        }

        // ots自定义异常
        $err = \ots\protocolbuffer\Error::parseFromString($res);
        $message = $err->getMessage();
        $code = $err->getCode();
        if (strtolower(trim($code)) == 'otsauthfailed') {
            // 如果认证失败，则保存临时数据
            $this->__putTmpData($key, array($tableName, $primaryKey, $attributeColumn));
        }
        if (trim($message)) {
            // 业务异常，判断是否因数据未保存上而导致的异常，则保存数据
            $this->__putTmpData($key, array($tableName, $primaryKey, $attributeColumn,true));
            return array('error' => $message);
        }

        // 返回正常结果
        $response = \ots\protocolbuffer\UpdateRowResponse::parseFromString($res);
        return $response;
    }

    /**
     * 获取单行数据
     * @param type $tableName string 表名
     * @param type $primaryKey array 主键条件 array('uid'=>'2','gid'=>'123')
     * @param type $select array 显示返回的字段  array('name','age') 不穿参数全部返回
     * @return type
     */
    public function getRow($tableName = '', $primaryKey = array(), $select = array()) {

        // 参数判断
        if (!trim($tableName)) {
            return array('error' => '操作的表名不能为空！');
        }
        if (!$primaryKey || !is_array($primaryKey)) {
            return array('error' => '主键条件不能为空，且必须为数组！');
        }

        // 请求数据初始化
        $operation = '/GetRow';
        $request = new \ots\protocolbuffer\GetRowRequest();
        $request->setTableName($tableName);

        // 主键查询条件
        if (is_array($primaryKey)) {
            foreach ($primaryKey as $key => $value) {

                // 根据数据类型获取赋值方法：$setMethod
                list($type, $setMethod) = $this->__getSetMethod($value);

                // 创建ColumnValue
                $ColumnValue = new \ots\protocolbuffer\ColumnValue();
                $ColumnValue->setType($this->columnType[$type]);
                $ColumnValue->$setMethod($value);

                $column = new \ots\protocolbuffer\Column();
                $column->setName($key);
                $column->setValue($ColumnValue);
                $request->appendPrimaryKey($column);
            }
        }

        // 设置需要显示的字段
        if (is_array($select)) {
            foreach ($select as $v) {
                if (!trim($v) || !is_string($v)) {
                    continue;
                }
                $request->appendColumnsToGet($v);
            }
        }

        // 发送请求
        $res = $this->__otsClientSend($operation, $request);
        if (isset($res['error'])) {
            return $res;
        }

        // 返回正常结果
        $response = \ots\protocolbuffer\GetRowResponse::parseFromString($res);
        return $this->__otsObjToArr($response->getRow());
    }

    /**
     * 根据主键删除数据
     * @param type $tableName string 表名
     * @param type $primaryKey array 主键条件 array('uid'=>'2','gid'=>'123')
     * @return type
     */
    public function deleteRow($tableName = '', $primaryKey = array()) {

        // 条件判断
        if (!trim($tableName)) {
            return array('error' => '操作的表名不能为空！');
        }
        if (!$primaryKey) {
            return array('error' => '主键不能为空！');
        }

        // 发送请求数据初始化
        $operation = '/DeleteRow';
        $request = new \ots\protocolbuffer\DeleteRowRequest();
        $request->setTableName($tableName);

        // 主键查询条件
        if (is_array($primaryKey)) {
            foreach ($primaryKey as $key => $value) {

                // 根据数据类型获取赋值方法：$setMethod
                list($type, $setMethod) = $this->__getSetMethod($value);

                // 创建ColumnValue
                $ColumnValue = new \ots\protocolbuffer\ColumnValue();
                $ColumnValue->setType($this->columnType[$type]);
                $ColumnValue->$setMethod($value);

                $column = new \ots\protocolbuffer\Column();
                $column->setName($key);
                $column->setValue($ColumnValue);
                $request->appendPrimaryKey($column);
            }
        }

        // 设置condition
        $condition = new \ots\protocolbuffer\Condition();
        $condition->setRowExistence(\ots\protocolbuffer\RowExistenceExpectation::EXPECT_EXIST);
        $request->setCondition($condition);

        // 发送请求
        $res = $this->__otsClientSend($operation, $request);

        // 结果处理
        $key = 'deleteRow__' . md5($tableName . json_encode($primaryKey));
        if (isset($res['error'])) {
            // 网络访问异常 保存临时数据
            $this->__putTmpData($key, array($tableName, $primaryKey));
            return $res;
        }

        $err = \ots\protocolbuffer\Error::parseFromString($res);
        $message = $err->getMessage();
        $code = $err->getCode();
        if (strtolower(trim($code)) == 'otsauthfailed') {
            // 如果认证失败，则保存临时数据
            $this->__putTmpData($key, array($tableName, $primaryKey));
        }
        if (trim($message)) {
            $this->__putTmpData($key, array($tableName, $primaryKey),true);
            return array('error' => $message);
        }

        // 返回正常结果
        $response = \ots\protocolbuffer\DeleteRowResponse::parseFromString($res);
        return $response;
    }

    /**
     * 读取指定主键范围内的数据。
     * @param type $tableName string 表名
     * @param type $select array 显示的字段 array('name','age')
     * @param type $startPrimaryKey array 主键范围最小值 array('uid'=>1,'gid'=>1)
     * @param type $endPrimaryKey array 主键范围最大值 array('uid'=>10,'gid'=>10)
     * @param type $limit string 显示的条数，
     * @return type \ots\protocolbuffer\GetRangeResponse 返回查询结果
     */
    public function getRange($tableName = '', $select = array(), $startPrimaryKey = array(), $endPrimaryKey = array(), $limit = '15') {

        // 参数判断
        if (!trim($tableName)) {
            return array('error' => '操作的表名不能为空！');
        }

        // 初始化
        $operation = '/GetRange';
        $request = new \ots\protocolbuffer\GetRangeRequest();
        $request->setTableName($tableName);

        // 设置显示的条数
        $request->setLimit(strval($limit));

        // 设置取值方向
        $request->setDirection(\ots\protocolbuffer\Direction::FORWARD);

        // 设置显示的字段
        if (is_array($select)) {
            foreach ($select as $v) {
                if (!trim($v) || !is_string($v)) {
                    continue;
                }
                $request->appendColumnsToGet($v);
            }
        }

        // 设置主键查询条件 
        /**
         * 主键条件设置要求 $startPrimaryKey 和 $endPrimaryKey的并集必须包含所有的主键
         * 如 $startPrimaryKey 没包含全主键，则认为未包含的为无穷小。
         * 如 $endPrimaryKey 没包含全主键，则认为未包含的为无穷大。
         */
        if (is_array($startPrimaryKey) && is_array($endPrimaryKey)) {

            // 处理主键无穷大，无穷小的情况
            $startPrimaryKeyInfMin = array_diff_key($endPrimaryKey, $startPrimaryKey);
            $endPrimaryKeyInfMax = array_diff_key($startPrimaryKey, $endPrimaryKey);
            foreach (array('appendInclusiveStartPrimaryKey' => $startPrimaryKeyInfMin, 'appendExclusiveEndPrimaryKey' => $endPrimaryKeyInfMax) as $method => $v_primary_columns) {
                foreach ($v_primary_columns as $key => $value) {
                    // 设置ColumnValue的类型
                    $columnType = $this->columnType['inf_min'];
                    if (trim($method) == 'appendExclusiveEndPrimaryKey') {
                        $columnType = $this->columnType['inf_max'];
                    }

                    // 创建ColumnValue
                    $ColumnValue = new \ots\protocolbuffer\ColumnValue();
                    $ColumnValue->setType($columnType);

                    $column = new \ots\protocolbuffer\Column();
                    $column->setName($key);
                    $column->setValue($ColumnValue);
                    $request->$method($column);
                }
            }

            // 处理正常主键值得范围
            foreach (array('appendInclusiveStartPrimaryKey' => $startPrimaryKey, 'appendExclusiveEndPrimaryKey' => $endPrimaryKey) as $method => $v_primary_columns) {
                foreach ($v_primary_columns as $key => $value) {

                    // 获取ColumnValue赋值的方法$setMethod
                    list($type, $setMethod) = $this->__getSetMethod($value);

                    $ColumnValue = new \ots\protocolbuffer\ColumnValue();
                    $ColumnValue->setType($this->columnType[$type]);
                    $ColumnValue->$setMethod($value);

                    $column = new \ots\protocolbuffer\Column();
                    $column->setName($key);
                    $column->setValue($ColumnValue);
                    $request->$method($column);
                }
            }
        }

        // 发送请求
        $res = $this->__otsClientSend($operation, $request);

        // 结果处理
        if (isset($res['error'])) {
            return $res;
        }
		
        // 返回正常结果 
        $response = \ots\protocolbuffer\GetRangeResponse::parseFromString($res);
		return $this->__otsObjToArr($response->getRows());
    }

}

// $ots = new OTS();

// 1.创建table
// $column = array('id' => 'integer');
// var_dump($ots->createTable('team',$column));
// exit;
// 
// 2.获取listtable
// var_dump($ots->listTable());
// 
// 3.删除表     string(14) "miertao_tesing"
// var_dump($ots->deleteTable('team'));

// 
// 4.添加一行数据
// $p = array('uid'=>27);
// $a = array('name'=>'zhangsan','age'=>22,'address'=>'xian');
// var_dump($ots->putRow('miertao_tesing',$p,$a));
// 
// 5.更新一行数据
// $p = array('uid'=>7);
// $a = array('name'=>'lisi','age'=>55,'address'=>'北京');
// var_dump($ots->updateRow('miertao_tesing',$p,$a));
// 
// 6.查询一行数据
// $p = array('id'=>85032);
// $a = array('uid','name','age','address');
// $r = $ots->getRow('team',$p);
// var_dump($r);
// var_dump(iconv("UTF-8","gb2312",$r['express']));exit;
// 
// 7.删除一行数据
// $p = array('id' => 6002);
// r = $ots->deleteRow('team', $p);
// var_dump($r);
// 
// 8.获取多行数据 $tableName='',$select=array(),$startPrimaryKey=array(),$endPrimaryKey=array(),$limit='15'
// $s_p = array('id' => 6011);
// $e_p = array();
// $a = array();
// $r = $ots->getRange('team', array(), $s_p, $e_p);
// var_dump($r);

// 9.将临时数据保存到ots中
// $ots->saveTmpDataToOts();