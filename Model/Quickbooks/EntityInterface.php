<?php
namespace TNW\QuickbooksBasic\Model\Quickbooks;

/**
 * Interface synchronize object
 */
interface EntityInterface
{
    /**
     * validate entity
     * @param object $object
     * @return string
     */
    public function validate($object);

    /**
     * synchronize process
     * @param object $object
     * @return array
     */
    public function synchronize($object);

    /**
     * get entity type
     * @return string
     */
    public function type();

    /**
     * entity is support
     * @param object $object
     * @return bool
     */
    public function isSupport($object);
}
