<?php
/**
 *  Copyright © 2016 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Service\Exception;

/**
 * Exception thrown when service is requested to populate auth token but the state is not valid.
 */
class InvalidStateException extends \Magento\Framework\Exception\LocalizedException
{
}
