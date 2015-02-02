<?php

namespace AlexDpy\Acl\Cache;

use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;

class PermissionBuffer implements PermissionBufferInterface
{
    /**
     * @var array
     */
    protected $buffer;

    /**
     * @param PermissionInterface $permission
     *
     * @return PermissionBufferInterface
     */
    public function add(PermissionInterface $permission)
    {
        $this->buffer[$permission->getRequester()->getAclRequesterIdentifier()]
            [$permission->getResource()->getAclResourceIdentifier()] = $permission;

        return $this;
    }

    /**
     * @param PermissionInterface $permission
     *
     * @return PermissionBufferInterface
     */
    public function remove(PermissionInterface $permission)
    {
        unset($this->buffer[$permission->getRequester()->getAclRequesterIdentifier()]
            [$permission->getResource()->getAclResourceIdentifier()]);

        return $this;
    }

    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     *
     * @return PermissionInterface|null
     */
    public function get(RequesterInterface $requester, ResourceInterface $resource)
    {
        if (isset($this->buffer[$requester->getAclRequesterIdentifier()][$resource->getAclResourceIdentifier()])) {
            return $this->buffer[$requester->getAclRequesterIdentifier()][$resource->getAclResourceIdentifier()];
        }

        return null;
    }
}
