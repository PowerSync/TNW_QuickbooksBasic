<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Model\Quickbooks;

use TNW\QuickbooksBasic\Model\Quickbooks;

/**
 * Class Company
 * @package TNW\QuickbooksBasic\Model\Quickbooks
 */
class Company extends Quickbooks
{
    const API_READ = 'company/:companyId/companyinfo/:companyId';
    const API_PREFERENCES_READ = 'company/:companyId/preferences';

    /**
     * @codeCoverageIgnore
     * @throws \Exception
     */
    public function create()
    {
        $this->throwQuickbooksException(new \Exception('Not available.'));
    }

    /**
     * @return \Zend_Http_Response
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function read()
    {
        return $this->quickbooksService->read(self::API_READ);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     * @throws \Exception
     */
    public function preferences()
    {
        static $preferences;

        if (empty($preferences)) {
            $response = $this->quickbooksService->checkResponse(
                $this->quickbooksService->read(self::API_PREFERENCES_READ)
            );

            if (isset($response['Fault']['Error'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('%1', $response['Fault']['Error']));
            }

            $preferences = $response['Preferences'];
        }

        return $preferences;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function companyInfo()
    {
        static $companyInfo;

        if (empty($companyInfo)) {
            $response = $this->quickbooksService->checkResponse(
                $this->read()
            );

            if (isset($response['Fault']['Error'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('%1', $response['Fault']['Error']));
            }

            $companyInfo = $response['CompanyInfo'];
        }

        return $companyInfo;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function isUSCompany()
    {
        return $this->companyInfo()['Country'] === 'US';
    }

    /**
     * @codeCoverageIgnore
     * @throws \Exception
     */
    public function update()
    {
        $this->throwQuickbooksException(new \Exception('Not available.'));
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $queryString
     *
     * @return void|\Zend_Http_Response
     * @throws \Exception
     */
    public function query($queryString)
    {
        $this->throwQuickbooksException(new \Exception('Not implemented.'));
    }
}
