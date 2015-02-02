<?php

namespace AlexDpy\Acl\Model;

use AlexDpy\Acl\Mask\MaskBuilderInterface;

class Permission implements PermissionInterface
{
    /**
     * @var RequesterInterface
     */
    private $requester;

    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * @var MaskBuilderInterface
     */
    private $maskBuilder;

    /**
     * @var bool
     */
    private $persistent;

    /**
     * @param RequesterInterface   $requester
     * @param ResourceInterface    $resource
     * @param MaskBuilderInterface $maskBuilder
     */
    public function __construct(
        RequesterInterface $requester,
        ResourceInterface $resource,
        MaskBuilderInterface $maskBuilder
    ) {
        $this->requester = $requester;
        $this->resource = $resource;
        $this->maskBuilder = $maskBuilder;
        $this->persistent = false;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequester()
    {
        return $this->requester;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getMask()
    {
        return $this->maskBuilder->get();
    }

    /**
     * {@inheritdoc}
     */
    public function setPersistent($persistent)
    {
        $this->persistent = $persistent;
    }

    /**
     * {@inheritdoc}
     */
    public function isPersistent()
    {
        return $this->persistent;
    }

    /**
     * {@inheritdoc}
     */
    public function grant($action)
    {
        $this->maskBuilder->add($action);
    }

    /**
     * {@inheritdoc}
     */
    public function revoke($action)
    {
        $this->maskBuilder->remove($action);
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted($action)
    {
        $requiredMask = $this->maskBuilder->resolveMask($action);

        return $requiredMask === ($this->getMask() & $requiredMask);
    }
}
