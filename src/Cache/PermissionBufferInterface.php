<?php

namespace AlexDpy\Acl\Cache;

use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;

interface PermissionBufferInterface
{
    /**
     * @param PermissionInterface $permission
     *
     * @return PermissionBufferInterface
     */
    public function add(PermissionInterface $permission);

    /**
     * @param PermissionInterface $permission
     *
     * @return PermissionBufferInterface
     */
    public function remove(PermissionInterface $permission);

    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     *
     * @return PermissionInterface|null
     */
    public function get(RequesterInterface $requester, ResourceInterface $resource);
}
