<?php

namespace Tests\AlexDpy\Acl;

use AlexDpy\Acl\Model\CascadingRequesterInterface;
use AlexDpy\Acl\Model\RequesterInterface;

class UserCircularCascading implements CascadingRequesterInterface
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string[]
     */
    private $parents;

    /**
     * @param string   $username
     * @param string[] $parents
     */
    public function __construct($username, array $parents = array())
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
        $parents = array();

        foreach ($this->parents as $parent) {
            $parents[] = new self($parent, array($this->username));
        }

        return $parents;
    }
}
