<?php

namespace Tests\AlexDpy\Acl\Database\Provider;

use AlexDpy\Acl\Database\Provider\DoctrineDbalProvider;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

class DoctrineDbalProviderTest extends AbstractDatabaseProviderTest
{
    public function getDatabaseProvider()
    {
        return new DoctrineDbalProvider(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => self::SQLITE_PATH], new Configuration())
        );
    }
}
