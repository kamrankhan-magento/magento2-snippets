<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '128M');
error_reporting(E_ALL);

require 'app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

$objectManager = $bootstrap->getObjectManager();

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

/** @var Magento\Framework\App\ResourceConnection $resource */
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
/** @var Magento\Framework\DB\Adapter\Pdo\Mysql $connection */
$connection = $resource->getConnection();

if (empty($argv[1])) die('Usage: undo-credit-memo.php <order-id>'.PHP_EOL);

$orderId = intval($argv[1]);

$tableName = $resource->getTableName('sales_order');

$salesOrder = $connection->rawFetchRow('SELECT * FROM '.$tableName.' WHERE entity_id = '.$orderId);

if (!$salesOrder) {
    die('Error: item sales_order.entity_id = '.$orderId.' not found'.PHP_EOL);
}

$queries = [];

$queries[] = '
    UPDATE sales_order SET
        state = "processing",
        status ="processing",      
        base_discount_refunded = NULL,
        base_shipping_refunded = NULL,
        base_shipping_tax_refunded = NULL,
        base_subtotal_refunded = NULL,
        base_tax_refunded = NULL,
        base_total_offline_refunded = NULL,
        base_total_refunded = NULL,
        discount_refunded = NULL,
        shipping_refunded = NULL,
        shipping_tax_refunded = NULL,
        subtotal_refunded = NULL,
        tax_refunded = NULL,
        total_offline_refunded = NULL,
        total_refunded = NULL,
        customer_note_notify = 1,
        adjustment_negative = NULL,
        adjustment_positive = NULL,
        base_adjustment_negative = NULL,
        base_adjustment_positive = NULL,
        discount_tax_compensation_refunded = NULL,
        base_discount_tax_compensation_refunded = NULL,
        base_gift_cards_refunded = NULL,
        gift_cards_refunded = NULL,
        completion_date = NULL,
        erply_order_id = NULL,
        erply_invoice_id = NULL,
        save_erply_was_executed = NULL            
    WHERE entity_id = '.$orderId.'
    LIMIT 1
    '
;

$queries[] = '
    UPDATE sales_order_grid SET
        status ="processing",
        total_refunded = NULL    
    WHERE entity_id = '.$orderId.'
    LIMIT 1
    '
;

$queries[] = '
    UPDATE sales_order_item SET
        qty_refunded = 0,
        amount_refunded = 0,
        base_amount_refunded = 0,
        discount_tax_compensation_refunded = NULL,
        base_discount_tax_compensation_refunded = NULL,
        tax_refunded = NULL,
        base_tax_refunded = NULL,
        discount_refunded = NULL,
        base_discount_refunded = NULL
    WHERE order_id = '.$orderId.'
    '
;

$queries[] = '
    UPDATE sales_order_payment SET
        amount_refunded = NULL,
        base_shipping_refunded = NULL,
        shipping_refunded = NULL,
        base_amount_refunded = NULL
    WHERE parent_id = '.$orderId.'
    '
;

$queries[] = 'DELETE FROM sales_creditmemo_item WHERE parent_id IN (SELECT entity_id FROM sales_creditmemo WHERE order_id = '.$orderId.')';
$queries[] = 'DELETE FROM sales_creditmemo WHERE order_id = '.$orderId;
$queries[] = 'DELETE FROM sales_creditmemo_grid WHERE order_id = '.$orderId;

foreach($queries as $query) {
    echo $query.PHP_EOL;
    echo '-----'.PHP_EOL;
    $connection->query($query);
}
