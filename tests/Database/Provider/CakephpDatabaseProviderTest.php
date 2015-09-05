<?php

namespace Tests\AlexDpy\Acl\Database\Provider;

use AlexDpy\Acl\Database\Provider\CakephpDatabaseProvider;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;

class CakephpDatabaseProviderTest extends AbstractDatabaseProviderTest
{
    public function getDatabaseProvider()
    {
        return new CakephpDatabaseProvider(new Connection(['driver' => new Sqlite(['database' => self::SQLITE_PATH])]));
    }
}
