<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\QuickbooksBasic\Model\Logger\Handler;

use Monolog\Handler\AbstractProcessingHandler;

class Admin extends AbstractProcessingHandler
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Message constructor.
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->messageManager = $messageManager;
        $this->appState = $appState;
        $this->request = $request;
        parent::__construct(\Monolog\Logger::INFO);
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function write(array $record) : void
    {
        if (strcasecmp($this->appState->getAreaCode(), \Magento\Framework\App\Area::AREA_ADMINHTML) !== 0) {
            return;
        }

        if (strcasecmp($this->request->getActionName(), 'inlineEdit') === 0) {
            return;
        }

        switch ($record['level']) {
            case \Monolog\Logger::ERROR:
                $this->messageManager->addErrorMessage($record['message'], 'backend');
                break;

            case \Monolog\Logger::WARNING:
                $this->messageManager->addWarningMessage($record['message'], 'backend');
                break;

            case \Monolog\Logger::INFO:
                $this->messageManager->addSuccessMessage($record['message'], 'backend');
                break;

            case \Monolog\Logger::NOTICE:
                $this->messageManager->addNoticeMessage($record['message'], 'backend');
                break;
        }
    }
}
