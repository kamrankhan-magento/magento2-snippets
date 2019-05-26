<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '1G');
error_reporting(E_ALL);

require 'app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

$objectManager = $bootstrap->getObjectManager();

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

/** @var  $state \Magento\Framework\App\State */
$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

// Reset status of the targetrule_product_rule indexer

/** @var \Magento\Indexer\Model\Indexer\State $state */
$state = $objectManager->create(\Magento\Framework\Mview\View\StateInterface::class);
$state->loadByView('targetrule_product_rule');
$state->setStatus(\Magento\Framework\Mview\View\StateInterface::STATUS_IDLE);
$state->save();

// Run Mview update manually
/** @var \Magento\Indexer\Model\Processor $processor */
$processor = $objectManager->create(\Magento\Indexer\Model\Processor::class);
$processor->updateMview();
