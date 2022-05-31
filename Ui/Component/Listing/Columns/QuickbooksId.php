<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Ui\Component\Listing\Columns;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer;

/**
 * Class QuickbooksId
 *
 * @package TNW\QuickbooksBasic\Ui\Component\Listing\Columns
 */
class QuickbooksId extends Column
{
    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            $url = Customer::PROTOCOL .
                $this->scopeConfig->getValue(Customer::QUICKBOOKS_URL) .
                Customer::QUICKBOOKS_CUSTOMER_URL;
            foreach ($dataSource['data']['items'] as & $item) {
                $html = '';
                if ($item['quickbooks_id'] && strlen(trim($item['quickbooks_id'])) > 0) {
                    $html = sprintf(
                        '<a href="%s" title="%s" target="_blank">%s</a>',
                        $url . $item['quickbooks_id'],
                        __('Show on QuickBooks'),
                        $item['quickbooks_id']
                    );
                }
                $item[$fieldName . '_html'] = $html;
            }
        }

        return $dataSource;
    }
}
