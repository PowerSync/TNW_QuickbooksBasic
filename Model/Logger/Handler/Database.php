<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\QuickbooksBasic\Model\Logger\Handler;

use Monolog\Handler\AbstractProcessingHandler;

class Database extends AbstractProcessingHandler
{
    const MESSAGE_LIMIT_SIZE = 65000;

    /**
     * @var \TNW\QuickbooksBasic\Model\ResourceModel\Message
     */
    protected $resourceMessage;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $systemLogger;

    /**
     * @var \TNW\QuickbooksBasic\Model\Config
     */
    private $quickbooksConfig;

    /**
     * Database constructor.
     * @param \TNW\QuickbooksBasic\Model\ResourceModel\Message $resourceMessage
     * @param \Psr\Log\LoggerInterface $systemLogger
     * @param \TNW\QuickbooksBasic\Model\Config $quickbooksConfig
     */
    public function __construct(
        \TNW\QuickbooksBasic\Model\ResourceModel\Message $resourceMessage,
        \Psr\Log\LoggerInterface $systemLogger,
        \TNW\QuickbooksBasic\Model\Config $quickbooksConfig
    ) {
        $this->resourceMessage = $resourceMessage;
        $this->systemLogger = $systemLogger;
        $this->quickbooksConfig = $quickbooksConfig;

        parent::__construct();
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record) :void
    {
        if (!$this->quickbooksConfig->getDbLogStatus()) {
            return;
        }

        try {
            do {
                $this->resourceMessage
                    ->saveRecord(
                        $record['extra']['uid'],
                        $record['level'],
                        substr($record['message'], 0, self::MESSAGE_LIMIT_SIZE)
                    );

                $record['message'] = substr($record['message'], self::MESSAGE_LIMIT_SIZE);
            } while (!empty($record['message']));

        } catch (\Exception $e) {
            $this->systemLogger->error($e);
        }
    }
}
