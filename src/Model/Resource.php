<?php

namespace AlexDpy\Acl\Model;

final class Resource implements ResourceInterface
{
    /**
     * @var string
     */
    private $aclResourceIdentifier;

    /**
     * @param string $aclResourceIdentifier
     */
    public function __construct($aclResourceIdentifier)
    {
        $this->aclResourceIdentifier = $aclResourceIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getAclResourceIdentifier()
    {
        return $this->aclResourceIdentifier;
    }
}
