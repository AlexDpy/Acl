<?php

namespace AlexDpy\Acl\Database\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query;

class DoctrineDbalAclFilter implements AclFilterInterface
{
    const MODE_DBAL_QUERY_BUILDER = 1;
    const MODE_ORM_QUERY = 2;

    /**
     * @var int
     */
    private $mode;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @param QueryBuilder|Query $queryBuilder
     */
    public function __construct($queryBuilder)
    {
        if ($queryBuilder instanceof QueryBuilder) {
            $this->mode = self::MODE_DBAL_QUERY_BUILDER;
            $this->connection = $queryBuilder->getConnection();
            $this->queryBuilder = $queryBuilder;
        } elseif ($queryBuilder instanceof Query) {
            $this->mode = self::MODE_ORM_QUERY;
            $this->connection = $queryBuilder->getEntityManager()->getConnection();
            $this->query = $queryBuilder;
        } else {
            throw new \InvalidArgumentException(sprintf('$queryBuilder must be an instance of %s'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function apply($fromAlias, $fromIdentifier, $resourcePrefix, array $requesterIdentifiers, $mask, array $orX = [])
    {
        if (self::MODE_DBAL_QUERY_BUILDER === $this->mode) {
            return $this->applyDbalQueryBuilderMode($fromAlias, $fromIdentifier, $resourcePrefix, $requesterIdentifiers, $mask, $orX);
        }

        if (self::MODE_ORM_QUERY === $this->mode) {
            return $this->applyOrmQueryMode($fromAlias, $fromIdentifier, $resourcePrefix, $requesterIdentifiers, $mask, $orX);
        }

        throw new \Exception('Can not apply any filter');
    }

    /**
     * @param string $fromAlias
     * @param string $fromIdentifier
     * @param string $resourcePrefix
     * @param array  $requesterIdentifiers
     * @param int    $mask
     * @param array  $orX
     *
     * @return QueryBuilder
     */
    private function applyDbalQueryBuilderMode($fromAlias, $fromIdentifier, $resourcePrefix, array $requesterIdentifiers, $mask, array $orX = [])
    {
        $this->queryBuilder
            ->leftJoin(
                $fromAlias,
                'acl_permissions',
                'acl_p',
                'acl_p.resource = ' . $this->connection->getDatabasePlatform()->getConcatExpression(
                    ':prefix', $fromAlias . '.' . $fromIdentifier
                )
            );

        $orX[] = 'acl_p.requester IN (:identifiers) AND :mask = (acl_p.mask & :mask)';
        $this->queryBuilder->andWhere(implode(' OR ', $orX));

        $this->queryBuilder->setParameters([
            'prefix' => $resourcePrefix,
            'identifiers' => $requesterIdentifiers,
            'mask' => $mask,
        ], [
            'prefix' => \PDO::PARAM_STR,
            'identifiers' => Connection::PARAM_STR_ARRAY,
            'mask' => \PDO::PARAM_INT,
        ]);

        return $this->queryBuilder;
    }

    /**
     * @param string $fromAlias
     * @param string $fromIdentifier
     * @param string $resourcePrefix
     * @param array  $requesterIdentifiers
     * @param int    $mask
     * @param array  $orX
     *
     * @return QueryBuilder
     */
    private function applyOrmQueryMode($fromAlias, $fromIdentifier, $resourcePrefix, array $requesterIdentifiers, $mask, array $orX = [])
    {
        $this->query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'AlexDpy\Acl\Database\Filter\DoctrineOrmAclWalker');
        $this->query->setHint('acl_from_alias', $fromAlias);
        $this->query->setHint('acl_from_identifier', $fromIdentifier);
        $this->query->setHint('acl_resource_prefix', $resourcePrefix);
        $this->query->setHint('acl_requester_identifiers', $requesterIdentifiers);
        $this->query->setHint('acl_mask', $mask);
        $this->query->setHint('acl_or_x', $orX);

        return $this->query;
    }
}
