<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Integration\Model\Customer\Attribute\Source;

use TNW\QuickbooksBasic\Model\Customer\Attribute\Source\SyncStatus;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class SyncStatusTest
 *
 * @package TNW\QuickbooksBasic\Test\Integration\Model\Customer\Attribute\Source
 */
class SyncStatusTest extends \PHPUnit_Framework_TestCase
{
    /** @var  SyncStatus */
    protected $syncStatus;

    protected function setUp()
    {
        $this->syncStatus = Bootstrap::getObjectManager()->create(
            SyncStatus::class
        );

        parent::setUp();
    }

    public function testGetOptionText()
    {
        /** @var string $result */
        $result = $this->syncStatus->getOptionText(1);

        $this->assertEquals('In Sync', $result);

        /** @var bool $result */
        $result = $this->syncStatus->getOptionText(2);

        $this->assertFalse($result);
    }
}
