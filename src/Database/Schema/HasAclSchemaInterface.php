<?php

namespace AlexDpy\Acl\Database\Schema;

interface HasAclSchemaInterface
{
    /**
     * @return AclSchema
     */
    public function getAclSchema();

    /**
     * @param AclSchema $aclSchema
     */
    public function setAclSchema(AclSchema $aclSchema);
}
