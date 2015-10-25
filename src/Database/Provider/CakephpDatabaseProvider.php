<?php

namespace AlexDpy\Acl\Database\Provider;

use AlexDpy\Acl\Exception\MaskNotFoundException;
use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;
use Cake\Database\Connection;

class CakephpDatabaseProvider extends AbstractDatabaseProvider
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function findMask(RequesterInterface $requester, ResourceInterface $resource)
    {
        if (false === $result = $this->connection->execute(
            'SELECT mask FROM ' . $this->getAclSchema()->getPermissionsTableName() . ' WHERE requester = :requester AND resource = :resource',
            [
                'requester' => $requester->getAclRequesterIdentifier(),
                'resource' => $resource->getAclResourceIdentifier(),
            ],
            [
                'requester' => 'string',
                'resource' => 'string',
            ]
        )->fetch('assoc')) {
            throw new MaskNotFoundException();
        }

        return (int) $result['mask'];
    }

    /**
     * {@inheritdoc}
     */
    public function deletePermission(PermissionInterface $permission)
    {
        $this->connection->delete(
            $this->getAclSchema()->getPermissionsTableName(),
            [
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
            ],
            [
                'requester' => 'string',
                'resource' => 'string',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updatePermission(PermissionInterface $permission)
    {
        $this->connection->update(
            $this->getAclSchema()->getPermissionsTableName(),
            ['mask' => $permission->getMask()],
            [
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
            ],
            [
                'mask' => 'integer',
                'requester' => 'string',
                'resource' => 'string',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function insertPermission(PermissionInterface $permission)
    {
        $this->connection->insert(
            $this->getAclSchema()->getPermissionsTableName(),
            [
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
                'mask' => $permission->getMask(),
            ],
            [
                'mask' => 'integer',
                'requester' => 'string',
                'resource' => 'string',
            ]
        );
    }
}
