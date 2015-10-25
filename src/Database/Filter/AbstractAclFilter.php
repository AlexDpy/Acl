<?php

namespace AlexDpy\Acl\Database\Filter;

use AlexDpy\Acl\Database\Schema\AclSchemaTrait;

abstract class AbstractAclFilter implements AclFilterInterface
{
    use AclSchemaTrait;
}
