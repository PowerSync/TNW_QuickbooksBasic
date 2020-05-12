<?php
/**
 *  Copyright © 2016 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Service\Exception;

class InvalidGrantException extends \Magento\Framework\Exception\LocalizedException
{
    const INVALID_GRANT = 'invalid_grant';
}
