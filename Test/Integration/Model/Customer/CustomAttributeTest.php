<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Integration\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use TNW\QuickbooksBasic\Model\Customer\CustomAttribute;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\ResourceConnection;

/**
 * Class CustomAttributeTest
 * @package TNW\QuickbooksBasic\Test\Integration\Model\Customer
 */
class CustomAttributeTest extends \PHPUnit_Framework_TestCase
{
    /** @var CustomAttribute */
    protected $customAttribute;

    protected function setUp()
    {
        $this->customAttribute = Bootstrap::getObjectManager()->create(
            CustomAttribute::class
        );

        parent::setUp();
    }

    /**
     * @magentoDataFixture loadSaveQuickbooksAttribute
     */
    public function testSaveQuickbooksAttribute()
    {
        /** @var Customer $customer */
        $customer = Bootstrap::getObjectManager()->create(Customer::class);
        $customer->load(1);

        /** @var CustomerInterface $customerDataModel */
        $customerDataModel = $customer->getDataModel();
        $customerDataModel->setCustomAttribute('quickbooks_sync_status', 1);
        $customerDataModel->setCustomAttribute('quickbooks_id', 2);
        $customerDataModel->setCustomAttribute('quickbooks_sync_token', 3);

        $this->customAttribute->saveQuickbooksAttribute($customerDataModel);

        $customer->load(1);

        $this->assertEquals(
            2,
            $customer->getDataModel()
                ->getCustomAttribute('quickbooks_id')->getValue()
        );

        $this->assertEquals(
            1,
            $customer->getDataModel()
                ->getCustomAttribute('quickbooks_sync_status')->getValue()
        );

        $this->assertEquals(
            3,
            $customer->getDataModel()
                ->getCustomAttribute('quickbooks_sync_token')->getValue()
        );
    }

    /** @codingStandardsIgnoreStart */
    public static function loadSaveQuickbooksAttribute()
    {
        include __DIR__ . '/../../_files/customer.php';
    }
    /** @codingStandardsIgnoreEnd */
}
