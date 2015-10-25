<?php

namespace AlexDpy\Acl\Database\Schema;

trait AclSchemaTrait
{
    /**
     * @var AclSchema
     */
    private $aclSchema;

    /**
     * @return AclSchema
     *
     * @throws \Exception
     */
    public function getAclSchema()
    {
        if (null === $this->aclSchema) {
            throw new \Exception('AclSchema does not exist yet. Please call "setAclSchema" before.');
        }

        return $this->aclSchema;
    }

    /**
     * @param AclSchema $aclSchema
     */
    public function setAclSchema(AclSchema $aclSchema)
    {
        $this->aclSchema = $aclSchema;
    }
}
