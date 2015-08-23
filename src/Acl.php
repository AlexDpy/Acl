<?php

namespace AlexDpy\Acl;

use AlexDpy\Acl\Cache\PermissionBufferInterface;
use AlexDpy\Acl\Exception\PermissionNotFoundException;
use AlexDpy\Acl\Mask\MaskBuilderInterface;
use AlexDpy\Acl\Model\CascadingRequesterInterface;
use AlexDpy\Acl\Model\Permission;
use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\ResourceInterface;
use Doctrine\DBAL\Connection;

class Acl implements AclInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $maskBuilderClass;

    /**
     * @var PermissionBufferInterface
     */
    protected $permissionBuffer;

    /**
     * @var string
     */
    protected $permissionsTable;

    /**
     * @param Connection                $connection
     * @param PermissionBufferInterface $permissionBuffer
     * @param string                    $maskBuilderClass
     * @param string                    $permissionsTable
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        Connection $connection,
        PermissionBufferInterface $permissionBuffer,
        $maskBuilderClass = 'AlexDpy\Acl\Mask\BasicMaskBuilder',
        $permissionsTable = 'acl_permissions'
    ) {
        if (!class_exists($maskBuilderClass)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist', $maskBuilderClass));
        }

        $this->maskBuilderClass = $maskBuilderClass;

        if (!$this->getMaskBuilder() instanceof MaskBuilderInterface) {
            throw new \InvalidArgumentException(sprintf('Class "%s" must implements MaskBuilderInterface', $maskBuilderClass));
        }

        $this->connection = $connection;
        $this->permissionBuffer = $permissionBuffer;
        $this->permissionsTable = $permissionsTable;
    }

    /**
     * {@inheritdoc}
     */
    public function grant(RequesterInterface $requester, ResourceInterface $resource, $actions)
    {
        try {
            $permission = $this->findPermission($requester, $resource);
        } catch (PermissionNotFoundException $e) {
            $permission = $this->initPermission($requester, $resource);
        }

        foreach ((array) $actions as $action) {
            $permission->grant($action);
        }

        $this->savePermission($permission);
    }

    /**
     * {@inheritdoc}
     */
    public function revoke(RequesterInterface $requester, ResourceInterface $resource, $actions)
    {
        try {
            $permission = $this->findPermission($requester, $resource);
        } catch (PermissionNotFoundException $e) {
            $permission = $this->initPermission($requester, $resource);
        }

        foreach ((array) $actions as $action) {
            $permission->revoke($action);
        }

        $this->savePermission($permission);
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(RequesterInterface $requester, ResourceInterface $resource, $action)
    {
        return $this->processIsGranted($requester, $resource, $action);
    }

    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     * @param string             $action
     * @param array              $buffer
     *
     * @return bool
     */
    protected function processIsGranted(RequesterInterface $requester, ResourceInterface $resource, $action, &$buffer = array())
    {
        try {
            $permission = $this->findPermission($requester, $resource);

            $isGranted = $permission->isGranted($action);
        } catch (PermissionNotFoundException $e) {
            $isGranted = false;
        }

        if (false === $isGranted && $requester instanceof CascadingRequesterInterface) {
            $buffer[] = $requester->getAclRequesterIdentifier();

            foreach ($requester->getAclParentsRequester() as $requesterParent) {
                if (in_array($requesterParent->getAclRequesterIdentifier(), $buffer)) {
                    return $isGranted;
                }

                $buffer[] = $requesterParent->getAclRequesterIdentifier();
                if (true === $this->processIsGranted($requesterParent, $resource, $action, $buffer)) {
                    return true;
                }
            }
        }

        return $isGranted;
    }

    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     *
     * @return PermissionInterface
     *
     * @throws Exception\PermissionNotFoundException
     */
    protected function findPermission(RequesterInterface $requester, ResourceInterface $resource)
    {
        if (null === $permission = $this->permissionBuffer->get($requester, $resource)) {
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
                throw new PermissionNotFoundException($requester, $resource);
            }

            $permission = $this->initPermission($requester, $resource, (int) $mask);
            $permission->setPersistent(true);

            $this->permissionBuffer->add($permission);
        }

        return $permission;
    }

    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     * @param int                $mask
     *
     * @return PermissionInterface
     */
    protected function initPermission(RequesterInterface $requester, ResourceInterface $resource, $mask = 0)
    {
        $maskBuilder = $this->getMaskBuilder();
        $maskBuilder->set($mask);

        return new Permission($requester, $resource, $maskBuilder);
    }

    /**
     * @param PermissionInterface $permission
     */
    protected function savePermission(PermissionInterface $permission)
    {
        if (0 === $permission->getMask()) {
            if ($permission->isPersistent()) {
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

                $permission->setPersistent(false);
            }

            $this->permissionBuffer->add($permission);

            return;
        }

        if ($permission->isPersistent()) {
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
        } else {
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
            $permission->setPersistent(true);
        }

        $this->permissionBuffer->add($permission);
    }

    /**
     * @return MaskBuilderInterface
     */
    protected function getMaskBuilder()
    {
        return new $this->maskBuilderClass();
    }
}
