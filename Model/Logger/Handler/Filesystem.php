<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\QuickbooksBasic\Model\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

class Filesystem extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/tnw_quickbooks.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var \TNW\QuickbooksBasic\Model\Config
     */
    private $quickbooksConfig;

    /**
     * SForce constructor.
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystem
     * @param \TNW\QuickbooksBasic\Model\Config $quickbooksConfig
     * @param null $filePath
     */
    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        \TNW\QuickbooksBasic\Model\Config $quickbooksConfig,
        $filePath = null
    ) {
        $this->quickbooksConfig = $quickbooksConfig;
        parent::__construct($filesystem, $filePath);
        $this->setFormatter(new LineFormatter("[%datetime%] [%extra.uid%] %level_name%: %message%\n"));
    }

    /**
     * @param array $record
     * @return void
     */
    public function write(array $record) :void
    {
        if (!$this->quickbooksConfig->getLogStatus()) {
            return;
        }

        parent::write($record);
    }
}
