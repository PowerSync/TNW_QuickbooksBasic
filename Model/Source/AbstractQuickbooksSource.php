<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */


namespace TNW\QuickbooksBasic\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use TNW\QuickbooksBasic\Model\Quickbooks;
use \Zend\Serializer\Serializer;

/**
 * Class SourceAbstract
 * @codeCoverageIgnore
 */
abstract class AbstractQuickbooksSource extends AbstractSource
{

    /** @var \Magento\Framework\Config\CacheInterface */
    protected $cache;

    /** @var \TNW\QuickbooksBasic\Model\Quickbooks */
    protected $quickbooks;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $config;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /**
     * @param CacheInterface $cache
     * @param Quickbooks $quickbooks
     * @param ScopeConfigInterface $config
     * @param Registry $registry
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        CacheInterface $cache,
        Quickbooks $quickbooks,
        ScopeConfigInterface $config,
        Registry $registry,
        ManagerInterface $messageManager
    ) {
        $this->cache = $cache;
        $this->quickbooks = $quickbooks;
        $this->config = $config;
        $this->registry = $registry;
        $this->messageManager = $messageManager;
    }

    /**
     * Get available options
     *
     * @codeCoverageIgnore
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

    /**
     * @return array
     */
    public function getAllOptions()
    {
        $optionList = [];

        $optionArr = $this->getSourceList();

        foreach ($optionArr as $option) {
            $optionList[] = [
                'value' => $option['Id'],
                'label' => $option['Name']
            ];
        }

        return $optionList;
    }

    /**
     * Returns options
     *
     * @return array
     */
    public function getOptions()
    {
        $optionList = [];
        $optionArr = $this->getSourceList();
        foreach ($optionArr as $option) {
            $optionList[$option['Id']] = $option['Name'];
        }
        return $optionList;
    }

    /**
     * @return bool|mixed|string
     */
    protected function getSourceList()
    {
        /** @var string $cacheId */
        $cacheId = $this->getCacheId();

        /** @var string|bool $sourceList */
        $sourceList = $this->cache->load($cacheId);

        /** @var bool $newList */
        $newList = false;

        if ($sourceList) {
            if ($this->isJson($sourceList)) {
                $sourceList = \Zend_Json::decode($sourceList, \Zend_Json::TYPE_ARRAY);
            } else {
                $sourceList = Serializer::unserialize($sourceList);
            }
        } else {
            $sourceList = $this->querySourceList();
            $newList = true;
        }

        if ($sourceList && count($sourceList) > 0 && $newList) {
            $this->cache->save(\Zend_Json::encode($sourceList), $cacheId);
        }

        return $sourceList;
    }

    /**
     * @return string
     */
    abstract protected function getCacheId();

    /**
     * @return array
     */
    public function querySourceList()
    {
        if (!$this->quickbooks->getAccessToken()) {
            return [];
        }

        $queryString = $this->getQueryString();
        $queryResponseKey = $this->getQueryResponseKey();
        $sourceArr = [];

        /** @var \Zend_Http_Response $response */
        $response = $this->quickbooks->query($queryString);

        /** @var array $responseBody */
        $sourceList =
            $this->quickbooks->getQuickbooksService()->checkResponse($response);

        if ($sourceList &&
            isset($sourceList['QueryResponse']) &&
            isset($sourceList['QueryResponse'][$queryResponseKey])
        ) {
            $sourceArr = $sourceList['QueryResponse'][$queryResponseKey];
            if (isset($sourceArr['Id'])) {
                $sourceArr = [$sourceArr];
            }
        }

        return $sourceArr;
    }

    /**
     * @return string
     */
    abstract protected function getQueryString();

    /**
     * @return string
     */
    abstract protected function getQueryResponseKey();

    /**
     * @param $websiteId
     * @return array
     */
    public function getSelectedOption($websiteId = null)
    {
        $configPath = $this->getConfigPath();

        $returnOption = [];
        $sourceList = $this->getSourceList();
        if ($websiteId) {
            $currentOptionId = $this->config->getValue(
                $configPath,
                ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            );
        } else {
            $currentOptionId = $this->config->getValue($configPath);
        }

        foreach ($sourceList as $option) {
            if ($option['Id'] == $currentOptionId) {
                $returnOption = $option;
                break;
            }
        }

        if (empty($returnOption) && count($sourceList) > 0) {
            $returnOption = reset($sourceList);
        }

        return $returnOption;
    }

    /**
     * @return string
     */
    abstract protected function getConfigPath();

    /**
     *
     */
    public function cleanCache()
    {
        /** @var string $cacheId */
        $cacheId = $this->getCacheId();
        $this->cache->remove($cacheId);
    }

    /**
     * @param $value
     * @return bool
     */
    private function isJson($value)
    {
        if ($value === '') {
            return false;
        }

        \json_decode($value);
        if (\json_last_error()) {
            return false;
        }

        return true;
    }
}
