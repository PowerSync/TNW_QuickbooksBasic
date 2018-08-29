<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Integration\Model\Config\Source;

use TNW\QuickbooksBasic\Model\Config\Source\SynchronizationType;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class SynchronizationTypeTest
 * @package TNW\QuickbooksBasic\Test\Integration\Model\Config\Source
 */
class SynchronizationTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var  SynchronizationType */
    protected $configSource;

    protected function setUp()
    {
        $this->configSource = Bootstrap::getObjectManager()->create(
            SynchronizationType::class
        );
        parent::setUp();
    }

    /**
     * @dataProvider toOptionArrayDataProvider
     * @param array $optionList
     */
    public function testToOptionArray(array $optionList)
    {
        /** @var array $result */
        $result = $this->configSource->toOptionArray();

        $this->assertEquals($optionList, $result);
    }

    /**
     * @return array
     */
    public function toOptionArrayDataProvider()
    {
        return [
            [
                'optionList' => [
                    0 => [
                        'value' => 0,
                        'label' => 'Manual',
                    ],
                    1 => [
                        'value' => 1,
                        'label' => 'Automatic',
                    ],
                ]
            ]
        ];
    }
}
