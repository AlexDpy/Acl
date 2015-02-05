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
     * {@inheritdoc}
     */
    public function add(PermissionInterface $permission)
    {
        $this->buffer[$permission->getRequester()->getAclRequesterIdentifier()]
            [$permission->getResource()->getAclResourceIdentifier()] = $permission;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(PermissionInterface $permission)
    {
        unset($this->buffer[$permission->getRequester()->getAclRequesterIdentifier()]
            [$permission->getResource()->getAclResourceIdentifier()]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(RequesterInterface $requester, ResourceInterface $resource)
    {
        if (true === $this->has($requester, $resource)) {
            return $this->buffer[$requester->getAclRequesterIdentifier()][$resource->getAclResourceIdentifier()];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function has(RequesterInterface $requester, ResourceInterface $resource)
    {
        return isset($this->buffer[$requester->getAclRequesterIdentifier()][$resource->getAclResourceIdentifier()]);
    }
}
