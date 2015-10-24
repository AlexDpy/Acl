<?php

namespace Tests\AlexDpy\Acl\Database;

use PDO;
use PDOStatement;
use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\Resource;
use AlexDpy\Acl\Model\ResourceInterface;

abstract class AbstractDatabaseTest extends \PHPUnit_Framework_TestCase
{
    const SQLITE_PATH = 'tests/sqlite_acl';

    /**
     * @var PDO
     */
    protected $pdo;

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
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     * @param int                $mask
     */
    protected function insertFixture(RequesterInterface $requester, ResourceInterface $resource, $mask)
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
    protected function findFixture(RequesterInterface $requester, ResourceInterface $resource)
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
            return;
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
    protected function getPdoStatement($statement)
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
