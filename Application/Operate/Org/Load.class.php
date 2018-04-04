<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-04-07
 * Time: 08:53
 */
namespace Operate\Org;

class Load {

    public function __construct($think) {
        $this->framework = $think;
    }

    public function register() {
        $this->regThink();
        $this->regSLS();
        $this->regOTS();
    }

    public function regThink() {
        spl_autoload_register(array($this->framework, "autoload"));
    }

    public function regSLS() {
        spl_autoload_register(array($this, 'sls'));
    }

    public function sls($className) {
        $classPath = explode('_', $className);
        if ($classPath[0] == 'Aliyun') {
            if(count($classPath)>4)
                $classPath = array_slice($classPath, 1, 3);
            else
                $classPath = array_slice($classPath, 1, 3);
            $filePath = dirname(__FILE__) . '/' . implode('/', $classPath) . '.php';

            if (file_exists($filePath))
                require_once($filePath);
        }
    }

    public function regOTS() {
        spl_autoload_register(array($this, 'ots'));
    }


    public function ots($name) {
        static $classmap;
        if (!$classmap) {
            $classmap = array(
                'ots\protocolbuffer\ColumnType' => '/ots/protocolbuffer/ColumnType.php',
                'ots\protocolbuffer\RowExistenceExpectation' => '/ots/protocolbuffer/RowExistenceExpectation.php',
                'ots\protocolbuffer\OperationType' => '/ots/protocolbuffer/OperationType.php',
                'ots\protocolbuffer\Direction' => '/ots/protocolbuffer/Direction.php',
                'ots\protocolbuffer\Error' => '/ots/protocolbuffer/Error.php',
                'ots\protocolbuffer\ColumnSchema' => '/ots/protocolbuffer/ColumnSchema.php',
                'ots\protocolbuffer\ColumnValue' => '/ots/protocolbuffer/ColumnValue.php',
                'ots\protocolbuffer\Column' => '/ots/protocolbuffer/Column.php',
                'ots\protocolbuffer\Row' => '/ots/protocolbuffer/Row.php',
                'ots\protocolbuffer\TableMeta' => '/ots/protocolbuffer/TableMeta.php',
                'ots\protocolbuffer\Condition' => '/ots/protocolbuffer/Condition.php',
                'ots\protocolbuffer\CapacityUnit' => '/ots/protocolbuffer/CapacityUnit.php',
                'ots\protocolbuffer\ReservedThroughputDetails' => '/ots/protocolbuffer/ReservedThroughputDetails.php',
                'ots\protocolbuffer\ReservedThroughput' => '/ots/protocolbuffer/ReservedThroughput.php',
                'ots\protocolbuffer\ConsumedCapacity' => '/ots/protocolbuffer/ConsumedCapacity.php',
                'ots\protocolbuffer\CreateTableRequest' => '/ots/protocolbuffer/CreateTableRequest.php',
                'ots\protocolbuffer\CreateTableResponse' => '/ots/protocolbuffer/CreateTableResponse.php',
                'ots\protocolbuffer\UpdateTableRequest' => '/ots/protocolbuffer/UpdateTableRequest.php',
                'ots\protocolbuffer\UpdateTableResponse' => '/ots/protocolbuffer/UpdateTableResponse.php',
                'ots\protocolbuffer\DescribeTableRequest' => '/ots/protocolbuffer/DescribeTableRequest.php',
                'ots\protocolbuffer\DescribeTableResponse' => '/ots/protocolbuffer/DescribeTableResponse.php',
                'ots\protocolbuffer\ListTableRequest' => '/ots/protocolbuffer/ListTableRequest.php',
                'ots\protocolbuffer\ListTableResponse' => '/ots/protocolbuffer/ListTableResponse.php',
                'ots\protocolbuffer\DeleteTableRequest' => '/ots/protocolbuffer/DeleteTableRequest.php',
                'ots\protocolbuffer\DeleteTableResponse' => '/ots/protocolbuffer/DeleteTableResponse.php',
                'ots\protocolbuffer\GetRowRequest' => '/ots/protocolbuffer/GetRowRequest.php',
                'ots\protocolbuffer\GetRowResponse' => '/ots/protocolbuffer/GetRowResponse.php',
                'ots\protocolbuffer\ColumnUpdate' => '/ots/protocolbuffer/ColumnUpdate.php',
                'ots\protocolbuffer\UpdateRowRequest' => '/ots/protocolbuffer/UpdateRowRequest.php',
                'ots\protocolbuffer\UpdateRowResponse' => '/ots/protocolbuffer/UpdateRowResponse.php',
                'ots\protocolbuffer\PutRowRequest' => '/ots/protocolbuffer/PutRowRequest.php',
                'ots\protocolbuffer\PutRowResponse' => '/ots/protocolbuffer/PutRowResponse.php',
                'ots\protocolbuffer\DeleteRowRequest' => '/ots/protocolbuffer/DeleteRowRequest.php',
                'ots\protocolbuffer\DeleteRowResponse' => '/ots/protocolbuffer/DeleteRowResponse.php',
                'ots\protocolbuffer\RowInBatchGetRowRequest' => '/ots/protocolbuffer/RowInBatchGetRowRequest.php',
                'ots\protocolbuffer\TableInBatchGetRowRequest' => '/ots/protocolbuffer/TableInBatchGetRowRequest.php',
                'ots\protocolbuffer\BatchGetRowRequest' => '/ots/protocolbuffer/BatchGetRowRequest.php',
                'ots\protocolbuffer\RowInBatchGetRowResponse' => '/ots/protocolbuffer/RowInBatchGetRowResponse.php',
                'ots\protocolbuffer\TableInBatchGetRowResponse' => '/ots/protocolbuffer/TableInBatchGetRowResponse.php',
                'ots\protocolbuffer\BatchGetRowResponse' => '/ots/protocolbuffer/BatchGetRowResponse.php',
                'ots\protocolbuffer\PutRowInBatchWriteRowRequest' => '/ots/protocolbuffer/PutRowInBatchWriteRowRequest.php',
                'ots\protocolbuffer\UpdateRowInBatchWriteRowRequest' => '/ots/protocolbuffer/UpdateRowInBatchWriteRowRequest.php',
                'ots\protocolbuffer\DeleteRowInBatchWriteRowRequest' => '/ots/protocolbuffer/DeleteRowInBatchWriteRowRequest.php',
                'ots\protocolbuffer\TableInBatchWriteRowRequest' => '/ots/protocolbuffer/TableInBatchWriteRowRequest.php',
                'ots\protocolbuffer\BatchWriteRowRequest' => '/ots/protocolbuffer/BatchWriteRowRequest.php',
                'ots\protocolbuffer\RowInBatchWriteRowResponse' => '/ots/protocolbuffer/RowInBatchWriteRowResponse.php',
                'ots\protocolbuffer\TableInBatchWriteRowResponse' => '/ots/protocolbuffer/TableInBatchWriteRowResponse.php',
                'ots\protocolbuffer\BatchWriteRowResponse' => '/ots/protocolbuffer/BatchWriteRowResponse.php',
                'ots\protocolbuffer\GetRangeRequest' => '/ots/protocolbuffer/GetRangeRequest.php',
                'ots\protocolbuffer\GetRangeResponse' => '/ots/protocolbuffer/GetRangeResponse.php',
                // @@protoc_insertion_point(autoloader_scope:classmap)
            );
        }
        if (isset($classmap[$name])) {
            //require __DIR__ . DIRECTORY_SEPARATOR . $classmap[$name];
            require dirname(dirname(dirname(__FILE__))) . "/Common/Org/lib/OTS/ots_protocol_buffer" . $classmap[$name];
        }
    }


}