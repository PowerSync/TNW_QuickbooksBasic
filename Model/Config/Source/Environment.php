<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */


namespace TNW\QuickbooksBasic\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Environment
 * @package TNW\QuickbooksBasic\Model\Config\Source
 */
class Environment implements ArrayInterface
{
    const ENVIRONMENT_TYPE_PRODUCTION = "Production";
    const ENVIRONMENT_TYPE_SANDBOX = "Sandbox";

    /**
     * get options for Synchronization Type
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $optionList */
        $optionList = [];

        $optionList[] = [
            'value' => 0,
            'label' => self::ENVIRONMENT_TYPE_PRODUCTION
        ];
        $optionList[] = [
            'value' => 1,
            'label' => self::ENVIRONMENT_TYPE_SANDBOX
        ];

        return $optionList;
    }
}
