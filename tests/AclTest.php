<?php

namespace Tests\AlexDpy\Acl;

use AlexDpy\Acl\Mask\BasicMaskBuilder;
use AlexDpy\Acl\Model\Permission;
use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\Resource;
use AlexDpy\Acl\Model\ResourceInterface;
use Prophecy\Argument;

class AclTest extends AbstractAclTest
{
    /**
     * @var RequesterInterface
     */
    protected $aliceRequester;
    /**
     * @var RequesterInterface
     */
    protected $bobRequester;
    /**
     * @var RequesterInterface
     */
    protected $malloryRequester;

    /**
     * @var ResourceInterface
     */
    protected $fooResource;
    /**
     * @var ResourceInterface
     */
    protected $barResource;

    protected function setUp()
    {
        parent::setUp();

        $this->aliceRequester = new Requester('alice');
        $this->bobRequester = new Requester('bob');
        $this->malloryRequester = new Requester('mallory');

        $this->fooResource = new Resource('foo');
        $this->barResource = new Resource('bar');
    }

    public function testGrantNotCachedNonexistentPermission()
    {
        $this->permissionBuffer->get(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->willReturn(null)
            ->shouldBeCalledTimes(1);

        $this->databaseProvider->findMask(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->shouldBeCalledTimes(1)
            ->willThrow('AlexDpy\Acl\Exception\MaskNotFoundException');

        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1));

        $this->databaseProvider->insertPermission(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);
                $result = $permission == $expectedPermission;
                $expectedPermission->setPersistent(true);

                return $result;
            })
        )
            ->shouldBeCalledTimes(1);

        $this->permissionBuffer->add(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);

                return $permission == $expectedPermission;
            })
        )
            ->shouldBeCalledTimes(1);

        $this->acl->grant($this->aliceRequester, $this->fooResource, 'view');
    }

    public function testGrantNotCachedExistentPermission()
    {
        $this->permissionBuffer->get(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->willReturn(null)
            ->shouldBeCalledTimes(1);

        $existentPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1));
        $existentPermission->setPersistent(true);

        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(3));
        $expectedPermission->setPersistent(true);

        $this->databaseProvider->findMask(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->shouldBeCalledTimes(1)
            ->willReturn(1);

        $this->permissionBuffer->add(Argument::type('AlexDpy\Acl\Model\Permission'))
            ->shouldBeCalledTimes(2);

        $this->databaseProvider->updatePermission(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);

                return $permission == $expectedPermission;
            })
        )
            ->shouldBeCalledTimes(1);

        $this->acl->grant($this->aliceRequester, $this->fooResource, 'edit');
    }

    public function testGrantCachedNonexistentPermission()
    {
        $cachedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1));
        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(3));

        $this->permissionBuffer->get(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->willReturn($cachedPermission)
            ->shouldBeCalledTimes(1);

        $this->databaseProvider->insertPermission(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);
                $result = $permission == $expectedPermission;
                $expectedPermission->setPersistent(true);

                return $result;
            })
        )
            ->shouldBeCalledTimes(1);

        $this->permissionBuffer->add(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);

                return $permission == $expectedPermission;
            })
        )
            ->shouldBeCalledTimes(1);

        $this->acl->grant($this->aliceRequester, $this->fooResource, 'edit');
    }

    public function testGrantCachedExistentPermission()
    {
        $cachedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1));
        $cachedPermission->setPersistent(true);

        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(3));
        $expectedPermission->setPersistent(true);

        $this->permissionBuffer->get(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->willReturn($cachedPermission)
            ->shouldBeCalledTimes(1);

        $this->databaseProvider->updatePermission(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);

                return $permission == $expectedPermission;
            })
        )
            ->shouldBeCalledTimes(1);

        $this->permissionBuffer->add(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);

                return $permission == $expectedPermission;
            })
        )
            ->shouldBeCalledTimes(1);

        $this->acl->grant($this->aliceRequester, $this->fooResource, 'edit');
    }

    public function testGrantWithArrayParameter()
    {
        $cachedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1));
        $cachedPermission->setPersistent(true);
        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(7));
        $expectedPermission->setPersistent(true);

        $this->permissionBuffer->get(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->willReturn($cachedPermission)
            ->shouldBeCalledTimes(1);

        $this->permissionBuffer->add(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);

                return $permission == $expectedPermission;
            })
        )
            ->shouldBeCalledTimes(1);

        $this->acl->grant($this->aliceRequester, $this->fooResource, ['edit', 'create']);
    }
/*
    public function testRevoke()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 1 + 2 + 4);

        $this->permissionBuffer->get(
            Argument::exact($this->aliceRequester),
            Argument::exact($this->fooResource)
        )->shouldBeCalled();
        $this->permissionBuffer->add(
            Argument::type('AlexDpy\Acl\Model\Permission')
        )->shouldBeCalled();

        $this->acl->revoke($this->aliceRequester, $this->fooResource, 'create');
        $this->assertEquals(3, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testRevokeWithArrayParameter()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 1 + 2 + 4);

        $this->acl->revoke($this->aliceRequester, $this->fooResource, array('create'));
        $this->assertEquals(3, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testRevokeManyActionsAtDifferentSteps()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 1 + 2 + 4);

        $this->acl->revoke($this->aliceRequester, $this->fooResource, 'view');
        $this->assertEquals(6, $this->findMask($this->aliceRequester, $this->fooResource));

        $this->acl->revoke($this->aliceRequester, $this->fooResource, 'edit');
        $this->assertEquals(4, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testRevokeManyActionsAtTheSameTime()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 1 + 2 + 4);

        $this->acl->revoke($this->aliceRequester, $this->fooResource, array('view', 'edit'));
        $this->assertEquals(4, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testRevokeAnActionNotGrantedYet()
    {
        $this->insertPermission($this->bobRequester, $this->fooResource, 2);

        $this->acl->revoke($this->bobRequester, $this->fooResource, 'view');
        $this->assertEquals(2, $this->findMask($this->bobRequester, $this->fooResource));
    }

    public function testRevokeAnActionForANonExistentRequester()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 2);

        try {
            $this->acl->revoke($this->malloryRequester, $this->fooResource, 'edit');
        } catch (\Exception $e) {
            $this->fail();
        }

        $this->assertEquals(2, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testRevokeAnActionForANonExistentResource()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 2);

        try {
            $this->acl->revoke($this->aliceRequester, $this->barResource, 'edit');
        } catch (\Exception $e) {
            $this->fail();
        }

        $this->assertEquals(2, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testIsGranted()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 1);
        $this->assertTrue($this->acl->isGranted($this->aliceRequester, $this->fooResource, 'view'));
        $this->assertFalse($this->acl->isGranted($this->aliceRequester, $this->fooResource, 'edit'));
        $this->assertFalse($this->acl->isGranted($this->aliceRequester, $this->fooResource, 'create'));
        $this->assertFalse($this->acl->isGranted($this->aliceRequester, $this->fooResource, 'delete'));
        $this->assertFalse($this->acl->isGranted($this->aliceRequester, $this->barResource, 'view'));
        $this->assertFalse($this->acl->isGranted($this->malloryRequester, $this->fooResource, 'view'));

        $this->insertPermission($this->bobRequester, $this->fooResource, 2 + 8);
        $this->assertFalse($this->acl->isGranted($this->bobRequester, $this->fooResource, 'view'));
        $this->assertTrue($this->acl->isGranted($this->bobRequester, $this->fooResource, 'edit'));
        $this->assertFalse($this->acl->isGranted($this->bobRequester, $this->fooResource, 'create'));
        $this->assertTrue($this->acl->isGranted($this->bobRequester, $this->fooResource, 'delete'));
        $this->assertFalse($this->acl->isGranted($this->bobRequester, $this->barResource, 'edit'));
        $this->assertFalse($this->acl->isGranted($this->bobRequester, $this->barResource, 'delete'));
        $this->assertFalse($this->acl->isGranted($this->malloryRequester, $this->fooResource, 'edit'));
        $this->assertFalse($this->acl->isGranted($this->malloryRequester, $this->fooResource, 'delete'));
    }

    public function testIsGrantedExceptedPermissionBufferCallsWhenPermissionFound()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 1);

        $this->permissionBuffer->get(
            Argument::exact($this->aliceRequester),
            Argument::exact($this->fooResource)
        )->shouldBeCalled();
        $this->permissionBuffer->add(
            Argument::type('AlexDpy\Acl\Model\Permission')
        )->shouldBeCalled();

        $this->acl->isGranted($this->aliceRequester, $this->fooResource, 'view');
    }

    public function testIsGrantedExpectedPermissionBufferCallsWhenPermissionNotFound()
    {
        $this->permissionBuffer->get(
            Argument::exact($this->aliceRequester),
            Argument::exact($this->fooResource)
        )->shouldBeCalled();
        $this->permissionBuffer->add(
            Argument::type('AlexDpy\Acl\Model\Permission')
        )->shouldNotBeCalled();

        $this->acl->isGranted($this->aliceRequester, $this->fooResource, 'view');
    }

    public function testIsGrantedWithANonExistentRequester()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 1);
        try {
            $this->assertFalse($this->acl->isGranted($this->malloryRequester, $this->fooResource, 'view'));
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function testIsGrantedWithANonExistentResource()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 1);
        try {
            $this->assertFalse($this->acl->isGranted($this->aliceRequester, $this->barResource, 'view'));
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function testIsGrantedWithCascadingRequester()
    {
        $alice = new User('alice', array('ROLE_USER', 'ROLE_EDITOR'));

        $this->insertPermission(new Requester('ROLE_ADMIN'), $this->fooResource, 1 + 2 + 4 + 8);
        $this->insertPermission(new Requester('ROLE_EDITOR'), $this->fooResource, 1 + 2 + 4);
        $this->insertPermission(new Requester('ROLE_USER'), $this->fooResource, 1);

        $this->assertTrue($this->acl->isGranted($alice, $this->fooResource, 'view'));
        $this->assertTrue($this->acl->isGranted($alice, $this->fooResource, 'edit'));
        $this->assertTrue($this->acl->isGranted($alice, $this->fooResource, 'create'));
        $this->assertFalse($this->acl->isGranted($alice, $this->fooResource, 'delete'));
    }

    public function testCascadingRequesterCircularReferenceImplementation()
    {
        $bob = new UserCircularCascading('bob', array('mallory'));
        $mallory = new UserCircularCascading('mallory', array('bob'));
        $oscar = new UserCircularCascading('oscar', array('bob', 'mallory'));

        $this->assertFalse($this->acl->isGranted($bob, $this->fooResource, 'view'));
        $this->assertFalse($this->acl->isGranted($oscar, $this->fooResource, 'edit'));

        $this->insertPermission($bob, $this->fooResource, 1);
        $this->insertPermission($mallory, $this->fooResource, 2);

        $this->assertTrue($this->acl->isGranted($bob, $this->fooResource, 'view'));
        $this->assertTrue($this->acl->isGranted($bob, $this->fooResource, 'edit'));
        $this->assertTrue($this->acl->isGranted($mallory, $this->fooResource, 'edit'));
        $this->assertTrue($this->acl->isGranted($mallory, $this->fooResource, 'edit'));
        $this->assertTrue($this->acl->isGranted($oscar, $this->fooResource, 'view'));
        $this->assertTrue($this->acl->isGranted($oscar, $this->fooResource, 'edit'));
    }

    public function testIsGrantedWithManyCascadingRequesterLevels()
    {
        $oscar = new UserCascading('oscar');
        $mallory = new UserCascading('mallory', array($oscar));
        $bob = new UserCascading('bob', array($mallory));
        $alice = new UserCascading('alice', array($bob));

        $this->insertPermission($oscar, $this->fooResource, 1);
        $this->insertPermission($mallory, $this->fooResource, 2);
        $this->insertPermission($bob, $this->fooResource, 4);
        $this->insertPermission($alice, $this->fooResource, 8);

        $this->assertTrue($this->acl->isGranted($alice, $this->fooResource, 'view'));
        $this->assertTrue($this->acl->isGranted($alice, $this->fooResource, 'edit'));
        $this->assertTrue($this->acl->isGranted($alice, $this->fooResource, 'create'));
        $this->assertTrue($this->acl->isGranted($alice, $this->fooResource, 'delete'));

        $this->assertTrue($this->acl->isGranted($bob, $this->fooResource, 'view'));
        $this->assertTrue($this->acl->isGranted($bob, $this->fooResource, 'edit'));
        $this->assertTrue($this->acl->isGranted($bob, $this->fooResource, 'create'));
        $this->assertFalse($this->acl->isGranted($bob, $this->fooResource, 'delete'));

        $this->assertTrue($this->acl->isGranted($mallory, $this->fooResource, 'view'));
        $this->assertTrue($this->acl->isGranted($mallory, $this->fooResource, 'edit'));
        $this->assertFalse($this->acl->isGranted($mallory, $this->fooResource, 'create'));
        $this->assertFalse($this->acl->isGranted($mallory, $this->fooResource, 'delete'));

        $this->assertTrue($this->acl->isGranted($oscar, $this->fooResource, 'view'));
        $this->assertFalse($this->acl->isGranted($oscar, $this->fooResource, 'edit'));
        $this->assertFalse($this->acl->isGranted($oscar, $this->fooResource, 'create'));
        $this->assertFalse($this->acl->isGranted($oscar, $this->fooResource, 'delete'));
    }
*/
    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     *
     * @return int
     */
    private function findMask(RequesterInterface $requester, ResourceInterface $resource)
    {
        return (int) $this->connection->fetchColumn(
            'SELECT mask FROM acl_permissions WHERE requester = :requester AND resource = :resource',
            [
                'requester' => $requester->getAclRequesterIdentifier(),
                'resource' => $resource->getAclResourceIdentifier(),
            ],
            0,
            [
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
            ]
        );
    }

    /**
     * @param RequesterInterface $requester
     * @param ResourceInterface  $resource
     * @param int                $mask
     */
    private function insertPermission(RequesterInterface $requester, ResourceInterface $resource, $mask)
    {
        $this->connection->insert(
            'acl_permissions',
            [
                'requester' => $requester->getAclRequesterIdentifier(),
                'resource' => $resource->getAclResourceIdentifier(),
                'mask' => $mask,
            ],
            [
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
                'mask' => \PDO::PARAM_INT,
            ]
        );
    }
}
