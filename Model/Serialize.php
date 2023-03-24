<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\QuickbooksBasic\Model;

use Magento\Framework\Serialize\Serializer\Serialize as CoreSerialize;

class Serialize extends CoreSerialize
{
    /**
     * {@inheritDoc}
     */
    public function unserializeObject($string, $allowedClasses)
    {
        if (false === $string || null === $string || '' === $string) {
            throw new \InvalidArgumentException('Unable to unserialize value.');
        }
        set_error_handler(
            function () {
                restore_error_handler();
                throw new \InvalidArgumentException('Unable to unserialize value, string is corrupted.');
            },
            E_NOTICE
        );
        // We have to use unserialize here
        // phpcs:ignore Magento2.Security.InsecureFunction
        $result = unserialize($string, ['allowed_classes' => $allowedClasses]);
        restore_error_handler();
        return $result;
    }
}
