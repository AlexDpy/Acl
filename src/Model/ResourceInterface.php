<?php

namespace AlexDpy\Acl\Model;

interface ResourceInterface
{
    /**
     * @return string
     */
    public function getAclResourceIdentifier();
}
