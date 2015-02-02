<?php

namespace AlexDpy\Acl\Exception;

use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;

class PermissionNotFoundException extends NotFoundException
{
    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     */
    public function __construct(RequesterInterface $requester, ResourceInterface $resource)
    {
        parent::__construct(sprintf(
            'Unable to find any permission where requester is "%s" and resource is "%s"',
            $requester->getAclRequesterIdentifier(),
            $resource->getAclResourceIdentifier()
        ));
    }
}
