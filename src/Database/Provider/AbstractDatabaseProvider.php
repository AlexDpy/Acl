<?php

namespace AlexDpy\Acl\Database\Provider;

use AlexDpy\Acl\Database\Schema\AclSchemaTrait;

abstract class AbstractDatabaseProvider implements DatabaseProviderInterface
{
    use AclSchemaTrait;
}
