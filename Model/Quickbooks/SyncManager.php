<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Model\Quickbooks;

use Magento\Framework\Message\ManagerInterface;

/**
 * Class SyncManager
 */
class SyncManager
{
    const OBJECT_TYPE_CUSTOMER = 'Customer';
    const ERROR_MESSAGE = 'Quickbooks: Cannot synchronize object type of %1';

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var EntityInterface[]
     */
    protected $entities;

    /**
     * SyncManager constructor.
     *
     * @param EntityInterface[] $entities
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        array $entities,
        ManagerInterface $messageManager
    ) {
        $this->entities = $entities;
        $this->messageManager = $messageManager;
    }

    /**
     * return synchronize object
     * @param string $type
     * @return object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function entitySynchronize($type)
    {
        if (empty($this->entities[$type])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Unknown type'));
        }

        return $this->entities[$type];
    }

    /**
     * synchronize customer object
     * @return Customer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQbCustomer()
    {
        return $this->entitySynchronize('QbCustomer');
    }

    /**
     * sync entity
     * @param object $object
     * @param bool $showResultMessage
     * @param bool $runNow
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function syncObject($object, $showResultMessage = false, $runNow = false)
    {
        $result = [
            'result' => [],
        ];

        try {
            $result['result'] = $this->postObject($object, $runNow);
        } catch (\Exception $e) {
            $result['result']['errors'][] = [
                'object' => $object,
                'message' => $e->getMessage(),
            ];
        }

        if ($showResultMessage) {
            $this->handleResultMessages([$result]);
        }

        return $result;
    }

    /**
     * @param object $object
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getTypeOfTheObject($object)
    {
        foreach ($this->entities as $entity) {
            if (!$entity->isSupport($object)) {
                continue;
            }

            return $entity->type();
        }

        throw new \Magento\Framework\Exception\LocalizedException(
            __(self::ERROR_MESSAGE, get_class($object))
        );
    }

    /**
     * @param object $object
     * @param bool $runNow
     *
     * @return array
     * @throws \Exception
     */
    protected function postObject($object, $runNow = false)
    {
        foreach ($this->entities as $entity) {
            if (!$entity->isSupport($object)) {
                continue;
            }

            $message = $entity->validate($object);
            if (!empty($message)) {
                throw new \Exception($message);
            }

            return $entity->synchronize($object);
        }

        throw new \Magento\Framework\Exception\LocalizedException(
            __(self::ERROR_MESSAGE, get_class($object))
        );
    }

    /**
     * Synchronize type is Real Time?
     * @return bool
     */
    public function syncTypeRealTime()
    {
        return false;
    }

    /**
     * Validate entity
     * @param object $object
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateObject($object)
    {
        foreach ($this->entities as $entity) {
            if (!$entity->isSupport($object)) {
                continue;
            }

            return $entity->validate($object);
        }

        throw new \Magento\Framework\Exception\LocalizedException(
            __(self::ERROR_MESSAGE, get_class($object))
        );
    }

    /**
     * Merge result
     * @param array $result
     * @return array
     */
    public static function mergeResult(array $result)
    {
        if (empty($result)) {
            return [];
        }

        return array_merge_recursive(...$result);
    }

    /**
     * Prepare Result Messages
     * @param array $result
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handleResultMessages(array $result)
    {
        $messages = [];
        foreach ($result as $_result) {
            if (isset($_result['result']['responses'])) {
                foreach ($_result['result']['responses'] as $response) {
                    $type = $this->getTypeOfTheObject($response['object']);
                    if (!isset($messages[$type]['sync'])) {
                        $messages[$type]['sync'] = 0;
                    }

                    $messages[$type]['sync']++;
                }
            }

            if (isset($_result['result']['errors']) && !empty($_result['result']['errors'])) {
                foreach ($_result['result']['errors'] as $error) {
                    if (is_array($error['message'])) {
                        $errorMessage = implode(', ', array_merge(
                            array_column($error['message'], 'Message'),
                            array_column($error['message'], 'Detail')
                        ));
                    } else {
                        $errorMessage = $error['message'];
                    }
                    $this->messageManager->addWarningMessage(__(
                        'Magento type %2: %1',
                        $errorMessage,
                        $this->getTypeOfTheObject($error['object'])
                    ));
                }
            }
        }

        foreach ($messages as $name => $message) {
            if (!empty($message['sync'])) {
                $this->messageManager
                    ->addSuccessMessage(__('The %2 of %1 were synchronized successfully', $name, $message['sync']));
            }
        }
    }
}
