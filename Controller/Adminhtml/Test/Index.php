<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Controller\Adminhtml\Test;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use TNW\QuickbooksBasic\Controller\Adminhtml\Quickbooks;
use TNW\QuickbooksBasic\Model\Quickbooks as ModelQuickbooks;
use TNW\QuickbooksBasic\Model\Quickbooks\Company;

/**
 * Class Index
 *
 * @package TNW\QuickbooksBasic\Controller\Adminhtml\Test
 */
class Index extends Quickbooks
{

    /** @var Company */
    protected $quickbooksCompany;

    /**
     * @param Action\Context  $context
     * @param JsonFactory     $resultJsonFactory
     * @param ModelQuickbooks $quickbooks
     * @param Company         $quickbooksCompany
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        ModelQuickbooks $quickbooks,
        Company $quickbooksCompany
    ) {
        $this->quickbooksCompany = $quickbooksCompany;
        parent::__construct($context, $resultJsonFactory, $quickbooks);
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        try {
            $response = $this->quickbooksCompany->read();
            $response = [
                'success' => 'true',
                'message' => 'Connection established.',
            ];
        } catch (\Exception $e) {
            $response = [
                'error' => 'true',
                'message' => 'Connection could not be established.',
            ];
        }
        $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);

        return $resultJson->setData($response);
    }
}
