<?php

namespace Tests\AlexDpy\Acl\Database\Filter;

use AlexDpy\Acl\Database\Filter\DoctrineOrmAclFilter;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Setup;
use Tests\AlexDpy\Acl\Post;

class DoctrineOrmAclFilterTest extends AbstractDatabaseFilterTest
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
        $qb = new QueryBuilder($this->entityManager);
        $query = $qb->select('p')->from('Tests\AlexDpy\Acl\Post', 'p')->getQuery();

        $aclFilter = new DoctrineOrmAclFilter($query);
        $aclFilter->setAclSchema($this->aclSchema);
        $aclFilter->apply('p', 'id', 'post-', $identifiers, $mask, $orX);

        $result = array_map(function (Post $post) {
            return (int) $post->getId();
        }, $query->getResult());

        return [$result];
    }
}
