<?php

namespace AlexDpy\Acl\Database\Filter;

interface AclFilterInterface
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
