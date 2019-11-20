<?php

namespace TNW\QuickbooksBasic\Model;
use Magento\AdminNotification\Model\ResourceModel\System\Message\Collection;
use Magento\Backend\Model\Session;

class SystemMessages
{

    const DISABLED_ALL_NOTIFY_KEY = 'disabled_notify';
    /**
     * @var Collection
     */
    private $messageCollection;
    /**
     * @var Session
     */
    private $session;

    public function __construct(
        Collection $collection,
        Session $session
    ) {
        $this->messageCollection = $collection;
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function getMessagesHash(): ?string
    {
        $items = $this->messageCollection->getItems();
        if (empty($items)) {
            return null;
        }
        $arrDataIdentity = [];
        foreach ($items as $item) {
            $arrDataIdentity[] = $item->getData('identity');
        }
        $key = implode('|', $arrDataIdentity);
        $hashKey = sha1($key);
        return $hashKey;
    }

    /**
     * @return mixed
     */
    public function getIsDisableMessagesHash(): ?string
    {
        return $this->session->getData(self::DISABLED_ALL_NOTIFY_KEY);
    }

    /**
     *
     */
    public function setDisabledMessageHash(): void
    {
        $this->session->setData(self::DISABLED_ALL_NOTIFY_KEY, $this->getMessagesHash());
    }

    /**
     * @return bool
     */
    public function isDisabledAllMessages(): bool
    {
        $isDisabledMessageHash = $this->getIsDisableMessagesHash();
        if ($isDisabledMessageHash !== null) {
            $hashCollection = $this->getMessagesHash();
            if ($hashCollection === null) {
                return true;
            }
            return ($isDisabledMessageHash == $hashCollection);
        }
        return false;
    }
}
