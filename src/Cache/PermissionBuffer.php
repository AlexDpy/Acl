<?php

namespace AlexDpy\Acl\Cache;

use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\VoidCache;

class PermissionBuffer implements PermissionBufferInterface
{
    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider = null)
    {
        $this->cacheProvider = $cacheProvider === null ? new VoidCache() : $cacheProvider;
        $this->cacheProvider->setNamespace('acl');
    }

    /**
     * {@inheritdoc}
     */
    public function add(PermissionInterface $permission)
    {
        $cacheId = $this->getCacheId($permission->getRequester(), $permission->getResource());
        $this->cacheProvider->save($cacheId, $permission);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(PermissionInterface $permission)
    {
        $cacheId = $this->getCacheId($permission->getRequester(), $permission->getResource());

        $this->cacheProvider->delete($cacheId);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(RequesterInterface $requester, ResourceInterface $resource)
    {
        $cacheId = $this->getCacheId($requester, $resource);

        $permission = $this->cacheProvider->fetch($cacheId);

        if ($permission instanceof PermissionInterface) {
            return $permission;
        }

        $this->cacheProvider->delete($cacheId);

        return null;
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
