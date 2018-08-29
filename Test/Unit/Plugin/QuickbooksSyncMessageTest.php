<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Plugin;

use Magento\Framework\Message\Collection;
use Magento\Framework\Message\Factory;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\View\Layout\ProcessorInterface;
use TNW\QuickbooksBasic\Plugin\QuickbooksSyncMessage;

/**
 * @covers TNW\QuickbooksBasic\Plugin\QuickbooksSyncMessage
 * Class QuickbooksSyncMessageTest
 * @package TNW\QuickbooksBasic\Test\Unit\Plugin
 */
class QuickbooksSyncMessageTest extends \PHPUnit_Framework_TestCase
{

    /** @var Factory $FactoryMock */
    protected $factoryMock;

    /** @var \Magento\Framework\Registry $registryMock */
    protected $registryMock;

    /** @var \Magento\Framework\UrlInterface $urlBuilderMock */
    protected $urlBuilderMock;

    /** @var \TNW\QuickbooksBasic\Model\Quickbooks $quickbooksMock */
    protected $quickbooksMock;

    /** @var  \Magento\Framework\View\Element\Messages $messagesMock */
    protected $messagesMock;

    /** @var  Collection $collectionMock */
    protected $collectionMock;

    /** @var  \Magento\Framework\View\LayoutInterface $layoutMock */
    protected $layoutMock;

    /** @var  ProcessorInterface $updateMock */
    protected $updateMock;

    /** @var  \Magento\Framework\App\RequestInterface $requestMock */
    protected $requestMock;

    /** @var  MessageInterface $messageMock */
    protected $messageMock;

    /** @var  QuickbooksSyncMessage $plugin */
    protected $plugin;

    /**
     * @codingStandardsIgnoreStart
     * @covers TNW\QuickbooksBasic\Plugin\QuickbooksSyncMessage::afterGetMessageCollection
     */
    public function testAfterGetMessageCollection()
    {

        $sectionIds = ['quickbooks'];
        $configHandle = 'adminhtml_system_config_edit';

        $handles = [$configHandle];
        $section = $sectionIds[0];

        $paramName = 'section';
        $paramDefault = null;

        $messageText = 'QuickBooks connector has not been configured yet, synchronization was skipped. ';

        $registryName = 'quickbooks_empty_access_token';

        $this->messagesMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($this->layoutMock);

        $this->layoutMock->expects($this->once())
            ->method('getUpdate')
            ->willReturn($this->updateMock);

        $this->updateMock->expects($this->once())
            ->method('getHandles')
            ->willReturn($handles);

        $this->messagesMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(
                $this->identicalTo($paramName),
                $this->identicalTo($paramDefault)
            )
            ->willReturn($section);

        $this->quickbooksMock->expects($this->once())
            ->method('getAccessToken')
            ->willReturn(null);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with($this->identicalTo($registryName))
            ->willReturn(false);

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with(
                $this->identicalTo($registryName),
                $this->identicalTo(true)
            )
            ->willReturn(null);

        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with(
                $this->identicalTo(MessageInterface::TYPE_WARNING),
                $this->identicalTo($messageText)
            )
            ->willReturn($this->messageMock);

        $this->collectionMock->expects($this->once())
            ->method('addMessage')
            ->with($this->identicalTo($this->messageMock))
            ->willReturn(null);


        $result = $this->plugin->afterGetMessageCollection($this->messagesMock,
            $this->collectionMock);

        $this->assertInstanceOf(Collection::class,
            $result);
    }

    /** @codingStandardsIgnoreEnd */

    public function setUp()
    {
        /** @var Factory $factoryMock */
        $this->factoryMock = $this->getMock(
            'Magento\Framework\Message\Factory',
            [],
            [],
            '',
            false
        );

        /** @var \Magento\Framework\Registry $registryMock */
        $this->registryMock = $this->getMock(
            'Magento\Framework\Registry',
            [],
            [],
            '',
            false
        );

        /** @var \Magento\Framework\UrlInterface $urlBuilderMock */
        $this->urlBuilderMock = $this->getMock(
            'Magento\Framework\UrlInterface',
            [],
            [],
            '',
            false
        );

        /** @var \TNW\QuickbooksBasic\Model\Quickbooks $quickbooksMock */
        $this->quickbooksMock = $this->getMock(
            'TNW\QuickbooksBasic\Model\Quickbooks',
            [],
            [],
            '',
            false
        );

        /** @var \Magento\Framework\View\Element\Messages $messagesMock */
        $this->messagesMock = $this->getMock(
            'Magento\Framework\View\Element\Messages',
            [],
            [],
            '',
            false
        );

        /** @var  Collection $collectionMock */
        $this->collectionMock = $this->getMock(
            'Magento\Framework\Message\Collection',
            [],
            [],
            '',
            false
        );

        /** @var  \Magento\Framework\View\LayoutInterface $layoutMock */
        $this->layoutMock = $this->getMock(
            'Magento\Framework\View\LayoutInterface',
            [],
            [],
            '',
            false
        );

        /** @var  ProcessorInterface $updateMock */
        $this->updateMock = $this->getMock(
            'Magento\Framework\View\Layout\ProcessorInterface',
            [],
            [],
            '',
            false
        );

        /** @var  \Magento\Framework\App\RequestInterface $requestMock */
        $this->requestMock = $this->getMock(
            'Magento\Framework\App\RequestInterface',
            [],
            [],
            '',
            false
        );

        /** @var  MessageInterface $messageMock */
        $this->messageMock = $this->getMock(
            'Magento\Framework\Message\MessageInterface',
            [],
            [],
            '',
            false
        );

        $this->plugin = new QuickbooksSyncMessage(
            $this->factoryMock,
            $this->urlBuilderMock,
            $this->registryMock,
            $this->quickbooksMock
        );

        parent::setUp();
    }

    public function tearDown()
    {
        $this->factoryMock = null;
        $this->registryMock = null;
        $this->urlBuilderMock = null;
        $this->quickbooksMock = null;
        $this->messagesMock = null;
        $this->collectionMock = null;
        $this->updateMock = null;
        $this->requestMock = null;
        $this->messageMock = null;
        $this->plugin = null;
        parent::tearDown();
    }
}
