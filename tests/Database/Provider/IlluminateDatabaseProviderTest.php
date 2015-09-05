<?php

namespace Tests\AlexDpy\Acl\Database\Provider;

use AlexDpy\Acl\Database\Provider\IlluminateDatabaseProvider;
use Illuminate\Database\Capsule\Manager;

class IlluminateDatabaseProviderTest extends AbstractDatabaseProviderTest
{
    public function getDatabaseProvider()
    {
        $capsule = new Manager();

        $capsule->addConnection([
            'driver'    => 'sqlite',
            'database'  => self::SQLITE_PATH,
        ]);

        return new IlluminateDatabaseProvider($capsule->getConnection('default'));
    }
}
