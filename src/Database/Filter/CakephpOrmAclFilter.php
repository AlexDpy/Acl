<?php

namespace AlexDpy\Acl\Database\Filter;


use Cake\ORM\Query;

class CakephpOrmAclFilter implements AclFilterInterface
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
        $this->query->join([
            'table' => 'acl_permissions',
            'alias' => 'acl_p',
            'type' => 'LEFT',
            'conditions' => $this->query->newExpr()->eq('acl_p.resource', $this->query->func()->concat([
                ':acl_resource_prefix' => 'literal',
                $fromAlias . '.' . $fromIdentifier => 'literal',
            ]))
        ]);


        $orX[] = $this->query->newExpr()->and_([
            $this->query->newExpr()->in('acl_p.requester', $requesterIdentifiers, 'string'),
            $this->query->newExpr(':acl_mask = (acl_p.mask & :acl_mask)')
        ]);

        $this->query->andWhere($this->query->newExpr()->or_($orX));

        $this->query->bind(':acl_resource_prefix', $resourcePrefix);
        $this->query->bind(':acl_mask', $mask, 'integer');

        return $this->query;
    }
}
