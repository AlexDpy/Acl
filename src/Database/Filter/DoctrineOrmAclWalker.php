<?php

namespace AlexDpy\Acl\Database\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\SqlWalker;

class DoctrineOrmAclWalker extends SqlWalker
{
    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause)
    {
        $sql = parent::walkFromClause($fromClause);

        $prefix = $this->getQuery()->getHint('acl_resource_prefix');
        $fromAlias = $this->getQuery()->getHint('acl_from_alias');
        $fromIdentifier = $this->getQuery()->getHint('acl_from_identifier');

        $resourceCondition = $this->getConnection()->getDatabasePlatform()->getConcatExpression(
            $this->getConnection()->quote($prefix), $this->dqlToSqlReference($fromAlias, $fromIdentifier)
        );

        $sql .= ' LEFT JOIN acl_permissions acl_p ON acl_p.resource = ' . $resourceCondition;

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    public function walkWhereClause($whereClause)
    {
        $sql = parent::walkWhereClause($whereClause);

        $sql .= empty($sql) ? ' WHERE (' : ' AND (';

        $orX = $this->getQuery()->getHint('acl_or_x');
        foreach ($orX as $key => $or) {
            preg_match_all("/\w+\.{1}\w+/", $or, $orReferences);

            foreach ($orReferences as $orReference) {
                $explode = explode('.', $orReference[0]);
                $orX[$key] = str_replace($orReference[0], $this->dqlToSqlReference($explode[0], $explode[1]), $or);
            }
        }

        $mask = (int) $this->getQuery()->getHint('acl_mask');

        $identifiers = array_map(function ($value) {
            return $value;
        }, $this->getQuery()->getHint('acl_requester_identifiers'));

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $orX[] = $expr->andX(
            $expr->in('acl_p.requester', $identifiers),
            $expr->eq($mask, 'acl_p.mask & ' . $mask)
        );

        $sql .= '(' . new Orx($orX) . '))';

        return $sql;
    }

    /**
     * @param string $dqlAlias
     * @param string $dqlField
     *
     * @return string
     */
    private function dqlToSqlReference($dqlAlias, $dqlField)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->getQueryComponent($dqlAlias)['metadata'];
        $tableReference = $metadata->table['name'];
        $aliasReference = $this->getSQLTableAlias($tableReference, $dqlAlias);
        $columnName = $metadata->fieldMappings[$dqlField]['columnName'];

        return $aliasReference . '.' . $columnName;
    }
}
