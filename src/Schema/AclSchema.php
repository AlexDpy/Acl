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
    public function __construct(array $options = array(), Connection $connection = null)
    {
        parent::__construct(array(), array(), null === $connection ? null : $connection->getSchemaManager()->createSchemaConfig());

        $options = array_merge(array(
            'permissions_table_name' => 'acl_permissions',
            'requester_column_length' => 255,
            'resource_column_length' => 255,
        ), $options);

        $permissionsTable = $this->createTable($options['permissions_table_name']);

        $permissionsTable->addColumn('requester', Type::STRING, array('length' => $options['requester_column_length']));
        $permissionsTable->addColumn('resource', Type::STRING, array('length' => $options['resource_column_length']));
        $permissionsTable->addColumn('mask', Type::INTEGER);

        $permissionsTable
            ->setPrimaryKey(array('requester', 'resource'))
            ->addUniqueIndex(array('resource', 'requester'))
            ->addIndex(array('resource', 'requester'));
    }

    /**
     * Merges AclSchema with the given schema.
     *
     * @param Schema $schema
     */
    public function addToSchema(Schema $schema)
    {
        foreach ($this->getTables() as $table) {
            $schema->_addTable($table);
        }

        foreach ($this->getSequences() as $sequence) {
            $schema->_addSequence($sequence);
        }
    }
}
