<?php

namespace Tests\AlexDpy\Acl\Database\Provider;

use AlexDpy\Acl\Database\Provider\DatabaseProviderInterface;
use AlexDpy\Acl\Exception\MaskNotFoundException;
use AlexDpy\Acl\Mask\BasicMaskBuilder;
use AlexDpy\Acl\Model\Permission;
use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\Resource;
use AlexDpy\Acl\Model\ResourceInterface;
use AlexDpy\Acl\Schema\AclSchema;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;

abstract class AbstractDatabaseProviderTest extends \PHPUnit_Framework_TestCase
{
    const SQLITE_PATH = 'tests/sqlite_acl';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DatabaseProviderInterface
     */
    protected $databaseProvider;

    /**
     * @var RequesterInterface
     */
    protected $aliceRequester;
    /**
     * @var RequesterInterface
     */
    protected $bobRequester;
    /**
     * @var RequesterInterface
     */
    protected $malloryRequester;

    /**
     * @var ResourceInterface
     */
    protected $fooResource;
    /**
     * @var ResourceInterface
     */
    protected $barResource;

    protected function setUp()
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
            $this->markTestSkipped('This test requires SQLite support in your environment.');
        }

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => self::SQLITE_PATH], new Configuration());
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
        $this->databaseProvider = $this->getDatabaseProvider();

        $this->aliceRequester = new Requester('alice');
        $this->bobRequester = new Requester('bob');
        $this->malloryRequester = new Requester('mallory');

        $this->fooResource = new Resource('foo');
        $this->barResource = new Resource('bar');

        if ($this->connection->fetchColumn('SELECT COUNT(*) FROM acl_permissions') > 0) {
            throw new \Exception('sqlite database must be reset before each test');
        }
    }

    /**
     * @return DatabaseProviderInterface
     */
    abstract public function getDatabaseProvider();

    public function testFindMaskShouldThrowMaskNotFoundExceptionIfNotFound()
    {
        try {
            $this->databaseProvider->findMask(new Requester('i do not exist'), new Resource('i do not exist'));

            $this->fail(get_class($this) . '::findMask should throw a MaskNotFoundException if permission does not exist');
        } catch (MaskNotFoundException $e) {
        }
    }

    public function testFindMaskShouldReturnIntegerWhenFound()
    {
        $this->insertFixture($this->aliceRequester, $this->fooResource, 1);

        $this->assertInternalType('int', $this->databaseProvider->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testDeletePermission()
    {
        $this->insertFixture($this->aliceRequester, $this->fooResource, 1);

        $this->databaseProvider->deletePermission(
            new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1))
        );

        $this->assertNull($this->findFixture($this->aliceRequester, $this->fooResource));
    }

    public function testUpdatePermission()
    {
        $this->insertFixture($this->aliceRequester, $this->fooResource, 1);

        $this->databaseProvider->updatePermission(
            new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(3))
        );

        $this->assertEquals(3, $this->findFixture($this->aliceRequester, $this->fooResource)['mask']);
    }

    public function testInsertPermission()
    {
        $this->databaseProvider->insertPermission(
            new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(3))
        );

        $this->assertEquals([
            'requester' => $this->aliceRequester->getAclRequesterIdentifier(),
            'resource' => $this->fooResource->getAclResourceIdentifier(),
            'mask' => 3,
        ], $this->findFixture($this->aliceRequester, $this->fooResource));
    }


    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     * @param int                $mask
     */
    private function insertFixture(RequesterInterface $requester, ResourceInterface $resource, $mask)
    {
        $this->connection->insert(
            'acl_permissions',
            [
                'requester' => $requester->getAclRequesterIdentifier(),
                'resource' => $resource->getAclResourceIdentifier(),
                'mask' => $mask,
            ],
            [
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
                'mask' => \PDO::PARAM_INT,
            ]
        );
    }

    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     *
     * @return null|array
     *
     * @throws \Exception
     */
    private function findFixture(RequesterInterface $requester, ResourceInterface $resource)
    {
        $fixtures = $this->connection->fetchAll(
            'SELECT * FROM acl_permissions WHERE requester = :requester AND resource = :resource',
            array(
                'requester' => $requester->getAclRequesterIdentifier(),
                'resource' => $resource->getAclResourceIdentifier(),
            ),
            array(
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
            )
        );

        if (empty($fixtures)) {
            return null;
        }

        if (count($fixtures) > 1) {
            throw new \Exception();
        }

        $fixture = current($fixtures);
        $fixture['mask'] = (int) $fixture['mask'];

        return $fixture;
    }
}
