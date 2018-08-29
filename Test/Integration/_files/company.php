<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

use Magento\Config\Model\Config;
use Magento\TestFramework\Helper\Bootstrap;

/** @var Config $config */
$config = Bootstrap::getObjectManager()->create(Config::class);
$config->setDataByPath('quickbooks/general/company_id', 'testCompany');
$config->save();
