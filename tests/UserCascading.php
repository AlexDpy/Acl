<?php

namespace Tests\AlexDpy\Acl;

use AlexDpy\Acl\Model\CascadingRequesterInterface;
use AlexDpy\Acl\Model\RequesterInterface;

class UserCascading implements CascadingRequesterInterface
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var UserCascading[]
     */
    private $parents;

    /**
     * @param string          $username
     * @param UserCascading[] $parents
     */
    public function __construct($username, array $parents = [])
    {
        $this->username = $username;
        $this->parents = $parents;
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
        return $this->parents;
    }
}
