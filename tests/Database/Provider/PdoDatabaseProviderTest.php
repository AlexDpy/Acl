<?php

namespace Tests\AlexDpy\Acl\Database\Provider;

use \PDO;
use AlexDpy\Acl\Database\Provider\PdoDatabaseProvider;

class PdoDatabaseProviderTest extends AbstractDatabaseProviderTest
{
    public function getDatabaseProvider()
    {
        return new PdoDatabaseProvider(
            new PDO('sqlite:' . self::SQLITE_PATH, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])
        );
    }
}
