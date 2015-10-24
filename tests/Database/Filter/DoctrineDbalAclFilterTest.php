<?php

namespace Tests\AlexDpy\Acl\Database\Filter;

use AlexDpy\Acl\Database\Filter\DoctrineDbalAclFilter;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class DoctrineDbalAclFilterTest extends AbstractDatabaseFilterTest
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        parent::setUp();

        $this->connection = DriverManager::getConnection(
            ['driver' => 'pdo_sqlite', 'path' => self::SQLITE_PATH],
            new Configuration()
        );

        $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . '/../..'], true);
        $this->entityManager = EntityManager::create($this->connection, $config);
    }

    protected function getFilteredPostsIds(array $identifiers, $mask, array $orX = [])
    {
        $qb = new QueryBuilder($this->connection);
        $qb->select('p.id')->from('posts', 'p');

        $aclFilter = new DoctrineDbalAclFilter($qb);
        $aclFilter->apply('p', 'id', 'post-', $identifiers, $mask, $orX);

        $result = array_map(function ($post) {
            return (int) $post['id'];
        }, $qb->execute()->fetchAll());

        return [$result];
    }

    public function testQueryBuilderShouldHaveNewParameters()
    {
        $qb = new QueryBuilder($this->connection);
        $qb->select('p.id')->from('posts', 'p');

        $aclFilter = new DoctrineDbalAclFilter($qb);
        $aclFilter->apply('p', 'id', 'post-', ['user-1'], 1, []);

        $this->assertEquals([
            'acl_prefix' => 'post-',
            'acl_identifiers' => ['user-1'],
            'acl_mask' => 1,
        ], $qb->getParameters());
    }

    public function testFilterShouldNotOverrideQueryBuilderParameters()
    {
        $qb = new QueryBuilder($this->connection);
        $qb->select('p.id')->from('posts', 'p')->where('p.status = :status')->setParameter('status', 'odd');

        $aclFilter = new DoctrineDbalAclFilter($qb);
        $aclFilter->apply('p', 'id', 'post-', ['user-1'], 1);

        $this->assertEquals([
            'status' => 'odd',
            'acl_prefix' => 'post-',
            'acl_identifiers' => ['user-1'],
            'acl_mask' => 1,
        ], $qb->getParameters());
    }

    public function testItShouldWorksWithParametersInTheOrX()
    {
        $qb = new QueryBuilder($this->connection);
        $qb->select('p.id')->from('posts', 'p')->setParameter('status', 'odd');

        $aclFilter = new DoctrineDbalAclFilter($qb);
        $aclFilter->apply('p', 'id', 'post-', ['user-1'], 1, ['status = :status']);

        $this->assertEquals([
            'status' => 'odd',
            'acl_prefix' => 'post-',
            'acl_identifiers' => ['user-1'],
            'acl_mask' => 1,
        ], $qb->getParameters());
    }
}
