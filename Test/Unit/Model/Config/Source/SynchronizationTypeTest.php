<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Model\Config\Source;

use TNW\QuickbooksBasic\Model\Config\Source\SynchronizationType;

/**
 * Class SynchronizationTypeTest
 * @package TNW\QuickbooksBasic\Test\Unit\Model\Config\Source
 */
class SynchronizationTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var \TNW\QuickbooksBasic\Model\Config\Source\SynchronizationType */
    protected $synchronizationType = null;

    public function setUp()
    {
        $this->synchronizationType = new SynchronizationType();
    }

    /**
     * @param $expected
     * @dataProvider getAllOptionsDataProvider
     */
    public function testToOptionArray($expected)
    {
        $this->assertSame(
            $this->synchronizationType->toOptionArray(),
            $expected
        );
    }

    /**
     * DataProvider for testToOptionArray
     * @return array
     */
    public function getAllOptionsDataProvider()
    {
        return [
            'Options array' => [
                [
                    [
                        'value' => SynchronizationType::SYNCHRONIZATION_TYPE_MANUAL,
                        'label' =>
                            SynchronizationType::SYNCHRONIZATION_TYPE_MANUAL_LABEL
                    ],
                    [
                        'value' => SynchronizationType::SYNCHRONIZATION_TYPE_AUTOMATIC,
                        'label' =>
                            SynchronizationType::SYNCHRONIZATION_TYPE_AUTOMATIC_LABEL
                    ]
                ]
            ]
        ];
    }

    public function tearDown()
    {
        $this->synchronizationType = null;
    }
}
