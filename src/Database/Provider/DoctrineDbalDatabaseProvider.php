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
            [
                'requester' => $requester->getAclRequesterIdentifier(),
                'resource' => $resource->getAclResourceIdentifier(),
            ],
            0,
            [
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
            ]
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
            [
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
            ],
            [
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updatePermission(PermissionInterface $permission)
    {
        $this->connection->update(
            $this->permissionsTable,
            ['mask' => $permission->getMask()],
            [
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
            ],
            [
                'mask' => \PDO::PARAM_INT,
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function insertPermission(PermissionInterface $permission)
    {
        $this->connection->insert(
            $this->permissionsTable,
            [
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
                'mask' => $permission->getMask(),
            ],
            [
                'mask' => \PDO::PARAM_INT,
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
            ]
        );
    }
}
