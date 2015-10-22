<?php

namespace Tests\AlexDpy\Acl;

use AlexDpy\Acl\Model\CascadingRequesterInterface;
use AlexDpy\Acl\Model\RequesterInterface;

class RoleCascading implements CascadingRequesterInterface
{
    /**
     * @var string
     */
    private $role;

    /**
     * @var UserCascading[]
     */
    private $parents;

    /**
     * @param string          $role
     * @param UserCascading[] $parents
     */
    public function __construct($role, array $parents = [])
    {
        $this->role = $role;
        $this->parents = $parents;
    }

    /**
     * @return string
     */
    public function getAclRequesterIdentifier()
    {
        return $this->role;
    }

    /**
     * @return RequesterInterface[]
     */
    public function getAclParentsRequester()
    {
        return $this->parents;
    }
}
