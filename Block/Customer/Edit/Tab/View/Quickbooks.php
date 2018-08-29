<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Block\Customer\Edit\Tab\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\ObjectManagerInterface;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer;
use TNW\QuickbooksBasic\Model\Config;
use TNW\QuickbooksBasic\Model\Quickbooks as QuickbooksModel;

/**
 * Adminhtml customer view quickbooks information block.
 */
class Quickbooks extends Template
{
    /** @var \Magento\Customer\Api\Data\CustomerInterface */
    protected $customer;

    /** @var array */
    protected $quickbooksAttributes = [
        Customer::QUICKBOOKS_ID,
        Customer::QUICKBOOKS_SYNC_TOKEN,
        Customer::QUICKBOOKS_SYNC_STATUS,
    ];

    /** @var DataObjectHelper */
    protected $dataObjectHelper;

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var CustomerInterfaceFactory */
    protected $customerDataFactory;

    /** @var ObjectManagerInterface */
    protected $objectManager;

    /**
     * @param Context                      $context
     * @param CustomerInterfaceFactory     $customerDataFactory
     * @param DataObjectHelper             $dataObjectHelper
     * @param AttributeRepositoryInterface $attributeRepository
     * @param ObjectManagerInterface       $objectManager
     * @param array                        $data
     */
    public function __construct(
        Context $context,
        CustomerInterfaceFactory $customerDataFactory,
        DataObjectHelper $dataObjectHelper,
        AttributeRepositoryInterface $attributeRepository,
        ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->customerDataFactory = $customerDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->objectManager = $objectManager;
        parent::__construct($context, $data);
    }

    /**
     * Get quickbooks customer attributes data
     *
     * @return array
     */
    public function getQuickbooksAttributes()
    {
        $result = [];
        foreach ($this->quickbooksAttributes as $attributeCode) {
            $customerAttribute =
                $this->getCustomer()->getCustomAttribute($attributeCode);

            if ($customerAttribute) {
                $result[] = $this->getAttributeData(
                    $customerAttribute->getAttributeCode(),
                    $customerAttribute->getValue()
                );
            } else {
                $result[] = $this->getAttributeData($attributeCode);
            }
        }

        return $result;
    }

    /**
     * Retrieve customer object
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer()
    {
        if (!$this->customer) {
            $this->customer = $this->customerDataFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $this->customer,
                $this->_backendSession->getCustomerData()['account'],
                '\Magento\Customer\Api\Data\CustomerInterface'
            );
        }

        return $this->customer;
    }

    /**
     * @param string      $attributeCode
     * @param string|null $attributeValue
     *
     * @return array
     */
    protected function getAttributeData($attributeCode, $attributeValue = null)
    {
        switch ($attributeCode) {
            case Customer::QUICKBOOKS_SYNC_STATUS:
                /** @var string $attributeValue */
                $attributeValue = $attributeValue ?
                    Config::IN_SYNC :
                    Config::OUT_OF_SYNC;
                break;
            case Customer::QUICKBOOKS_ID:
                /** @var string $attributeValue */
                $attributeValue = $attributeValue ?
                    $this->getQbCustomerUrl($attributeValue) :
                    $attributeValue;
                break;
        }

        $result = [];
        $attr = $this->attributeRepository->get(
            \Magento\Customer\Model\Customer::ENTITY,
            $attributeCode
        );

        /** @var string|null $sourceModelName */
        $sourceModelName = $attr->getSourceModel();

        if ($sourceModelName) {
            /** @var AbstractSource $sourceModel */
            $sourceModel = $this->objectManager->create($sourceModelName);
            $result['value'] = $sourceModel->getOptionText($attributeValue);
        } else {
            $result['value'] = $attributeValue;
        }

        $result['label'] = $attr->getDefaultFrontendLabel();

        return $result;
    }

    /**
     * @param string|null $attributeValue
     *
     * @return string
     */
    private function getQbCustomerUrl($attributeValue = null)
    {
        /** @var string $url */
        $url = '';

        if ($attributeValue) {
            $url = QuickbooksModel::PROTOCOL
                . $this->_scopeConfig->getValue(QuickbooksModel::QUICKBOOKS_URL)
                . Customer::QUICKBOOKS_CUSTOMER_URL
                . $attributeValue;

            $url = '<a href="'
                . $url
                . '" target="_blank">'
                . $attributeValue
                . '</a>';
        }

        return $url;
    }

    /**
     * @return array
     */
    public function getSyncButtonData()
    {
        $controller = $this->getUrl(
            'quickbooks/customer/synccustomer',
            ['customer_id' => $this->getCustomer()->getId()]
        );

        $data = [
            'label' => __('QuickBooks Sync'),
            'class' => 'action-primary',
            'on_click' => sprintf("location.href = '%s';", $controller),
        ];

        return $data;
    }
}
