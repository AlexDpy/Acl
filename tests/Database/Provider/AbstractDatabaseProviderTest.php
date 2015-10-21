<?php

namespace Tests\AlexDpy\Acl\Database\Provider;

use \PDO;
use \PDOStatement;
use AlexDpy\Acl\Database\Provider\DatabaseProviderInterface;
use AlexDpy\Acl\Exception\MaskNotFoundException;
use AlexDpy\Acl\Mask\BasicMaskBuilder;
use AlexDpy\Acl\Model\Permission;
use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\Resource;
use AlexDpy\Acl\Model\ResourceInterface;

abstract class AbstractDatabaseProviderTest extends \PHPUnit_Framework_TestCase
{
    const SQLITE_PATH = 'tests/sqlite_acl';

    /**
     * @var PDO
     */
    protected $pdo;

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
        if (!in_array('sqlite', PDO::getAvailableDrivers())) {
            $this->markTestSkipped('This test requires SQLite support in your environment.');
        }

        $this->pdo = new PDO('sqlite:' . self::SQLITE_PATH, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        try {
            $this->pdo->prepare('DROP TABLE acl_permissions')->execute();
        } catch (\PDOException $e) {
        }

        $create = <<<SQL
CREATE TABLE acl_permissions (
  requester VARCHAR(255) NOT NULL,
  resource VARCHAR(255) NOT NULL,
  mask INTEGER NOT NULL,
  PRIMARY KEY(requester, resource)
)
SQL;

        $this->pdo->prepare($create)->execute();

        $this->databaseProvider = $this->getDatabaseProvider();

        $this->aliceRequester = new Requester('alice');
        $this->bobRequester = new Requester('bob');
        $this->malloryRequester = new Requester('mallory');

        $this->fooResource = new Resource('foo');
        $this->barResource = new Resource('bar');

        if ($this->pdo->query('SELECT COUNT(*) FROM acl_permissions')->fetchColumn() > 0) {
            throw new \Exception('sqlite database must be reset before each test');
        }
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        try {
            $this->pdo->prepare('DROP TABLE acl_permissions')->execute();
        } catch (\PDOException $e) {
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
        $sth = $this->getPdoStatement(
            'INSERT INTO acl_permissions (requester, resource, mask) VALUES (:requester, :resource, :mask)'
        );

        $sth->bindValue(':mask', $mask, PDO::PARAM_INT);
        $sth->bindValue(':requester', $requester->getAclRequesterIdentifier(), PDO::PARAM_STR);
        $sth->bindValue(':resource', $resource->getAclResourceIdentifier(), PDO::PARAM_STR);

        $sth->execute();
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
        $sth = $this->getPdoStatement(
            'SELECT * FROM acl_permissions WHERE requester = :requester AND resource = :resource'
        );

        $sth->execute([
            ':requester' => $requester->getAclRequesterIdentifier(),
            ':resource' => $resource->getAclResourceIdentifier(),
        ]);

        $fixtures = $sth->fetchAll(PDO::FETCH_ASSOC);

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


    /**
     * @param string $statement
     *
     * @return PDOStatement
     *
     * @throws \Exception
     */
    private function getPdoStatement($statement)
    {
        try {

            if (false === $sth = $this->pdo->prepare($statement)) {
                throw new \Exception(sprintf('Can not prepare this pdo statement: "%s"', $statement));
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $sth;
    }
}
