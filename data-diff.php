<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '128M');
error_reporting(E_ALL);

define('MAX_CELL_SIZE', 32);
define('OUTPUT_SQL', false);

require 'app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

$objectManager = $bootstrap->getObjectManager();

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

/** @var Magento\Framework\App\ResourceConnection $resource */
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
/** @var Magento\Framework\DB\Adapter\Pdo\Mysql $connection */
$connection = $resource->getConnection();

//Configuration example
//$tablesToDiff = [
//    'sales_order' => [
//        'entity_pk'=>'entity_id',
//        'entity_ids'=>[3119, 3120],
//        'where'=>'`$entity_pk` = "$entity_id"', // default value so we can ommit this conf
//        'column_append' => [],  // add column, useful for joins
//        'column_include' => [], // compare values only for this columns
//        'column_exclude' => [], // ignore this columns while compare
//    ],
//    'sales_order_tax_item' => [
//        'entity_pk'=>'order_id',
//        'entity_ids'=>[3119, 3120],
//        'select'=>'sot.order_id as order_id',
//        'join' => 'LEFT JOIN sales_order_tax sot ON sot.tax_id = sales_order_tax_item.tax_id',
//        'where'=>'`sot`.`$entity_pk` = "$entity_id"',
//        'column_append' =>['order_id'],
//    ],
//];

$compareOrderIds = [3120, 3121];

$tablesToDiff = [
    'sales_order' => [
        'entity_pk' => 'entity_id',
        'entity_ids' => $compareOrderIds,
    ],
    'sales_order_address' => [
        'entity_pk' => 'parent_id',
        'entity_ids' => $compareOrderIds,
    ],
    'sales_order_grid' => [
        'entity_pk' => 'entity_id',
        'entity_ids' => $compareOrderIds,
    ],
    'sales_order_item' => [
        'entity_pk' => 'order_id',
        'entity_ids' => $compareOrderIds,
    ],
    'sales_order_payment' => [
        'entity_pk'=>'parent_id',
        'entity_ids' => $compareOrderIds,
    ],
    'sales_order_status_history' => [
        'entity_pk' => 'parent_id',
        'entity_ids' => $compareOrderIds,
    ],
    'sales_order_tax' => [
        'entity_pk' => 'order_id',
        'entity_ids' => $compareOrderIds,
    ],
    'sales_order_tax_item' => [
        'entity_pk' => 'order_id',
        'entity_ids' => $compareOrderIds,
        'select' => 'sot.order_id as order_id',
        'join' => 'LEFT JOIN sales_order_tax sot ON sot.tax_id = sales_order_tax_item.tax_id',
        'where' => 'sot.$entity_pk = "$entity_id"',
        'column_append' => ['order_id'],
    ],
    'sales_creditmemo' => [
        'entity_pk' => 'order_id',
        'entity_ids' => $compareOrderIds,
    ],
    'sales_creditmemo_grid' => [
        'entity_pk' => 'order_id',
        'entity_ids' => $compareOrderIds,
    ],
    'sales_creditmemo_item' => [
        'entity_pk' => 'order_id',
        'entity_ids' => $compareOrderIds,
        'select' => 'scm.order_id as order_id',
        'join' => 'LEFT JOIN sales_creditmemo scm ON scm.entity_id = sales_creditmemo_item.parent_id',
        'where' => 'scm.$entity_pk = "$entity_id"',
        'column_append' => ['order_id'],
    ],
];

foreach($tablesToDiff as $table=>$diffParams) {

    $tableName = $resource->getTableName($table);

    $tableColumnsInfo = $connection->fetchAll('SHOW COLUMNS FROM `'.$tableName.'`');
    $tableColumns = array_column($tableColumnsInfo, 'Field');
    $prefixedTableColumns = array_map(
        function($column) use ($tableName){
            return $tableName.'.'.$column;
        },
        $tableColumns
    );

    $dataSets = [];
    $dataSetsSql = [];

    foreach($diffParams['entity_ids'] as $entityId) {

        $sqlSelect = implode(',', $prefixedTableColumns);
        if (!empty($diffParams['select'])) $sqlSelect .= ','.$diffParams['select'];

        $sqlJoin = $diffParams['join'] ?? '';

        $sqlWhere = str_replace(
            ['$entity_pk', '$entity_id'],
            [$diffParams['entity_pk'],$entityId],
            ($diffParams['where'] ?? '$entity_pk = "$entity_id"')
        );

        $sql = 'SELECT '.$sqlSelect.' FROM `'.$tableName.'` '.$sqlJoin.' WHERE '.$sqlWhere;
        $dataSetsSql[] = $sql;
        $dataSets[] = $connection->rawFetchRow($sql);
    }

    $diffOutput = [];
    $columnsToCheck = $tableColumns;
    if (!empty($diffParams['column_append']))
    {
        $columnsToCheck = array_merge($columnsToCheck, $diffParams['column_append']);
    }

    foreach ($columnsToCheck as $tableColumn) {
        if (!empty($diffParams['column_include'])) {
            if (!in_array($tableColumn, $diffParams['column_include'])) continue;
        }
        if (!empty($diffParams['column_exclude'])) {
            if (in_array($tableColumn, $diffParams['column_exclude'])) continue;
        }
        $hasDiff = false;
        $isFirstDataSet = true;
        $baseVal = null;
        foreach($dataSets as $dataSet) {
            if ($isFirstDataSet) {
                $baseVal = $dataSet[$tableColumn];
                $isFirstDataSet = false;
                continue;
            }
            if ($baseVal != $dataSet[$tableColumn]) {
                $hasDiff = true;
                break;
            }
        }
        if ($hasDiff) {
            foreach($dataSets as $k => $dataSet) {
                $diffOutput[$tableColumn][$k] = $dataSet[$tableColumn];
            }
        }
    }

    $columnsLength = [];
    if ($diffOutput) {
        $columnsLength[0] = max(array_map('strlen', array_keys($diffOutput)));
    } else {
        $columnsLength[0] = 0;
    }

    if (strlen($tableName) > $columnsLength[0]) {
        $columnsLength[0] = strlen($tableName);
    }

    foreach($diffParams['entity_ids'] as $k=>$value) {
        $length = mb_strlen($value);
        if ($length > MAX_CELL_SIZE) $length = MAX_CELL_SIZE;
        $columnsLength[$k+1] = $length;
    }

    foreach($diffOutput as $column=>$values) {
        foreach($values as $k=>$value) {
            $length = mb_strlen($value);
            if ($length > MAX_CELL_SIZE) $length = MAX_CELL_SIZE;
            if ($columnsLength[$k+1] < $length) {
                $columnsLength[$k+1] = $length;
            }
        }
    }

    $outputLines = [];


    $outputHeader = '';
    $outputHeader .= str_pad($diffParams['entity_pk'], $columnsLength[0], ' ', STR_PAD_RIGHT);
    $outputHeader .= ' | ';
    foreach($diffParams['entity_ids'] as $k=>$value) {
        $value = trim($value);
        $value = str_replace(PHP_EOL, ' ', $value);
        if (mb_strlen($value) > MAX_CELL_SIZE) $value = mb_substr($value,0,MAX_CELL_SIZE-2).'..';
        $outputHeader .= str_pad($value, $columnsLength[$k+1], ' ', STR_PAD_RIGHT);
        $outputHeader .= ' | ';
    }
    $outputHeader = trim($outputHeader);

    if (!$diffOutput) {
        $outputLines[] = '| '.str_pad('All items are equal', strlen($outputHeader)-4, ' ', STR_PAD_RIGHT).' |';
    } else {

        foreach ($diffOutput as $column => $values) {
            $outputLine = '';
            $outputLine .= str_pad($column, $columnsLength[0], ' ', STR_PAD_RIGHT);
            $outputLine .= ' | ';
            foreach ($values as $k => $value) {
                $value = trim($value);
                $value = str_replace(PHP_EOL, ' ', $value);
                if (mb_strlen($value) > MAX_CELL_SIZE) {
                    $value = mb_substr($value, 0, MAX_CELL_SIZE - 2) . '..';
                }
                $outputLine .= str_pad($value, $columnsLength[$k + 1], ' ', STR_PAD_RIGHT);
                $outputLine .= ' | ';
            }
            $outputLines[] = trim($outputLine);
        }
    }

    echo PHP_EOL;
    echo 'TABLE: '.$table.PHP_EOL;
    echo str_repeat('-',strlen($outputHeader)).PHP_EOL;
    echo $outputHeader.PHP_EOL;
    echo str_repeat('-',strlen($outputHeader)).PHP_EOL;
    foreach($outputLines as $outputLine) {
        echo $outputLine.PHP_EOL;
    }
    echo str_repeat('=',strlen($outputHeader)).PHP_EOL;

    if (defined('OUTPUT_SQL') && OUTPUT_SQL) {
        foreach($dataSetsSql as $k=>$sql) {
            echo ($k?'----'.PHP_EOL:'').$sql.PHP_EOL;
        }
        echo str_repeat('=',strlen($outputHeader)).PHP_EOL;
    }
}
