<?php

namespace AlexDpy\Acl\Model;

interface CascadingRequesterInterface extends RequesterInterface
{
    /**
     * @return RequesterInterface[]
     */
    public function getAclParentsRequester();
}
