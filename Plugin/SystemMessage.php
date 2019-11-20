<?php

namespace TNW\QuickbooksBasic\Plugin;

use Magento\AdminNotification\Model\ResourceModel\System\Message\Collection;
use Magento\Framework\Notification\MessageInterface;
use Magento\Backend\Model\Session;
use TNW\QuickbooksBasic\Model\SystemMessages;

/**
 * Class QuickbooksSyncMessage
 *
 * @package TNW\QuickbooksBasic\Plugin
 */
class SystemMessage
{
    /**
     * @var Collection
     */
    private $messageCollection;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var SystemMessages
     */
    private $systemMessages;

    /**
     * SystemMessage constructor.
     * @param Collection $collection
     */
    public function __construct(
        Collection $collection,
        Session $session,
        SystemMessages $systemMessages
    ) {
        $this->messageCollection= $collection;
        $this->session = $session;
        $this->systemMessages = $systemMessages;
    }
    /**
     * @param MessageInterface $message
     * @param $result
     */
    public function afterIsDisplayed(MessageInterface $message, bool $result): bool
    {
        $result = $this->systemMessages->isDisabledAllMessages() ? false : $result;
        return $result;
    }
}