<?php
/**
 *  Copyright © 2016 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

use Magento\Config\Model\Config;
use Magento\TestFramework\Helper\Bootstrap;
use TNW\QuickbooksBasic\Model\Quickbooks as ModelQuickbooks;

/** @var Config $config */
$config = Bootstrap::getObjectManager()->create(Config::class);
$config->setDataByPath(
    ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID,
    'testCompanyId'
);
$config->save();

$config->setDataByPath(
    ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_URL_QUICKBOOKS_API,
    'http://testQbUrlApi/'
);
$config->save();
