<?php

namespace AlexDpy\Acl\Filter;

use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;
use Doctrine\DBAL\Query\QueryBuilder;

class DoctrineDbalAclFilter implements AclFilterInterface
{
    public function __construct(QueryBuilder $queryBuilder)
    {

    }

    public function apply(RequesterInterface $requester, ResourceInterface $resource, $action)
    {

    }
}
