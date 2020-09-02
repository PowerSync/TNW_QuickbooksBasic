<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Plugin;

use Magento\Framework\Message\Collection;
use Magento\Framework\Message\Factory;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Messages;
use TNW\QuickbooksBasic\Model\Quickbooks;
use TNW\QuickbooksBasic\Model\Quickbooks\Company as QuickbooksCompany;

/**
 * Class QuickbooksSyncMessage
 *
 * @package TNW\QuickbooksBasic\Plugin
 */
class QuickbooksSyncMessage
{
    /** @var Factory */
    protected $messageCollectionFactory;

    /** @var array */
    protected $sectionIds = ['quickbooks'];

    /** @var string */
    protected $configHandle = 'adminhtml_system_config_edit';

    /** @var Registry */
    protected $registry;

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var \TNW\QuickbooksBasic\Model\Quickbooks */
    protected $quickbooks;

    /**
     * @var QuickbooksCompany
     */
    protected $quickbooksCompany;

    /**
     * QuickbooksSyncMessage constructor.
     * @param Factory $collectionFactory
     * @param UrlInterface $urlBuilder
     * @param Registry $registry
     * @param Quickbooks $quickbooks
     * @param QuickbooksCompany $quickbooksCompany
     */
    public function __construct(
        Factory $collectionFactory,
        UrlInterface $urlBuilder,
        Registry $registry,
        Quickbooks $quickbooks,
        QuickbooksCompany $quickbooksCompany
    ) {
        $this->quickbooksCompany = $quickbooksCompany;
        $this->messageCollectionFactory = $collectionFactory;
        $this->registry = $registry;
        $this->urlBuilder = $urlBuilder;
        $this->quickbooks = $quickbooks;
    }

    /**
     * @param Messages $messages
     * @param Collection $collection
     * @return Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetMessageCollection(
        Messages $messages,
        Collection $collection
    ) {
        /** @var array $handles */
        $handles = $messages->getLayout()->getUpdate()->getHandles();

        /** @var string $section */
        $section = $messages->getRequest()->getParam('section', null);

        if (in_array($this->configHandle, $handles) && in_array(
            $section,
            $this->sectionIds
        )
        ) {
            if (!($this->quickbooks->getAccessToken() instanceof \OAuth\OAuth2\Token\StdOAuth2Token)) {
                if (!$this->registry->registry('quickbooks_empty_access_token')) {
                   $this->noAccessTokenMessage($collection);
                }
            } elseif ($this->quickbooks->getAccessToken()) {
                $accessTokenCleared = false;
                try {
                    $data = $this->quickbooksCompany->getQuickbooksService()
                        ->checkResponse($this->quickbooksCompany->read());
                } catch (\Exception $e) {
                    $this->quickbooksCompany->getQuickbooksService()->clearAccessToken();
                    $accessTokenCleared = true;
                }
                if (isset($data) && isset($data['fault']['error']) && is_array($data['fault']['error'])) {
                    foreach ($data['fault']['error'] as $error) {
                        if (isset($error['code']) && $error['code'] == 3200) {
                            $this->quickbooksCompany->getQuickbooksService()->clearAccessToken();
                            $accessTokenCleared = true;
                            break;
                        }
                    }
                }
                if ($accessTokenCleared) {
                    $this->noAccessTokenMessage($collection);
                }
            }
        }

        return $collection;
    }

    /**
     * @param Collection $collection
     */
    private function noAccessTokenMessage($collection)
    {
        if (!is_bool($this->registry->registry('quickbooks_empty_access_token'))) {
            $this->registry->register(
                'quickbooks_empty_access_token',
                true
            );
        }
        $collection->addMessage(
            $this->messageCollectionFactory->create(
                MessageInterface::TYPE_WARNING,
                'QuickBooks connector has not been configured yet, synchronization was skipped. '
            )
        );
    }
}
