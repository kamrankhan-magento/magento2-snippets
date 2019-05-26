<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '2G');
error_reporting(E_ALL);

require 'app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

$objectManager = $bootstrap->getObjectManager();

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

/** @var  $state \Magento\Framework\App\State */
$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);


/** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
$productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');

/** Apply filters here */
$collection = $productCollection->addAttributeToSelect('*')
            ->setPageSize(10) // only get 10 products 
            ->setCurPage(1)  // first page (means limit 0,10)
            ->load();

/** @var  $repository \Magento\Catalog\Api\ProductRepositoryInterface */
$repository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);


foreach ($collection as $product){

    $oldPrice = $product->getPrice();
    $newPrice = $oldPrice + 0.01;

    echo 'ID  =  '.$product->getId();
    echo '| NAME  =  '.$product->getName();
    echo ' | SKU  =  '.$product->getSku();
    echo ' | PRICE  =  '.$oldPrice.' -> '.$newPrice;

    $product->setPrice($newPrice);
    echo ' .. saving';
    
    $repository->save($product);
    echo ' .. complete';
    
    echo PHP_EOL;
}  
