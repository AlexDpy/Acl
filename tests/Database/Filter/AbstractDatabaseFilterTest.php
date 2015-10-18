<?php

namespace Tests\AlexDpy\Acl\Database\Filter;

use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\Resource;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Tests\AlexDpy\Acl\Database\AbstractDatabaseTest;

abstract class AbstractDatabaseFilterTest extends AbstractDatabaseTest
{
    protected function setUp()
    {
        parent::setUp();

        $create = <<<SQL
CREATE TABLE posts (
  id INT NOT NULL,
  resource VARCHAR(32) NOT NULL,
  PRIMARY KEY(id)
)
SQL;

        $this->pdo->prepare($create)->execute();

        for ($i = 1; $i <= 10; $i++) {
            $sth = $this->getPdoStatement('INSERT INTO posts (id, status) VALUES (:id, :status)');

            $sth->bindValue(':mask', $i, \PDO::PARAM_INT);
            $sth->bindValue(':status', 0 === $i % 2 ? 'even' : 'odd', \PDO::PARAM_STR);

            $sth->execute();
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

        foreach ($this->getFilteredPostsIds($identifiers, $filterMask, $orX) as $result) {
            $this->assertEquals($expected, $result);
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
                    'ROLE_EMPLOYEE' => [1 => 1,]
                ],
                1, []
            ],
            [
                ['alice', 'ROLE_EMPLOYEE',],
                [
                    'ROLE_EMPLOYEE' => [1 => 1,]
                ],
                1, [1]
            ],
            [
                ['alice', 'ROLE_EMPLOYEE',],
                [
                    'ROLE_EMPLOYEE' => [1 => 1, 2 => 1]
                ],
                1, [1, 2]
            ],
            [
                ['alice', 'ROLE_EMPLOYEE',],
                [
                    'ROLE_CLIENT' => [1 => 1, 2 => 1]
                ],
                1, []
            ],
            [
                ['alice', 'ROLE_EMPLOYEE',],
                [
                    'ROLE_EMPLOYEE' => [1 => 1, 2 => 1]
                ],
                1, [1, 2, 3, 5, 7, 9], ['p.status = \'odd\'']
            ],
        ];
    }
}
