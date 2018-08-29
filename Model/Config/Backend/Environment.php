<?php
namespace TNW\QuickbooksBasic\Model\Config\Backend;

use Magento\Framework\Registry;
use Magento\Framework\Model\Context;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use TNW\QuickbooksBasic\Model\Quickbooks;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use TNW\QuickbooksBasic\Model\Config;

/**
 * Class Quickbooks
 *
 * @package TNW\QuickbooksBasic\Model\Config\Backend
 */
class Environment extends Value
{
    /** @var ResourceConfig */
    protected $resourceConfig;

    /** @var  Config */
    protected $quickbooksConfig;

    /**
     * Environment constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ResourceConfig $resourceConfig
     * @param Config $quickbooksConfig
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ResourceConfig $resourceConfig,
        Config $quickbooksConfig,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->quickbooksConfig = $quickbooksConfig;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function afterSave()
    {
        if (!$this->isValueChanged()) {
            return parent::afterSave();
        }

        try {
            $environment = (int) $this->getData('value');

            $this->resourceConfig->saveConfig(
                Quickbooks::XML_PATH_QUICKBOOKS_GENERAL_URL_QUICKBOOKS_API,
                $this->quickbooksConfig->getQuickbooksApiUrlByType($environment),
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );

            $this->resourceConfig->saveConfig(
                Quickbooks::QUICKBOOKS_URL,
                $this->quickbooksConfig->getQuickbooksUrlByType($environment),
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );

            $this->resourceConfig->saveConfig(
                Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS,
                null,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        } catch (\Exception $e) {
            throw new \Exception(__("QuickBooks: Can't save the Environment."));
        }

        return parent::afterSave();
    }
}
