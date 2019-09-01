<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '128M');
error_reporting(E_ALL);

require 'app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

$objectManager = $bootstrap->getObjectManager();

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();
$tableName = $resource->getTableName('core_config_data'); //gives table name with prefix

echo 'Table: '.$tableName.PHP_EOL;

// OUTPUT:
//  Table: core_config_data

//Select Data from table
$sql = "SELECT * FROM " . $tableName.' WHERE path LIKE "%base_url%"';
$result = $connection->fetchAll($sql); // gives associated array, table fields as key in array.

print_r($result);

// OUTPUT:
// Array
// (
//     [0] => Array
//         (
//             [config_id] => 2
//             [scope] => default
//             [scope_id] => 0
//             [path] => web/unsecure/base_url
//             [value] => http://m23.sv:7070/
//         )
//
//     [1] => Array
//         (
//             [config_id] => 3
//             [scope] => default
//             [scope_id] => 0
//             [path] => web/secure/base_url
//             [value] => https://m23.sv:7070/
//         )
//
// )


try {

    //Insert Data into table
    $sql = "INSERT INTO " . $tableName . " SET scope='default', scope_id=0, path='custom/base_url', value='http://127.0.0.1'";
    $connection->query($sql);

} catch (Magento\Framework\DB\Adapter\DuplicateException $ex) {
    //UPDATE Data into table
    $sql = "UPDATE " . $tableName . " SET value='http://127.0.0.1' WHERE path='custom/base_url' AND scope='default' AND scope_id=0";
    $connection->query($sql);
}

//Select Data from table
$sql = "SELECT * FROM " . $tableName.' WHERE path LIKE "%base_url%"';
$result = $connection->fetchAll($sql); // gives associated array, table fields as key in array.

print_r($result);

// OUTPUT:
// Array
// (
//     [0] => Array
//         (
//             [config_id] => 2
//             [scope] => default
//             [scope_id] => 0
//             [path] => web/unsecure/base_url
//             [value] => http://m23.sv:7070/
//         )
//
//     [1] => Array
//         (
//             [config_id] => 3
//             [scope] => default
//             [scope_id] => 0
//             [path] => web/secure/base_url
//             [value] => https://m23.sv:7070/
//         )
//
//     [2] => Array
//         (
//             [config_id] => 85
//             [scope] => default
//             [scope_id] => 0
//             [path] => custom/base_url
//             [value] => http://127.0.0.1
//         )
//
// )

//Delete Data from table
$sql = "DELETE FROM " . $tableName." WHERE path='custom/base_url' AND scope='default' AND scope_id=0 LIMIT 1";
$connection->query($sql);

//Select Data from table
$sql = "SELECT * FROM " . $tableName.' WHERE path LIKE "%base_url%"';
$result = $connection->fetchAll($sql); // gives associated array, table fields as key in array.

print_r($result);

// OUTPUT:
// Array
// (
//     [0] => Array
//         (
//             [config_id] => 2
//             [scope] => default
//             [scope_id] => 0
//             [path] => web/unsecure/base_url
//             [value] => http://m23.sv:7070/
//         )
//
//     [1] => Array
//         (
//             [config_id] => 3
//             [scope] => default
//             [scope_id] => 0
//             [path] => web/secure/base_url
//             [value] => https://m23.sv:7070/
//         )
//
// )
