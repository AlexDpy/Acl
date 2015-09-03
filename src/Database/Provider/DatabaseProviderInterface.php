<?php

namespace AlexDpy\Acl\Database\Provider;

use AlexDpy\Acl\Exception\MaskNotFoundException;
use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;

interface DatabaseProviderInterface
{
    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     *
     * @return int
     *
     * @throws MaskNotFoundException
     */
    public function findMask(RequesterInterface $requester, ResourceInterface $resource);

    /**
     * @param PermissionInterface $permission
     */
    public function deletePermission(PermissionInterface $permission);

    /**
     * @param PermissionInterface $permission
     */
    public function updatePermission(PermissionInterface $permission);

    /**
     * @param PermissionInterface $permission
     */
    public function insertPermission(PermissionInterface $permission);
}
