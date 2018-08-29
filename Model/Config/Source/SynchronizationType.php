<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */


namespace TNW\QuickbooksBasic\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class SynchronizationType
 * @package TNW\QuickbooksBasic\Model\Config\Source
 */
class SynchronizationType implements ArrayInterface
{
    const SYNCHRONIZATION_TYPE_MANUAL_LABEL = "Manual";
    const SYNCHRONIZATION_TYPE_MANUAL = 0;
    const SYNCHRONIZATION_TYPE_AUTOMATIC_LABEL = "Automatic";
    const SYNCHRONIZATION_TYPE_AUTOMATIC = 1;

    /**
     * get options for Synchronization Type
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $optionList */
        $optionList = [];

        $optionList[] = [
            'value' => self::SYNCHRONIZATION_TYPE_MANUAL,
            'label' => self::SYNCHRONIZATION_TYPE_MANUAL_LABEL
        ];
        $optionList[] = [
            'value' => self::SYNCHRONIZATION_TYPE_AUTOMATIC,
            'label' => self::SYNCHRONIZATION_TYPE_AUTOMATIC_LABEL
        ];

        return $optionList;
    }
}
