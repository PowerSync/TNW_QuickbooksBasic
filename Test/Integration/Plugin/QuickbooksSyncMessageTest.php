<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Integration\Plugin;

use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Messages;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\ObjectManager;
use TNW\QuickbooksBasic\Model\Quickbooks;
use TNW\QuickbooksBasic\Plugin\QuickbooksSyncMessage as QuickbooksSyncMessagePlugin;

/**
 * Class QuickbooksSyncMessageTest
 * @package TNW\QuickbooksBasic\Test\Integration\Plugin
 */
class QuickbooksSyncMessageTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ObjectManager */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();
        parent::setUp();
    }

    public function testTheQuickbooksSyncMessagePluginRegistered()
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->create(PluginList::class);

        /** @var array $pluginInfo */
        $pluginInfo = $pluginList->get(Messages::class, []);

        $this->assertSame(
            QuickbooksSyncMessagePlugin::class,
            $pluginInfo['tnw_quickbooks_plugin_quickbookssyncmessage']['instance']
        );
    }

    public function testAfterGetMessageCollection()
    {
        /** @var Quickbooks mock $quickbooksMock */
        $quickbooksMock = $this->getMockBuilder(Quickbooks::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quickbooksMock->method('getAccessToken')
            ->will($this->returnValue(null));

        $this->objectManager->configure(
            [Quickbooks::class => ['shared' => true]]
        );
        $this->objectManager->addSharedInstance(
            $quickbooksMock,
            Quickbooks::class
        );

        /** @var Messages $messagesElement */
        $messagesElement = $this->objectManager->create(Messages::class);
        $messagesElement->getRequest()->setParams(['section' => 'quickbooks']);
        $messagesElement->getLayout()->getUpdate()
            ->addHandle('adminhtml_system_config_edit');

        /** @var \Magento\Framework\Message\Collection $collection */
        $collection = $messagesElement->getMessageCollection();

        /** @var Registry $registry */
        $registry = $this->objectManager->get(Registry::class);

        $this->assertTrue($registry->registry('quickbooks_empty_access_token'));

        $this->assertSame(
            MessageInterface::TYPE_WARNING,
            $collection->getLastAddedMessage()->getType()
        );
        $this->assertSame(
            'QuickBooks connector has not been configured yet, synchronization was skipped. ',
            $collection->getLastAddedMessage()->getText()
        );
    }
}
