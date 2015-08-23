<?php

namespace AlexDpy\Acl\Model;

final class Requester implements RequesterInterface
{
    /**
     * @var string
     */
    private $aclRequesterIdentifier;

    /**
     * @param string $aclRequesterIdentifier
     */
    public function __construct($aclRequesterIdentifier)
    {
        $this->aclRequesterIdentifier = $aclRequesterIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getAclRequesterIdentifier()
    {
        return $this->aclRequesterIdentifier;
    }
}
