<?php

namespace AlexDpy\Acl\Database\Schema;

class AclSchema
{
    const DEFAULT_PERMISSIONS_TABLE_NAME = 'acl_permissions';
    const DEFAULT_REQUESTER_COLUMN_LENGTH = 255;
    const DEFAULT_RESOURCE_COLUMN_LENGTH = 255;

    /**
     * @var string
     */
    private $permissionsTableName;

    /**
     * @var int
     */
    private $requesterColumnLength;

    /**
     * @var int
     */
    private $resourceColumnLength;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->permissionsTableName = isset($options['permissions_table_name'])
            ? $options['permissions_table_name'] : self::DEFAULT_PERMISSIONS_TABLE_NAME;

        $this->requesterColumnLength = isset($options['requester_column_length'])
            ? $options['requester_column_length'] : self::DEFAULT_REQUESTER_COLUMN_LENGTH;

        $this->resourceColumnLength = isset($options['resource_column_length'])
            ? $options['resource_column_length'] : self::DEFAULT_RESOURCE_COLUMN_LENGTH;
    }

    /**
     * @return string
     */
    public function getPermissionsTableName()
    {
        return $this->permissionsTableName;
    }

    /**
     * @return int
     */
    public function getRequesterColumnLength()
    {
        return $this->requesterColumnLength;
    }

    /**
     * @return int
     */
    public function getResourceColumnLength()
    {
        return $this->resourceColumnLength;
    }

    /**
     * @param string $driver
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getCreateQuery($driver)
    {
        switch ($driver) {
            case 'mysql' :
                return <<<SQL
CREATE TABLE {$this->getPermissionsTableName()} (
  requester VARCHAR({$this->getRequesterColumnLength()}) NOT NULL,
  resource VARCHAR({$this->getResourceColumnLength()}) NOT NULL,
  mask INT NOT NULL,
  UNIQUE INDEX UNIQ_ACL_PERM_REQ_RES (requester, resource),
  INDEX IDX_ACL_PERM_REQ (requester),
  INDEX IDX_ACL_PERM_RES (resource),
  PRIMARY KEY (requester, resource),
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
            case 'sqlite' :
                return <<<SQL
CREATE TABLE {$this->getPermissionsTableName()} (
  requester VARCHAR({$this->getRequesterColumnLength()}) NOT NULL,
  resource VARCHAR({$this->getResourceColumnLength()}) NOT NULL,
  mask INTEGER NOT NULL,
  PRIMARY KEY (requester, resource)
);
CREATE UNIQUE INDEX UNIQ_ACL_PERM_REQ_RES ON {$this->getPermissionsTableName()} (requester, resource);
CREATE INDEX IDX_ACL_PERM_REQ ON {$this->getPermissionsTableName()} (requester);
CREATE INDEX IDX_ACL_PERM_RES ON {$this->getPermissionsTableName()} (resource);
SQL;
            case 'pgsql' :
                return <<<SQL
CREATE TABLE {$this->getPermissionsTableName()} (
  requester VARCHAR({$this->getRequesterColumnLength()}) NOT NULL,
  resource VARCHAR({$this->getResourceColumnLength()}) NOT NULL,
  mask INT NOT NULL,
  PRIMARY KEY (requester, resource)
);
CREATE UNIQUE INDEX UNIQ_ACL_PERM_REQ_RES ON {$this->getPermissionsTableName()} (requester, resource);
CREATE INDEX IDX_ACL_PERM_REQ ON {$this->getPermissionsTableName()} (requester);
CREATE INDEX IDX_ACL_PERM_RES ON {$this->getPermissionsTableName()} (resource);
SQL;
            default :
                throw new \Exception(sprintf(
                    'The getCreateQuery() is not available for driver "%".',
                    $driver
                ));
        }
    }
}
