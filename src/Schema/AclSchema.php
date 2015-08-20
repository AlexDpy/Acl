<?php

namespace AlexDpy\Acl\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class AclSchema extends Schema
{
    /**
     * @param array           $options
     * @param Connection|null $connection
     */
    public function __construct(array $options = [], Connection $connection = null) {
        parent::__construct([], [], null === $connection ? null : $connection->getSchemaManager()->createSchemaConfig());

        $options = array_merge([
            'permissions_table_name' => 'acl_permissions',
            'requester_column_length' => 255,
            'resource_column_length' => 255,
        ], $options);

        $permissionsTable = $this->createTable($options['permissions_table_name']);

        $permissionsTable->addColumn('requester', Type::STRING, ['length' => $options['requester_column_length']]);
        $permissionsTable->addColumn('resource', Type::STRING, ['length' => $options['resource_column_length']]);
        $permissionsTable->addColumn('mask', Type::INTEGER);

        $permissionsTable
            ->setPrimaryKey(['requester', 'resource'])
            ->addUniqueIndex(['resource', 'requester'])
            ->addIndex(['resource', 'requester']);
    }
}
