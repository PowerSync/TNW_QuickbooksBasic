<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Model\Quickbooks;

use Magento\Config\Model\Config\Factory;
use Magento\Customer\Api\Data\AddressExtension;
use Magento\Customer\Api\Data\AddressExtensionFactory;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use TNW\QuickbooksBasic\Model\Config as QuickbooksConfig;
use TNW\QuickbooksBasic\Model\Quickbooks;
use TNW\QuickbooksBasic\Model\ResourceModel\Customer as CustomerResource;
use TNW\QuickbooksBasic\Service\Quickbooks as QuickbooksService;
use Magento\Customer\Model\CustomerFactory;
use \Magento\Store\Model\StoreManagerInterface;

/**
 * Class Customer
 */
class Customer extends Quickbooks implements EntityInterface
{
    const API_CREATE = 'company/:companyId/customer';
    const API_READ = 'company/:companyId/customer/:entityId';
    const API_UPDATE = 'company/:companyId/customer';
    //API_UPDATE should be 'company/:companyId/customer?operation=update'
    //but it's not working - response code - 401, error - 3200
    //more details on
    // http://stackoverflow.com/questions/24038662/update-a-customer-in-qbo-api-3

    const QUICKBOOKS_CUSTOMER_URL = '/app/customerdetail?nameId=';
    const CUSTOMER_ENTITY_ID = 'entity_id';
    const ADDRESS_ENTITY_ID = 'address_id';
    const QUICKBOOKS_ID = 'quickbooks_id';
    const QUICKBOOKS_SYNC_TOKEN = 'quickbooks_sync_token';
    const QUICKBOOKS_SYNC_STATUS = 'quickbooks_sync_status';
    const CUSTOMER_QUICKBOOKS_TABLE = 'customer_entity';
    const SHIPPINGG_ADDRESS_QUICKBOOKS_TABLE = 'quickbooks_shipping_address';
    const BILLING_ADDRESS_QUICKBOOKS_TABLE = 'quickbooks_billing_address';

    const REGISTRY =
        'quickbooks_customer_save_after_data_object_observer_customer';

    const DISPLAY_NAME_MAX_LENGTH = 100;
    const MIDDLE_NAME_MAX_LENGTH = 25;
    const FAMILY_NAME_MAX_LENGTH = 25;
    const GIVEN_NAME_MAX_LENGTH = 25;
    const TITLE_MAX_LENGTH = 15;
    const PRINT_ON_CHECK_NAME_MAX_LENGTH = 110;

    const BILLING = 'billing_address';
    const SHIPPING = 'shipping_address';

    const API_QUERY = /** @lang text */
        'select * from Customer';

    /** @var CustomerResource */
    protected $customerResource;

    /** @var AddressFactory */
    protected $addressFactory;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Magento\Customer\Api\Data\AddressExtensionFactory */
    protected $addressExtensionFactory;

    /** @var  ManagerInterface */
    protected $messageManager;

    /** @var  array */
    private $parentQuickbooksIdForContact = [];
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $manager;

    /**
     * Customer constructor.
     * @param Factory $configFactory
     * @param ScopeConfigInterface $config
     * @param UrlInterface $urlBuilder
     * @param EncoderInterface $jsonEncoder
     * @param DecoderInterface $jsonDecoder
     * @param LoggerInterface $logger
     * @param QuickbooksConfig $quickbooksConfig
     * @param QuickbooksService $quickbooksService
     * @param CustomerResource $customerResource
     * @param AddressFactory $addressFactory
     * @param ResourceConnection $resourceConnection
     * @param AddressExtensionFactory $extensionFactory
     * @param ManagerInterface $messageManager
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $manager
     */
    public function __construct(
        Factory $configFactory,
        ScopeConfigInterface $config,
        UrlInterface $urlBuilder,
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        LoggerInterface $logger,
        QuickbooksConfig $quickbooksConfig,
        QuickbooksService $quickbooksService,
        CustomerResource $customerResource,
        AddressFactory $addressFactory,
        ResourceConnection $resourceConnection,
        AddressExtensionFactory $extensionFactory,
        ManagerInterface $messageManager,
        CustomerFactory $customerFactory,
        StoreManagerInterface $manager
    ) {
        $this->customerResource = $customerResource;
        $this->addressFactory = $addressFactory;
        $this->resourceConnection = $resourceConnection;
        $this->addressExtensionFactory = $extensionFactory;
        $this->messageManager = $messageManager;
        $this->customerFactory = $customerFactory;
        $this->manager = $manager;
        parent::__construct(
            $configFactory,
            $config,
            $urlBuilder,
            $jsonEncoder,
            $jsonDecoder,
            $logger,
            $quickbooksConfig,
            $quickbooksService
        );
    }

    /**
     * @param array $data
     */
    public function addParentQuickbooksIdForContact(array $data)
    {
        $this->parentQuickbooksIdForContact += $data;
    }

    /**
     * @param CustomerInterface $customer
     *
     * @return array
     * @throws \Exception
     */
    public function postCustomer(CustomerInterface $customer)
    {
        $return = $this->syncAccount($customer);
        if (!empty($return['errors'])) {
            return $return;
        }

        return $this->syncContact($customer);
    }

    /**
     * @param CustomerInterface $customer
     * @return mixed
     * @throws \Zend_Http_Client_Exception
     */
    public function syncAccount(CustomerInterface $customer)
    {
        $data = $this->prepareAccountData($customer);
        if (empty($data['data'])) {
            return [];
        }

        $encodedData = $this->jsonEncoder->encode($data['data']);
        $response = $this->quickbooksService->post($encodedData, $data['uri']);
        $responseBody = $this->getQuickbooksService()->checkResponse($response);
        if (empty($responseBody['Fault']['Error'])) {
            $this->addParentQuickbooksIdForContact([
                $customer->getId() => $responseBody['Customer']['Id']
            ]);

            $return['responses'][] = [
                'object' => $customer,
                'response' => $responseBody
            ];
        } else {
            $return['errors'][] = [
                'object' => $customer,
                'message' => $responseBody['Fault']['Error'],
            ];
        }

        return $return;
    }

    /**
     * @param CustomerInterface $customer
     * @return array
     * @throws \Zend_Http_Client_Exception
     */
    public function prepareAccountData(CustomerInterface $customer)
    {
        /** @var string $companyName */
        $companyName = '';
        //prepare base customer data
        /** @var array $data */
        $data = [];
        try {
            /** @var \Magento\Customer\Model\Customer $customerFactory */
            $customerFactory = $this->customerFactory->create();
            $customerFactory->setStoreId($this->manager->getStore()->getStoreId());
            $customerModel = $customerFactory->load($customer->getId());
            $billindAddress = $customerModel->getDefaultBillingAddress();
            if (($billindAddress)) {
                $companyName = ($billindAddress->getCompany() !== null) ? $billindAddress->getCompany() : '';
            }
            if (empty($companyName)) {
                $shippingAddress = $customerModel->getDefaultShippingAddress();
                if ($shippingAddress) {
                    $companyName = ($shippingAddress->getCompany() !== null) ? $shippingAddress->getCompany() : '';
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->debug(__('QUICKBOOKS Could not get customer by id.'));
        }
        if (empty($companyName)) {
            return [];
        }
        $companyName = $this->correctCompanyName($companyName);
        $parent = $this->lookupQuickbooksParentByCompanyOrEmail($companyName, $customer->getEmail());
        if (isset($parent['Id'])) {
            $data['Id'] = $parent['Id'];
            $data['sparse'] = true;
            $data['SyncToken'] = $parent['SyncToken'];

            $uri = self::API_UPDATE;
        } else {
            $data['CompanyName'] = $companyName;
            $data['DisplayName'] = sprintf('%s (company)', $companyName);
            $uri = self::API_CREATE;
        }

        return ['data' => $data, 'uri' => $uri];
    }

    /**
     * @param $companyName
     * @return mixed
     */
    public function correctCompanyName($companyName)
    {
        /**
         * https://quickbooks.intuit.com/learn-support/en-us/manage-intuit-account/acceptable-characters-in-quickbooks-online/00/186243
         */
        $companyName = preg_replace("|[^a-zA-Z0-9,\.?@&!#'`\*\(\)_\-;+]|", " ", $companyName);

        return $companyName;
    }

    /**
     * @param string $company
     * @param string $email
     * @return array
     * @throws \Zend_Http_Client_Exception
     */
    protected function lookupQuickbooksParentByCompanyOrEmail($company, $email)
    {
        if (!empty($company)) {
            /** @var \Zend_Http_Response $response */
            $companyResponse = $this->query(sprintf(
                "SELECT * FROM Customer WHERE DisplayName = '%s'",
                addslashes(sprintf('%s (company)', $company))
            ));
            /** @var array $companyList */
            $companyList = $this->getQuickbooksService()->checkResponse($companyResponse);
            if (isset($companyList['QueryResponse']['Customer'][0]['Id'])) {
                return $companyList['QueryResponse']['Customer'][0];
            }
        }

        $customer = [];
        /** @var \Zend_Http_Response $response */
        $response = $this->query(sprintf(
            "SELECT ParentRef FROM Customer WHERE PrimaryEmailAddr = '%s'",
            addslashes($email)
        ));

        /** @var array $customerList */
        $customerList = $this->getQuickbooksService()->checkResponse($response);

        if (isset($customerList['QueryResponse']['Customer'][0]['Id'])) {
            $customerList['QueryResponse']['Customer'] = $customerList['QueryResponse']['Customer'][0];
        }

        if (!empty($customerList['QueryResponse']['Customer']['ParentRef'])) {
            $response = $this->query(sprintf(
                "SELECT * FROM Customer WHERE Id = '%d'",
                $customerList['QueryResponse']['Customer']['ParentRef']
            ));
        }
        /** @var array $customerList */
        $customerList = $this->getQuickbooksService()->checkResponse($response);
        if (isset($customerList['QueryResponse']['Customer'])) {
            $customer = $customerList['QueryResponse']['Customer'];
        }
        if (isset($customerList['QueryResponse']['Customer'][0]['Id'])) {
            $customer = $customerList['QueryResponse']['Customer'][0];
        }

        return $customer;
    }

    /**
     * @param CustomerInterface $customer
     * @return mixed
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function syncContact(CustomerInterface $customer)
    {
        $data = $this->prepareCustomerData($customer);
        $encodedData = $this->jsonEncoder->encode($data['data']);
        $response = $this->quickbooksService->post($encodedData, $data['uri']);
        $responseBody = $this->getQuickbooksService()->checkResponse($response);
        if (empty($responseBody['Fault']['Error'])) {
            $this->syncCustomerQuickbooksResponse($responseBody, $customer);
            $return['responses'][] = [
                'object' => $customer,
                'response' => $responseBody
            ];
        } else {
            $return['errors'][] = [
                'object' => $customer,
                'message' => $responseBody['Fault']['Error'],
            ];
        }

        return $return;
    }

    /**
     * prepare customer data array from customer
     *
     * @param CustomerInterface $customer
     *
     * @return array
     * @throws \Exception
     */
    public function prepareCustomerData(
        CustomerInterface $customer
    ) {
        //prepare base customer data
        /** @var array $data */
        $data = [
            'Title' => \mb_substr(
                $customer->getPrefix(),
                0,
                self::TITLE_MAX_LENGTH
            ),
            'GivenName' => \mb_substr(
                $customer->getFirstname(),
                0,
                self::GIVEN_NAME_MAX_LENGTH
            ),
            'MiddleName' => \mb_substr(
                $customer->getMiddlename(),
                0,
                self::MIDDLE_NAME_MAX_LENGTH
            ),
            'FamilyName' => \mb_substr(
                $customer->getLastname(),
                0,
                self::FAMILY_NAME_MAX_LENGTH
            ),
            'FullyQualifiedName' => $this->getCustomerName($customer),
            'PrintOnCheckName' => \mb_substr(
                $this->getCustomerName($customer),
                0,
                self::PRINT_ON_CHECK_NAME_MAX_LENGTH
            ),
            'PrimaryEmailAddr' => [
                'Address' => $customer->getEmail(),
            ],
        ];

        /** @var string $companyName */
        $companyName = '';

        $customerAddresses = $customer->getAddresses();

        /** @var string $billingAddress */
        $billingAddressId = $customer->getDefaultBilling();

        //add billing address information
        if ($billingAddressId && count($customerAddresses)) {
            /** @var \Magento\Customer\Model\Address $billingAddress */
            $billingAddress = $this->addressFactory->create();
            $billingAddress->load($billingAddressId);

            /** @var AddressInterface $billingAddressData */
            $billingAddressData = $billingAddress->getDataModel();
            $billingAddressData = $this->setAddressQuickbooksId(
                $billingAddressData,
                self::BILLING
            );

            $companyName = $billingAddressData->getCompany();

            $data['PrimaryPhone'] = [
                'FreeFormNumber' => $billingAddressData->getTelephone(),
            ];
            $data['BillAddr'] = [
                'Line1' => $this->getStreetLine($billingAddressData, 1),
                'Line2' => $this->getStreetLine($billingAddressData, 2),
                'Line3' => $this->getStreetLine($billingAddressData, 3),
                'Line4' => $this->getStreetLine($billingAddressData, 4),
                'Line5' => $this->getStreetLine($billingAddressData, 5),
                'City' => $billingAddressData->getCity(),
                'Country' => $billingAddressData->getCountryId(),
                'CountrySubDivisionCode' =>
                    $billingAddressData->getRegion()->getRegionCode(),
                'PostalCode' => $billingAddressData->getPostcode(),
            ];
            $billingAddressQuickbooksId =
                $billingAddressData->getExtensionAttributes() ?
                    $billingAddressData->getExtensionAttributes()->getQuickbooksId() :
                    null;

            if ($billingAddressQuickbooksId) {
                $data['BillAddr']['Id'] = $billingAddressQuickbooksId;
            }
        }

        /** @var string $shippingAddressId */
        $shippingAddressId = $customer->getDefaultShipping();

        //add shipping address information
        if ($shippingAddressId && count($customerAddresses)) {

            /** @var \Magento\Customer\Model\Address $shippingAddress */
            $shippingAddress = $this->addressFactory->create();
            $shippingAddress->load($shippingAddressId);

            /** @var AddressInterface $billingAddressData */
            $shippingAddressData = $shippingAddress->getDataModel();

            $shippingAddressData = $this->setAddressQuickbooksId(
                $shippingAddressData,
                self::SHIPPING
            );

            if (!$companyName) {
                $companyName = $shippingAddressData->getCompany();
            }

            $data['ShipAddr'] = [
                'Line1' => $this->getStreetLine($shippingAddressData, 1),
                'Line2' => $this->getStreetLine($shippingAddressData, 2),
                'Line3' => $this->getStreetLine($shippingAddressData, 3),
                'Line4' => $this->getStreetLine($shippingAddressData, 4),
                'Line5' => $this->getStreetLine($shippingAddressData, 5),
                'City' => $shippingAddressData->getCity(),
                'Country' => $shippingAddressData->getCountryId(),
                'CountrySubDivisionCode' =>
                    $shippingAddressData->getRegion()->getRegionCode(),
                'PostalCode' => $shippingAddressData->getPostcode(),
            ];

            $shippingAddressQuickbooksId =
                $shippingAddressData->getExtensionAttributes() ?
                    $shippingAddressData->getExtensionAttributes()->getQuickbooksId() :
                    null;

            if ($shippingAddressQuickbooksId) {
                $data['ShipAddr']['Id'] = $shippingAddressQuickbooksId;
            }
        }

        $data['CompanyName'] = $companyName;

        list($data, $uri) = $this->addQBCustomerData(
            $customer,
            $data,
            $companyName
        );

        if (isset($this->parentQuickbooksIdForContact[$customer->getId()])) {
            $data['ParentRef']['value'] = $this->parentQuickbooksIdForContact[$customer->getId()];
            $data['Job'] = true;
            $data['BillWithParent'] = true;
        }

        return ['data' => $data, 'uri' => $uri];
    }

    /**
     * @param string $email
     *
     * @return array
     * @throws \Zend_Http_Client_Exception
     */
    public function lookupQuickbooksCustomerByEmail($email)
    {
        /** @var array $customer */
        $customer = [];

        /** @var string $queryString */
        $queryString =
            self::API_QUERY . " where PrimaryEmailAddr = '" . $email . "'";

        /** @var \Zend_Http_Response $response */
        $response = $this->query($queryString);

        /** @var array $customerList */
        $customerList = $this->getQuickbooksService()->checkResponse($response);

        if (isset($customerList['QueryResponse']['Customer']['Id'])) {
            $customer = $customerList['QueryResponse']['Customer'];
        }

        if (isset($customerList['QueryResponse']['Customer'][0]['Id'])) {
            $customer = $customerList['QueryResponse']['Customer'][0];
        }

        return $customer;
    }

    /**
     * @param int $quickbooksId
     *
     * @return null|\Zend_Http_Response
     */
    public function read($quickbooksId)
    {
        return $this->quickbooksService->read(
            Customer::API_READ,
            $quickbooksId
        );
    }

    /**
     * @param CustomerInterface $customer
     * @param string            $companyName
     *
     * @return string
     */
    public function getDisplayName(
        CustomerInterface $customer,
        $companyName
    ) {
        $customerName =
            $this->getCustomerName($customer);
        $id = ' (' . $customer->getId() . ')';
        $displayName = $customerName . $id;
        $displayName = $this->correctCompanyName($displayName);
        if (\mb_strlen($displayName) > self::DISPLAY_NAME_MAX_LENGTH) {
            $customerNameMaxLen = self::DISPLAY_NAME_MAX_LENGTH - \mb_strlen($id);
            $customerName = \mb_substr($customerName, 0, $customerNameMaxLen - 3);
            $displayName = $customerName . '...' . $id;
        }

        return $displayName;
    }

    /**
     * @param array             $data
     * @param CustomerInterface $customer
     */
    public function syncCustomerQuickbooksResponse(
        array $data,
        CustomerInterface $customer
    ) {
        $this->customerResource->syncQuickbooksResponse($data, $customer);
    }

    /**
     * @param CustomerInterface $customer
     *
     * @return string
     */
    protected function getCustomerName(
        CustomerInterface $customer
    ) {
        $name = '';

        $prefix = $customer->getPrefix();
        if ($prefix) {
            $name .= $prefix . ' ';
        }

        $name .= $customer->getFirstname();

        $middleName = $customer->getMiddlename();
        if ($middleName) {
            $name .= ' ' . $middleName;
        }

        $name .= ' ' . $customer->getLastname();

        $suffix = $customer->getSuffix();
        if ($suffix) {
            $name .= ' ' . $suffix;
        }

        return $name;
    }

    /**
     * @param AddressInterface $address
     * @param                  $number
     *
     * @return string
     */
    protected function getStreetLine(
        AddressInterface $address,
        $number
    ) {
        $lines = $address->getStreet();

        return isset($lines[$number - 1]) ? $lines[$number - 1] : '';
    }

    /**
     * @param AddressInterface $address
     * @param                  $type
     *
     * @return AddressInterface
     */
    protected function setAddressQuickbooksId(AddressInterface $address, $type)
    {
        $connection = $this->resourceConnection->getConnection();

        /** @var \Magento\Framework\DB\Select $select */
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName(self::BILLING_ADDRESS_QUICKBOOKS_TABLE))
            ->where(self::ADDRESS_ENTITY_ID . ' = ' . $address->getId());

        if ($type === self::SHIPPING) {
            $select = $connection->select()
                ->from($this->resourceConnection->getTableName(self::SHIPPINGG_ADDRESS_QUICKBOOKS_TABLE))
                ->where(self::ADDRESS_ENTITY_ID . ' = ' . $address->getId());
        }

        /** @var array $row */
        $row = $connection->fetchRow($select);

        if ($row) {
            /** @var AddressExtension $customerExtension */
            $addressExtension =
                $address->getExtensionAttributes() ? :
                    $this->addressExtensionFactory->create();
            $addressExtension->setQuickbooksId($row[self::QUICKBOOKS_ID]);

            $address->setExtensionAttributes($addressExtension);
        }

        return $address;
    }

    /**
     * @param CustomerInterface $customer
     * @param array             $data
     * @param string            $companyName
     *
     * @return array
     * @throws \Exception
     */
    private function addQBCustomerData(
        CustomerInterface $customer,
        $data,
        $companyName
    ) {
        /** @var string|null $customerQuickbooksId */
        $customerQuickbooksId =
            $customer->getCustomAttribute(Customer::QUICKBOOKS_ID) ?
                $customer->getCustomAttribute(Customer::QUICKBOOKS_ID)->getValue() :
                null;

        if ($customerQuickbooksId) {
            /** @var array $customerUpdateData */
            $customerUpdateData = $this->checkCustomerOnQB(
                $customerQuickbooksId
            );

            /**
             * if customer is not active or does not exists on QB
             * - try to lookup by email or create new one
             */
            if (!$customerUpdateData['active']) {
                $customerQuickbooksId = null;
            }
        }

        if (!$customerQuickbooksId) {
            /** @var array $customerUpdateData */
            $customerUpdateData =
                $this->lookupQuickbooksCustomerByEmail($customer->getEmail());

            $customerQuickbooksId =
                (!empty($customerUpdateData) && isset($customerUpdateData['Id'])) ?
                    $customerUpdateData['Id'] :
                    $customerQuickbooksId;
        }

        if ($customerQuickbooksId) {
            $data['Id'] = $customerQuickbooksId;
            $data['sparse'] = true;

            /**  retrieve SyncToken for existing Quickbooks customer */
            if (isset($customerUpdateData['SyncToken'])) {
                $data['SyncToken'] = $customerUpdateData['SyncToken'];
            } else {
                /** @var \Zend_Http_Response $response */
                $response = $this->read($customerQuickbooksId);

                /** @var array $responseData */
                $responseData =
                    $this->quickbooksService->checkResponse($response);

                if (empty($responseData)) {
                    $this->throwQuickbooksException(new \Exception(
                        'Synchronization of customer failed. Please try later.'
                    ));
                }

                $data['SyncToken'] = $responseData['Customer']['SyncToken'];
            }

            /** @var string $uri */
            $uri = self::API_UPDATE;
        } else {
            //set Display name for new quickbooks customer
            $data['DisplayName'] = $this->getDisplayName(
                $customer,
                $companyName
            );

            /** @var string $uri */
            $uri = self::API_CREATE;
        }

        return [$data, $uri];
    }

    /**
     * @param string $customerQuickbooksId
     *
     * @return array
     */
    private function checkCustomerOnQB($customerQuickbooksId)
    {
        /** @var array $customerData */
        $customerData = [
            'active' => false,
            'SyncToken' => null,
        ];

        if (is_string($customerQuickbooksId)) {
            /** @var int $customerQuickbooksId */
            $customerQuickbooksId = (int) $customerQuickbooksId;
        }

        /** @var array $readResult */
        $readResult = $this->quickbooksService->checkResponse(
            $this->read($customerQuickbooksId)
        );

        if (isset($readResult['Customer']['Active']) &&
            $readResult['Customer']['Active']
        ) {
            $customerData['active'] = true;
            $customerData['SyncToken'] =
                $readResult['Customer']['SyncToken'];
        }

        return $customerData;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($object)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function synchronize($object)
    {
        return $this->postCustomer($object);
    }

    /**
     * {@inheritdoc}
     */
    public function type()
    {
        return SyncManager::OBJECT_TYPE_CUSTOMER;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupport($object)
    {
        return $object instanceof CustomerInterface;
    }
}
