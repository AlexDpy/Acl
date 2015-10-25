<?php

namespace AlexDpy\Acl\Database\Filter;

use Doctrine\ORM\Query;

class DoctrineOrmAclFilter extends AbstractAclFilter
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function apply($fromAlias, $fromIdentifier, $resourcePrefix, array $requesterIdentifiers, $mask, array $orX = [])
    {
        $this->query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'AlexDpy\Acl\Database\Filter\DoctrineOrmAclWalker');

        $this->query->setHint('acl_permissions_table_name', $this->getAclSchema()->getPermissionsTableName());
        $this->query->setHint('acl_resource_prefix', $resourcePrefix);
        $this->query->setHint('acl_requester_identifiers', $requesterIdentifiers);
        $this->query->setHint('acl_mask', $mask);
        $this->query->setHint('acl_from_alias', $fromAlias);
        $this->query->setHint('acl_from_identifier', $fromIdentifier);
        $this->query->setHint('acl_or_x', $orX);

        return $this->query;
    }
}
