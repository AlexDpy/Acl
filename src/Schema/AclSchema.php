<?php

namespace AlexDpy\Acl\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Types\Type;

class AclSchema extends Schema
{
    public function __construct(
        array $tables = array(),
        array $sequences = array(),
        SchemaConfig $schemaConfig = null,
        array $namespaces = array()
    ) {
        parent::__construct($tables, $sequences, $schemaConfig, $namespaces);

        $permissionsTable = $this->createTable('acl_permissions');
        $permissionsTable->addColumn('requester', Type::STRING, ['length' => 255]);
        $permissionsTable->addColumn('resource', Type::STRING, ['length' => 255]);
        $permissionsTable->addColumn('mask', Type::INTEGER);
        $permissionsTable
            ->setPrimaryKey(['requester', 'resource'])
            ->addUniqueIndex(['resource', 'requester'])
            ->addIndex(['resource', 'requester']);
    }

}
 