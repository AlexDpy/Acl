<?php

namespace AlexDpy\Acl\Database\Provider;

use AlexDpy\Acl\Exception\MaskNotFoundException;
use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;
use Illuminate\Database\Connection;

class IlluminateDatabaseProvider extends AbstractDatabaseProvider
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
        $oldFetchMode = $this->connection->getFetchMode();
        $this->connection->setFetchMode(\PDO::FETCH_COLUMN);

        if (null === $mask = $this->connection->selectOne(
            'SELECT mask FROM ' . $this->getAclSchema()->getPermissionsTableName() . ' WHERE requester = :requester AND resource = :resource',
            [
                'requester' => $requester->getAclRequesterIdentifier(),
                'resource' => $resource->getAclResourceIdentifier(),
            ]
        )) {
            $this->connection->setFetchMode($oldFetchMode);

            throw new MaskNotFoundException();
        }

        $this->connection->setFetchMode($oldFetchMode);

        return (int) $mask;
    }

    /**
     * {@inheritdoc}
     */
    public function deletePermission(PermissionInterface $permission)
    {
        $this->connection->delete(
            'DELETE FROM ' . $this->getAclSchema()->getPermissionsTableName() . ' WHERE requester = :requester AND resource = :resource',
            [
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updatePermission(PermissionInterface $permission)
    {
        $this->connection->update(
            'UPDATE ' . $this->getAclSchema()->getPermissionsTableName() . ' SET mask = :mask WHERE requester = :requester AND resource = :resource',
            [
                'mask' => $permission->getMask(),
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function insertPermission(PermissionInterface $permission)
    {
        $this->connection->insert(
            'INSERT INTO ' . $this->getAclSchema()->getPermissionsTableName() . ' (requester, resource, mask) VALUES (:requester, :resource, :mask)',
            [
                'requester' => $permission->getRequester()->getAclRequesterIdentifier(),
                'resource' => $permission->getResource()->getAclResourceIdentifier(),
                'mask' => $permission->getMask(),
            ]
        );
    }
}
