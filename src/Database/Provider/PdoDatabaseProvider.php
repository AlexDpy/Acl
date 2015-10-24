<?php

namespace AlexDpy\Acl\Database\Provider;

use PDO;
use PDOStatement;
use AlexDpy\Acl\Exception\MaskNotFoundException;
use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;

class PdoDatabaseProvider implements DatabaseProviderInterface
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $permissionsTable;

    /**
     * @param PDO    $pdo
     * @param string $permissionsTable
     */
    public function __construct(PDO $pdo, $permissionsTable = 'acl_permissions')
    {
        $this->pdo = $pdo;
        $this->permissionsTable = $permissionsTable;
    }

    /**
     * {@inheritdoc}
     */
    public function findMask(RequesterInterface $requester, ResourceInterface $resource)
    {
        $sth = $this->getPdoStatement(
            'SELECT mask FROM ' . $this->permissionsTable . ' WHERE requester = :requester AND resource = :resource'
        );

        $sth->execute([
            ':requester' => $requester->getAclRequesterIdentifier(),
            ':resource' => $resource->getAclResourceIdentifier(),
        ]);

        if (false === $mask = $sth->fetchColumn()) {
            throw new MaskNotFoundException();
        }

        return (int) $mask;
    }

    /**
     * {@inheritdoc}
     */
    public function deletePermission(PermissionInterface $permission)
    {
        $sth = $this->getPdoStatement(
            'DELETE FROM ' . $this->permissionsTable . ' WHERE requester = :requester AND resource = :resource'
        );

        $sth->bindValue(':requester', $permission->getRequester()->getAclRequesterIdentifier(), PDO::PARAM_STR);
        $sth->bindValue(':resource', $permission->getResource()->getAclResourceIdentifier(), PDO::PARAM_STR);

        $sth->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function updatePermission(PermissionInterface $permission)
    {
        $sth = $this->getPdoStatement(
            'UPDATE ' . $this->permissionsTable . ' SET mask = :mask WHERE requester = :requester AND resource = :resource'
        );

        $sth->bindValue(':mask', $permission->getMask(), PDO::PARAM_INT);
        $sth->bindValue(':requester', $permission->getRequester()->getAclRequesterIdentifier(), PDO::PARAM_STR);
        $sth->bindValue(':resource', $permission->getResource()->getAclResourceIdentifier(), PDO::PARAM_STR);

        $sth->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function insertPermission(PermissionInterface $permission)
    {
        $sth = $this->getPdoStatement(
            'INSERT INTO ' . $this->permissionsTable . ' (requester, resource, mask) VALUES (:requester, :resource, :mask)'
        );

        $sth->bindValue(':mask', $permission->getMask(), PDO::PARAM_INT);
        $sth->bindValue(':requester', $permission->getRequester()->getAclRequesterIdentifier(), PDO::PARAM_STR);
        $sth->bindValue(':resource', $permission->getResource()->getAclResourceIdentifier(), PDO::PARAM_STR);

        $sth->execute();
    }

    /**
     * @param string $statement
     *
     * @return PDOStatement
     *
     * @throws \Exception
     */
    private function getPdoStatement($statement)
    {
        try {
            if (false === $sth = $this->pdo->prepare($statement)) {
                throw new \Exception(sprintf('Can not prepare this pdo statement: "%s"', $statement));
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $sth;
    }
}
