<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Model\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use TNW\QuickbooksBasic\Model\Quickbooks;

/**
 * Class Invalid
 *
 * @package TNW\QuickbooksBasic\Model\Message
 */
class Invalid implements MessageInterface
{
    /** @var Quickbooks */
    protected $quickbooks;

    /** @var UrlInterface */
    protected $urlBuilder;

    /**
     * Invalid constructor.
     *
     * @param Quickbooks   $quickbooks
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Quickbooks $quickbooks,
        UrlInterface $urlBuilder
    ) {
        $this->quickbooks = $quickbooks;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Check whether access token is valid or not
     *
     * @return bool
     */
    public function isDisplayed()
    {
        return !boolval($this->quickbooks->getAccessToken());
    }

    /**
     * Retrieve unique message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return md5('ACCESS_TOKEN_INVALID');
    }

    /**
     * Retrieve message text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        $url = $this->urlBuilder->getUrl(
            'adminhtml/system_config/edit/section/quickbooks'
        );

        //@codingStandardsIgnoreStart
        return __(
            'QuickBooks: An authorized access token has been expired. Please go to <a href="%1">config section</a> and connect to QuickBooks . ',
            $url
        );
        //@codingStandardsIgnoreEnd
    }

    /**
     * Retrieve message severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return MessageInterface::SEVERITY_CRITICAL;
    }
}
