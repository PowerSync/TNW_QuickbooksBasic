<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use TNW\QuickbooksBasic\Model\Quickbooks as ModelQuickbooks;

/**
 * Class Quickbooks
 *
 * @package TNW\QuickbooksBasic\Controller\Adminhtml
 */
abstract class Quickbooks extends Action
{
    /** @var JsonFactory */
    protected $resultJsonFactory;

    /** @var ModelQuickbooks */
    protected $quickbooks;

    /**
     * @param Action\Context  $context
     * @param JsonFactory     $resultJsonFactory
     * @param ModelQuickbooks $quickbooks
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        ModelQuickbooks $quickbooks
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quickbooks = $quickbooks;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TNW_QuickbooksBasic::config');
    }
}
