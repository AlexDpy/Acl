<?php

namespace AlexDpy\Acl\Model;

interface PermissionInterface
{
    /**
     * @return ResourceInterface
     */
    public function getResource();

    /**
     * @param string $action
     *
     * @return bool
     */
    public function isGranted($action);

    /**
     * @param string $action
     */
    public function grant($action);

    /**
     * @param string $action
     */
    public function revoke($action);

    /**
     * @param bool $persistent
     */
    public function setPersistent($persistent);

    /**
     * @return bool
     */
    public function isPersistent();

    /**
     * @return int
     */
    public function getMask();

    /**
     * @return RequesterInterface
     */
    public function getRequester();
}
