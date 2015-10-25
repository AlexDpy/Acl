<?php

namespace AlexDpy\Acl\Database\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class DoctrineDbalAclFilter extends AbstractAclFilter
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->connection = $queryBuilder->getConnection();
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function apply($fromAlias, $fromIdentifier, $resourcePrefix, array $requesterIdentifiers, $mask, array $orX = [])
    {
        $this->queryBuilder
            ->leftJoin(
                $fromAlias,
                $this->getAclSchema()->getPermissionsTableName(),
                'acl_p',
                'acl_p.resource = ' . $this->connection->getDatabasePlatform()->getConcatExpression(
                    ':acl_prefix', $fromAlias . '.' . $fromIdentifier
                )
            );

        $orX[] = 'acl_p.requester IN (:acl_identifiers) AND :acl_mask = (acl_p.mask & :acl_mask)';
        $this->queryBuilder->andWhere(implode(' OR ', $orX));

        $this->queryBuilder
            ->setParameter('acl_prefix', $resourcePrefix, \PDO::PARAM_STR)
            ->setParameter('acl_identifiers', $requesterIdentifiers, Connection::PARAM_STR_ARRAY)
            ->setParameter('acl_mask', $mask, \PDO::PARAM_INT);

        return $this->queryBuilder;
    }
}
