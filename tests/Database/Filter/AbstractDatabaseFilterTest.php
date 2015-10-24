<?php

namespace Tests\AlexDpy\Acl\Database\Filter;

use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\Resource;
use Doctrine\DBAL\Schema\Schema;
use Tests\AlexDpy\Acl\Database\AbstractDatabaseTest;

abstract class AbstractDatabaseFilterTest extends AbstractDatabaseTest
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->pdo->prepare('DROP TABLE posts')->execute();
        } catch (\PDOException $e) {
        }

        $create = <<<SQL
CREATE TABLE posts (
  id INT NOT NULL,
  status VARCHAR(32) NOT NULL,
  PRIMARY KEY(id)
)
SQL;

        $this->pdo->prepare($create)->execute();

        for ($i = 1; $i <= 10; $i++) {
            $status = 0 === $i % 2 ? 'even' : 'odd';
            $sth = $this->getPdoStatement('INSERT INTO posts (id, status) VALUES (' . $i . ', "' . $status . '")');

            $sth->execute();
        }
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        parent::tearDown();

        try {
            $this->pdo->prepare('DROP TABLE posts')->execute();
        } catch (\PDOException $e) {
        }
    }

    /**
     * @dataProvider dataFilter
     */
    public function testFilter(array $identifiers, $grants, $filterMask, $expected, $orX = [])
    {
        foreach ($grants as $grantedIdentifier => $acl) {
            $grantedRequester = new Requester($grantedIdentifier);
            foreach ($acl as $postId => $mask) {
                $this->insertFixture($grantedRequester, new Resource('post-' . (string) $postId), $mask);
            }
        }

        try {
            $results = $this->getFilteredPostsIds($identifiers, $filterMask, $orX);

            foreach ($results as $result) {
                $this->assertEquals($expected, $result);
            }
        } catch (\PDOException $e) {
            if (!empty($e->errorInfo[1]) && $e->errorInfo[1] === 17) {
                //              SQLSTATE[HY000]: General error: 17 database schema has changed
                return $this->getFilteredPostsIds($identifiers, $filterMask, $orX);
            }

            throw $e;
        }
    }

    /**
     * @param string[] $identifiers
     * @param int      $mask
     * @param array    $orX
     *
     * @return array
     */
    abstract protected function getFilteredPostsIds(array $identifiers, $mask, array $orX = []);

    public function dataFilter()
    {
        return [
            [
                ['alice'],
                [
                    'ROLE_EMPLOYEE' => [1 => 1],
                ],
                1, [],
            ],
            [
                ['alice'],
                [
                    'alice' => [1 => 2],
                ],
                1, [],
            ],
            [
                ['alice'],
                [
                    'alice' => [1 => 3],
                ],
                1, [1],
            ],
            [
                ['alice', 'ROLE_EMPLOYEE'],
                [
                    'ROLE_EMPLOYEE' => [1 => 1],
                ],
                1, [1],
            ],
            [
                ['alice', 'ROLE_EMPLOYEE'],
                [
                    'ROLE_EMPLOYEE' => [1 => 1, 2 => 1],
                ],
                1, [1, 2],
            ],
            [
                ['alice', 'ROLE_EMPLOYEE'],
                [
                    'ROLE_CLIENT' => [1 => 1, 2 => 1],
                ],
                1, [],
            ],
            [
                ['alice', 'ROLE_EMPLOYEE'],
                [
                    'ROLE_EMPLOYEE' => [1 => 1, 2 => 1],
                ],
                1, [1, 2, 3, 5, 7, 9], ['p.status = \'odd\''],
            ],
        ];
    }
}
