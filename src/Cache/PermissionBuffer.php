<?php

namespace AlexDpy\Acl\Cache;

use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;
use Doctrine\Common\Cache\CacheProvider;

class PermissionBuffer implements PermissionBufferInterface
{
    /**
     * @var array
     */
    protected $buffer;

    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider = null)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function add(PermissionInterface $permission)
    {
        $cacheId = $this->getCacheId($permission->getRequester(), $permission->getResource());

        $this->buffer[$cacheId] = $permission;

        if ($this->hasCacheProvider()) {
            $this->cacheProvider->save($cacheId, $permission);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(PermissionInterface $permission)
    {
        $cacheId = $this->getCacheId($permission->getRequester(), $permission->getResource());

        unset($this->buffer[$cacheId]);

        if ($this->hasCacheProvider()) {
            $this->cacheProvider->delete($cacheId);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(RequesterInterface $requester, ResourceInterface $resource)
    {
        $cacheId = $this->getCacheId($requester, $resource);

        if (isset($this->buffer[$cacheId])) {
            return $this->buffer[$cacheId];
        }

        if ($this->hasCacheProvider()) {
            $permission = $this->cacheProvider->fetch($cacheId);

            if ($permission instanceof PermissionInterface) {
                return $permission;
            }

            $this->cacheProvider->delete($cacheId);
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function hasCacheProvider()
    {
        return null !== $this->cacheProvider;
    }

    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     *
     * @return string
     */
    protected function getCacheId(RequesterInterface $requester, ResourceInterface $resource)
    {
        return $requester->getAclRequesterIdentifier() . $resource->getAclResourceIdentifier();
    }
}
