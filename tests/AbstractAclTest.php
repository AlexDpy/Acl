<?php

namespace Tests\AlexDpy\Acl;

use AlexDpy\Acl\Acl;
use AlexDpy\Acl\AclInterface;
use AlexDpy\Acl\Schema\AclSchema;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Prophecy\Prophecy\ObjectProphecy;

abstract class AbstractAclTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ObjectProphecy
     */
    protected $permissionBuffer;

    /**
     * @var AclInterface
     */
    protected $acl;

    protected function setUp()
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
            $this->markTestSkipped('This test requires SQLite support in your environment.');
        }

        $connection = DriverManager::getConnection(array('driver' => 'pdo_sqlite'), new Configuration());

        $schema = new AclSchema();

        $connection->beginTransaction();
        try {
            foreach ($schema->toDropSql($connection->getDatabasePlatform()) as $query) {
                $connection->exec($query);
            }
            $connection->commit();
        } catch (DBALException $e) { // @see TableNotFoundException for doctrine/dbal > 2.5
            $connection->rollBack();
        }

        $connection->transactional(function (Connection $connection) use ($schema) {
                foreach ($schema->toSql($connection->getDatabasePlatform()) as $query) {
                    $connection->exec($query);
                }
            });

        $this->connection = $connection;
        $this->permissionBuffer = $this->prophesize('AlexDpy\Acl\Cache\PermissionBufferInterface');
        $this->acl = new Acl($connection, $this->permissionBuffer->reveal());
    }
}
