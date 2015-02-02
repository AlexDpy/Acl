<?php

namespace AlexDpy\Acl\Model;

interface RequesterInterface
{
    /**
     * @return string
     */
    public function getAclRequesterIdentifier();
}
