<?php

namespace AlexDpy\Acl\Database\Provider;

use AlexDpy\Acl\Exception\MaskNotFoundException;
use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;
use Doctrine\DBAL\Connection;

class DoctrineDbalDatabaseProvider implements DatabaseProviderInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $permissionsTable;

    /**
     * @param Connection $connection
     * @param string     $permissionsTable
     */
    public function __construct(Connection $connection, $permissionsTable = 'acl_permissions')
    {
        $this->connection = $connection;
        $this->permissionsTable = $permissionsTable;
    }

    /**
     * {@inheritdoc}
     */
    public function findMask(RequesterInterface $requester, ResourceInterface $resource)
    {
        if (false === $mask = $this->connection->fetchColumn(
            'SELECT mask FROM ' . $this->permissionsTable . ' WHERE requester = :requester AND resource = :resource',
            array(
                'requester' => $requester->getAclRequesterIdentifier(),
                'resource' => $resource->getAclResourceIdentifier(),
            ),
            0,
            array(
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
            )
        )) {
            throw new MaskNotFoundException();
        }


        return (int) $mask;
    }

    /**
     * {@inheritdoc}
     */
    public function deletePermission(PermissionInterface $permission)
    {
        $this->connection->delete(
            $this->permissionsTable,
            array(
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
            ),
            array(
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updatePermission(PermissionInterface $permission)
    {
        $this->connection->update(
            $this->permissionsTable,
            array('mask' => $permission->getMask()),
            array(
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
            ),
            array(
                'mask' => \PDO::PARAM_INT,
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function insertPermission(PermissionInterface $permission)
    {
        $this->connection->insert(
            $this->permissionsTable,
            array(
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
                'mask' => $permission->getMask(),
            ),
            array(
                'mask' => \PDO::PARAM_INT,
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
            )
        );
    }
}
