<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Integration\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractBackendController;
use TNW\QuickbooksBasic\TokenData;
use Magento\Framework\Message\Manager;
use Magento\TestFramework\Response;
use Magento\Customer\Model\Customer as MagentoCustomer;
use TNW\QuickbooksBasicBusiness\Service\Quickbooks as QuickbooksService;

/**
 * Class SyncCustomerTest
 *
 * @package TNW\QuickbooksBasic\Test\Integration\Controller\Adminhtml\Customer
 */
class SyncCustomerTest extends AbstractBackendController
{
    const TEST_REFERRER_URL = 'http://testReferrerUrl';
    const TEST_EXCEPTION = 'test exception';

    /** @var  ObjectManager */
    protected $objectManager;

    /** @var  TokenData mock */
    protected $tokenData;

    protected function setUp()
    {
        $this->uri = 'backend/quickbooks/customer/syncCustomer';
        $this->resource = 'TNW_QuickbooksBasic::customer';

        $this->objectManager = ObjectManager::getInstance();

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->cleanUpCustomer();

        parent::tearDown();
    }

    /**
     * test customer lookup by email
     *
     * @magentoDataFixture loadExecute
     * @dataProvider       executeDataProvider
     *
     * @param array $responseBodyAfterQuery
     * @param array $responseBodyAfterPost
     */
    public function testExecute(
        array $responseBodyAfterQuery,
        array $responseBodyAfterPost
    ) {
        $this->replaceQbService(
            $responseBodyAfterQuery,
            $responseBodyAfterPost
        );

        $this->replaceRedirect();

        $this->getRequest()->setParams(['customer_id' => 1]);

        /** @var \Magento\Backend\Model\View\Result\Redirect $result */
        $this->dispatch($this->uri);

        /** @var Response $response */
        $response = $this->getResponse();

        /** @var MagentoCustomer $magentoCustomer */
        $magentoCustomer = $this->objectManager->create(MagentoCustomer::class);
        $magentoCustomer->load(1);

        $this->assertEquals(
            1,
            $magentoCustomer->getDataModel()
                ->getCustomAttribute('quickbooks_id')->getValue()
        );

        $this->assertEquals(
            2,
            $magentoCustomer->getDataModel()
                ->getCustomAttribute('quickbooks_sync_token')->getValue()
        );

        $this->assertEquals(
            1,
            $magentoCustomer->getDataModel()
                ->getCustomAttribute('quickbooks_sync_status')->getValue()
        );

        /** @var Manager $messageManager */
        $messageManager = $this->objectManager->get(Manager::class);

        $this->assertEquals(
            "Magento customer 'John Smith' was successfully synchronized",
            $messageManager->getMessages()->getLastAddedMessage()->getText()
        );

        $this->assertTrue($response->isRedirect());
        $this->assertContains(
            self::TEST_REFERRER_URL,
            $response->toString()
        );
    }

    /**
     * @magentoDataFixture loadExecuteException
     */
    public function testExecuteException()
    {
        $this->replaceQbService([], [], true);

        $this->replaceRedirect();

        $this->getRequest()->setParams(['customer_id' => 1]);
        $this->dispatch($this->uri);

        /** @var Response $response */
        $response = $this->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertContains(
            self::TEST_REFERRER_URL,
            $response->toString()
        );

        /** @var Manager $messageManager */
        $messageManager = $this->objectManager->get(Manager::class);

        $this->assertSame(
            self::TEST_EXCEPTION,
            $messageManager->getMessages()->getLastAddedMessage()->getText()
        );
    }

    /**
     * @param array $responseBodyAfterQuery
     * @param array $responseBodyAfterPost
     * @param bool  $shouldThrowException
     */
    private function replaceQbService(
        array $responseBodyAfterQuery = [],
        array $responseBodyAfterPost = [],
        $shouldThrowException = false
    ) {
        /** @var QuickbooksService mock $quickbooksServiceMock */
        $quickbooksServiceMock = $this->getMockBuilder(
            QuickbooksService::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        if ($shouldThrowException) {
            $quickbooksServiceMock->expects($this->once())
                ->method('query')
                ->will($this->throwException(
                    new \Exception(self::TEST_EXCEPTION)
                ));
        } else {
            $quickbooksServiceMock->expects($this->once())
                ->method('query')
                ->willReturn(null);
            $quickbooksServiceMock->expects($this->once())
                ->method('post')
                ->willReturn(null);
            $quickbooksServiceMock->expects($this->exactly(2))
                ->method('checkResponse')
                ->will($this->onConsecutiveCalls(
                    $this->returnValue($responseBodyAfterQuery),
                    $this->returnValue($responseBodyAfterPost)
                ));
        }

        $this->objectManager->configure(
            [QuickbooksService::class => ['shared' => true]]
        );
        $this->objectManager->addSharedInstance(
            $quickbooksServiceMock,
            QuickbooksService::class
        );
    }

    private function replaceRedirect()
    {
        $redirect = $this->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redirect->expects($this->once())
            ->method('getRefererUrl')
            ->willReturn(self::TEST_REFERRER_URL);

        /** @var Context $context */
        $context = $this->objectManager->create(
            Context::class,
            ['redirect' => $redirect]
        );

        $this->objectManager->addSharedInstance($context, Context::class);
    }

    private function cleanUpCustomer()
    {
        /** @codingStandardsIgnoreStart */
        include __DIR__ . '/../../../_files/customer_cleanup.php';
        /** @codingStandardsIgnoreEnd */
    }
    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'responseBodyAfterQuery' => [
                    'QueryResponse' => [
                        'Customer' => [
                            'Id' => 1,
                            'SyncToken' => 2,
                            'Title' => 'Mr.',
                            'GivenName' => 'John',
                            'MiddleName' => 'A',
                            'FamilyName' => 'Smith',
                            'FullyQualifiedName' => 'Mr. John A Smith Esq.',
                            'PrintOnCheckName' => 'Mr. John A Smith Esq.',
                            'PrimaryEmailAddr' =>
                                [
                                    'Address' => 'customer@example.com',
                                ],
                            'PrimaryPhone' =>
                                [
                                    'FreeFormNumber' => '3468676',
                                ],
                            'BillAddr' =>
                                [
                                    'Line1' => 'Green str, 67',
                                    'Line2' => '',
                                    'Line3' => '',
                                    'Line4' => '',
                                    'Line5' => '',
                                    'City' => 'TestCity',
                                    'Country' => 'US',
                                    'CountrySubDivisionCode' => 'AL',
                                    'PostalCode' => '75477',
                                ],
                            'ShipAddr' =>
                                [
                                    'Line1' => 'Green str, 67',
                                    'Line2' => '',
                                    'Line3' => '',
                                    'Line4' => '',
                                    'Line5' => '',
                                    'City' => 'TestCity',
                                    'Country' => 'US',
                                    'CountrySubDivisionCode' => 'AL',
                                    'PostalCode' => '75477',
                                ],
                            'CompanyName' => 'TestCompanyName',
                            'DisplayName' => '
                            Mr. John A Smith Esq. @ TestCompanyName(1)',
                        ]
                    ]
                ],
                'responseBodyAfterPost' => [
                    'Customer' => [
                        'Title' => 'Mr.',
                        'GivenName' => 'John',
                        'MiddleName' => 'A',
                        'FamilyName' => 'Smith',
                        'FullyQualifiedName' => 'Mr. John A Smith Esq.',
                        'PrintOnCheckName' => 'Mr. John A Smith Esq.',
                        'PrimaryEmailAddr' =>
                            [
                                'Address' => 'customer@example.com',
                            ],
                        'PrimaryPhone' =>
                            [
                                'FreeFormNumber' => '3468676',
                            ],
                        'BillAddr' =>
                            [
                                'Line1' => 'Green str, 67',
                                'Line2' => '',
                                'Line3' => '',
                                'Line4' => '',
                                'Line5' => '',
                                'City' => 'TestCity',
                                'Country' => 'US',
                                'CountrySubDivisionCode' => 'AL',
                                'PostalCode' => '75477',
                            ],
                        'ShipAddr' =>
                            [
                                'Line1' => 'Green str, 67',
                                'Line2' => '',
                                'Line3' => '',
                                'Line4' => '',
                                'Line5' => '',
                                'City' => 'TestCity',
                                'Country' => 'US',
                                'CountrySubDivisionCode' => 'AL',
                                'PostalCode' => '75477',
                            ],
                        'CompanyName' => 'TestCompanyName',
                        'Id' => 1,
                        'sparse' => true,
                        'SyncToken' => 2,
                        "DisplayName" =>
                            "Mr. John A Smith Esq. @ TestCompanyName(1)"
                    ]
                ],
            ]
        ];
    }

    /** @codingStandardsIgnoreStart */

    public static function loadExecute()
    {
        include __DIR__ . '/../../../_files/customer.php';
        include __DIR__ . '/../../../_files/customer_address.php';
        include __DIR__ . '/../../../_files/company.php';
    }

    public static function loadExecuteException()
    {
        include __DIR__ . '/../../../_files/customer.php';
    }

    /** @codingStandardsIgnoreEnd */
}
