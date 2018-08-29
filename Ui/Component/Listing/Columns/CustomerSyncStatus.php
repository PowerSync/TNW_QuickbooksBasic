<?php
namespace TNW\QuickbooksBasic\Ui\Component\Listing\Columns;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer;

/**
 * Class CustomerSyncStatus
 * @package TNW\QuickbooksBasic\Ui\Component\Listing\Columns
 */
class CustomerSyncStatus extends Column
{
    const ERROR_ICON_PATH = 'pub\errors\default\images\i_msg-error.gif';
    const SUCCESS_ICON_PATH = 'pub\errors\default\images\i_msg-success.gif';

    /** @var  StoreManagerInterface */
    protected $storeManager;

    /**
     * SyncStatus constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManagerInterface,
        array $components,
        array $data
    ) {
        $this->storeManager = $storeManagerInterface;
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
        /** @var string $syncStatusName */
        $syncStatusName = Customer::QUICKBOOKS_SYNC_STATUS;

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {

                /** @var string $type */
                $type = '-warning error';

                if (key_exists($syncStatusName, $item)) {
                    if ($item[$syncStatusName]) {
                        /** @var string $html */
                        $type = '-success success';
                    }
                }

                $item[$syncStatusName . '_html'] =
                    '<div class="message message' . $type .
                    ' sync-status-quickbooks"></div>';
            }
        }

        return $dataSource;
    }
}
