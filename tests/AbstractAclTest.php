<?php

namespace Tests\AlexDpy\Acl;

use AlexDpy\Acl\Acl;
use AlexDpy\Acl\AclInterface;
use AlexDpy\Acl\Schema\AclSchema;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\TableNotFoundException;

abstract class AbstractAclTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var AclInterface
     */
    protected $acl;

    protected function setUp()
    {
        if (!class_exists('PDO') || !in_array('sqlite', \PDO::getAvailableDrivers())) {
            $this->markTestSkipped('This test requires SQLite support in your environment.');
        }

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite'], new Configuration());

        $schema = new AclSchema();

        $connection->beginTransaction();
        try {
            foreach ($schema->toDropSql($connection->getDatabasePlatform()) as $query) {
                $connection->exec($query);
            }
            $connection->commit();
        } catch (TableNotFoundException $e) {
            $connection->rollBack();
        }

        $connection->transactional(function(Connection $connection) use ($schema) {
                foreach ($schema->toSql($connection->getDatabasePlatform()) as $query) {
                    $connection->exec($query);
                }
            });

        $this->connection = $connection;
        $this->acl = new Acl($connection);
    }
}
