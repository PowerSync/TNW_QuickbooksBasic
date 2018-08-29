<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Cron;

use TNW\QuickbooksBasic\Model\Quickbooks;

/**
 * Class Reconnect
 *
 * @package TNW\QuickbooksBasic\Cron
 */
class Reconnect
{
    /** @var Quickbooks */
    protected $quickbooks;

    /**
     * Reconnect constructor.
     *
     * @param Quickbooks $quickbooks
     */
    public function __construct(
        Quickbooks $quickbooks
    ) {
        $this->quickbooks = $quickbooks;
    }

    /**
     * @return void
     */
    public function execute()
    {
        if ($this->quickbooks->isAccessTokenNeedRenewal()) {
            $this->quickbooks->reconnect();
        }
    }
}
