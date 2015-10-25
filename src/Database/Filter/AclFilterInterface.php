<?php

namespace AlexDpy\Acl\Database\Filter;

use AlexDpy\Acl\Database\Schema\HasAclSchemaInterface;

interface AclFilterInterface extends HasAclSchemaInterface
{
    /**
     * @param string $fromAlias
     * @param string $fromIdentifier
     * @param string $resourcePrefix
     * @param array  $requesterIdentifiers
     * @param int    $mask
     * @param array  $orX
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function apply(
        $fromAlias,
        $fromIdentifier,
        $resourcePrefix,
        array $requesterIdentifiers,
        $mask,
        array $orX = []
    );
}
