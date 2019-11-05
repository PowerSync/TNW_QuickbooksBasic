<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TNW\QuickbooksBasic\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\QuickbooksBasic\Model\SystemMessages;

/**
 * Class Actions
 */
class NotificationActions extends Column
{
    /**
     * @var SystemMessages
     */
    private $systemMessages;

    /**
     * NotificationActions constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SystemMessages $systemMessages
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SystemMessages $systemMessages,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->systemMessages = $systemMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        $dataSource = parent::prepareDataSource($dataSource);

        if ($this->systemMessages->isDisabledAllMessages()) {
            $dataSource['data']['items'] = [];
            $dataSource['data']['totalRecords'] = 0;
        }
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }
        return $dataSource;
    }
}
