<?php

namespace Tests\AlexDpy\Acl;

use AlexDpy\Acl\Model\CascadingRequesterInterface;
use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\RequesterInterface;

class User implements CascadingRequesterInterface
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string[]
     */
    private $roles;

    /**
     * @param string   $username
     * @param string[] $roles
     */
    public function __construct($username, array $roles = [])
    {
        $this->username = $username;
        $this->roles = $roles;
    }

    /**
     * @return string
     */
    public function getAclRequesterIdentifier()
    {
        return 'User-' . $this->username;
    }

    /**
     * @return RequesterInterface[]
     */
    public function getAclParentsRequester()
    {
        $parents = [];

        foreach ($this->roles as $role) {
            $parents[] = new Requester($role);
        }

        return $parents;
    }
}
