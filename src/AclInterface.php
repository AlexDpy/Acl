<?php

namespace AlexDpy\Acl;

use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;

interface AclInterface
{
    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     * @param string             $action
     *
     * @return bool
     */
    public function isGranted(RequesterInterface $requester, ResourceInterface $resource, $action);

    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     * @param string|string[]    $actions
     */
    public function grant(RequesterInterface $requester, ResourceInterface $resource, $actions);

    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     * @param string|string[]    $actions
     */
    public function revoke(RequesterInterface $requester, ResourceInterface $resource, $actions);
}
