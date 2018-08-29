<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Indexer\Handler;

use Magento\Framework\App\ResourceConnection\SourceProviderInterface;
use Magento\Framework\Indexer\HandlerInterface;

/**
 * Indexer Handler for Quickbooks Attributes
 */
class Attribute implements HandlerInterface
{
    /**
     * Prepare SQL for field and add it to collection
     *
     * {@inheritdoc }
     */
    public function prepareSql(SourceProviderInterface $source, $alias, $fieldInfo)
    {
        $source->addFieldToSelect($fieldInfo['origin'], $fieldInfo['name']);
    }
}
