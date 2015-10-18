<?php

namespace Tests\AlexDpy\Acl;

use AlexDpy\Acl\Acl;
use AlexDpy\Acl\Exception\InvalidMaskBuilderException;
use AlexDpy\Acl\Mask\BasicMaskBuilder;
use AlexDpy\Acl\Model\Permission;
use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\Resource;
use AlexDpy\Acl\Model\ResourceInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class AclTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @var ObjectProphecy
     */
    protected $databaseProvider;

    /**
     * @var ObjectProphecy
     */
    protected $permissionBuffer;

    /**
     * @var Acl
     */
    protected $acl;

    protected function setUp()
    {
        $this->databaseProvider = $this->prophesize('AlexDpy\Acl\Database\Provider\DatabaseProviderInterface');
        $this->permissionBuffer = $this->prophesize('AlexDpy\Acl\Cache\PermissionBufferInterface');
        $this->acl = new Acl(
            $this->databaseProvider->reveal(),
            $this->permissionBuffer->reveal()
        );

        $this->aliceRequester = new Requester('alice');
        $this->bobRequester = new Requester('bob');
        $this->malloryRequester = new Requester('mallory');

        $this->fooResource = new Resource('foo');
        $this->barResource = new Resource('bar');
    }

    public function testConstructInvalidMaskBuilderShouldThrowInvalidMaskBuilderException()
    {
        try {
            new Acl(
                $this->databaseProvider->reveal(),
                $this->permissionBuffer->reveal(),
                'ThisClassDoesNotExist'
            );
            $this->fail('Acl::__construct should throw an InvalidMaskBuilderException when MaskBuilder class does not exist');
        } catch (InvalidMaskBuilderException $e) {
        }

        try {
            new Acl(
                $this->databaseProvider->reveal(),
                $this->permissionBuffer->reveal(),
                'InvalidMaskBuilderException'
            );
            $this->fail('Acl::__construct should throw an InvalidMaskBuilderException when MaskBuilder class does not implement MaskBuilderInterface');
        } catch (InvalidMaskBuilderException $e) {
        }
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

        $this->databaseProvider->updatePermission()->shouldNotBeCalled();
        $this->databaseProvider->deletePermission()->shouldNotBeCalled();

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

        $this->databaseProvider->insertPermission()->shouldNotBeCalled();
        $this->databaseProvider->deletePermission()->shouldNotBeCalled();

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

        $this->databaseProvider->insertPermission()->shouldNotBeCalled();
        $this->databaseProvider->deletePermission()->shouldNotBeCalled();

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

        $this->databaseProvider->insertPermission()->shouldNotBeCalled();
        $this->databaseProvider->deletePermission()->shouldNotBeCalled();

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

    public function testRevokeNotCachedNonexistentPermission()
    {
        $this->permissionBuffer->get(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->willReturn(null)
            ->shouldBeCalledTimes(1);

        $this->databaseProvider->findMask(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->shouldBeCalledTimes(1)
            ->willThrow('AlexDpy\Acl\Exception\MaskNotFoundException');

        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(0));

        $this->permissionBuffer->add(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);

                return $permission == $expectedPermission;
            })
        )
            ->shouldBeCalledTimes(1);

        $this->databaseProvider->insertPermission()->shouldNotBeCalled();
        $this->databaseProvider->updatePermission()->shouldNotBeCalled();
        $this->databaseProvider->deletePermission()->shouldNotBeCalled();

        $this->acl->revoke($this->aliceRequester, $this->fooResource, 'view');
    }

    public function testRevokeNotCachedExistentPermission()
    {
        $this->permissionBuffer->get(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->willReturn(null)
            ->shouldBeCalledTimes(1);

        $existentPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1));
        $existentPermission->setPersistent(true);

        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(0));
        $expectedPermission->setPersistent(true);

        $this->databaseProvider->findMask(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->shouldBeCalledTimes(1)
            ->willReturn(1);

        $this->permissionBuffer->add(Argument::type('AlexDpy\Acl\Model\Permission'))
            ->shouldBeCalledTimes(2);

        $this->databaseProvider->deletePermission(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);
                $result = $permission == $expectedPermission;
                $expectedPermission->setPersistent(false);

                return $result;
            })
        )
            ->shouldBeCalledTimes(1);

        $this->databaseProvider->insertPermission()->shouldNotBeCalled();
        $this->databaseProvider->updatePermission()->shouldNotBeCalled();

        $this->acl->revoke($this->aliceRequester, $this->fooResource, 'view');
    }

    public function testRevokeCachedNonexistentPermission()
    {
        $cachedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1));
        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(0));
        $expectedPermission->setPersistent(false);

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

        $this->databaseProvider->insertPermission()->shouldNotBeCalled();
        $this->databaseProvider->updatePermission()->shouldNotBeCalled();
        $this->databaseProvider->deletePermission()->shouldNotBeCalled();

        $this->acl->revoke($this->aliceRequester, $this->fooResource, 'view');
    }

    public function testRevokeCachedExistentPermission()
    {
        $cachedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1));
        $cachedPermission->setPersistent(true);

        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(0));
        $expectedPermission->setPersistent(true);

        $this->permissionBuffer->get(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->willReturn($cachedPermission)
            ->shouldBeCalledTimes(1);

        $this->databaseProvider->deletePermission(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);
                $result = $permission == $expectedPermission;
                $expectedPermission->setPersistent(false);

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

        $this->databaseProvider->insertPermission()->shouldNotBeCalled();
        $this->databaseProvider->updatePermission()->shouldNotBeCalled();

        $this->acl->revoke($this->aliceRequester, $this->fooResource, 'view');
    }

    public function testRevokeButKeepSomeAccess()
    {
        $cachedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(3));
        $cachedPermission->setPersistent(true);

        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1));
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

        $this->databaseProvider->insertPermission()->shouldNotBeCalled();
        $this->databaseProvider->deletePermission()->shouldNotBeCalled();

        $this->acl->revoke($this->aliceRequester, $this->fooResource, 'edit');
    }

    public function testIsGrantedNotCachedNonexistentPermission()
    {
        $this->permissionBuffer->get(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->willReturn(null)
            ->shouldBeCalledTimes(1);

        $this->databaseProvider->findMask(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->shouldBeCalledTimes(1)
            ->willThrow('AlexDpy\Acl\Exception\MaskNotFoundException');

        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(0));
        $expectedPermission->setPersistent(false);

        $this->permissionBuffer->add(
            Argument::that(function (PermissionInterface $permission) use ($expectedPermission) {
                $this->assertEquals($expectedPermission, $permission);

                return $permission == $expectedPermission;
            })
        )
            ->shouldBeCalledTimes(1);

        $this->databaseProvider->insertPermission()->shouldNotBeCalled();
        $this->databaseProvider->updatePermission()->shouldNotBeCalled();
        $this->databaseProvider->deletePermission()->shouldNotBeCalled();

        $this->assertFalse($this->acl->isGranted($this->aliceRequester, $this->fooResource, 'view'));
    }

    public function testIsGrantedCachedExistentPermission()
    {
        $expectedPermission = new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1));
        $expectedPermission->setPersistent(true);

        $this->permissionBuffer->get(Argument::exact($this->aliceRequester), Argument::exact($this->fooResource))
            ->willReturn($expectedPermission)
            ->shouldBeCalledTimes(1);

        $this->databaseProvider->insertPermission()->shouldNotBeCalled();
        $this->databaseProvider->updatePermission()->shouldNotBeCalled();
        $this->databaseProvider->deletePermission()->shouldNotBeCalled();

        $this->assertTrue($this->acl->isGranted($this->aliceRequester, $this->fooResource, 'view'));
    }

    public function testFilter()
    {

    }

    public function testExtractRequesterIdentifiers()
    {
//        $roleUser = new Requester('ROLE_USER');
//
//        $roleEmployee = new RoleCascading('ROLE_EMPLOYEE', [$roleUser]);
//        $roleInspector = new RoleCascading('ROLE_INSPECTOR', [$roleUser]);
//        $roleClient = new RoleCascading('ROLE_CLIENT', [$roleUser]);
//        $roleAccounting = new RoleCascading('ROLE_ACCOUNTING', [$roleEmployee]);
//        $roleAccountingManager = new RoleCascading('ROLE_ACCOUNTING_MANAGER', [$roleAccounting]);
//        $roleSales = new RoleCascading('ROLE_SALES', [$roleEmployee]);
//        $roleCoordination = new RoleCascading('ROLE_COORDINATION', [$roleEmployee]);
//        $roleCoordinationManager = new RoleCascading('ROLE_COORDINATION_MANAGER', [$roleCoordination]);
//        $roleHr = new RoleCascading('ROLE_HR', [$roleEmployee]);
//        $roleOperation = new RoleCascading('ROLE_OPERATION', [$roleEmployee]);
//        $roleOperationManager = new RoleCascading('ROLE_OPERATION_MANAGER', [$roleOperation]);
//        $roleEditor = new RoleCascading('ROLE_EDITOR', [$roleEmployee]);
//        $roleAdmin = new RoleCascading('ROLE_ADMIN', [
//            $roleAccountingManager,
//            $roleSales,
//            $roleCoordinationManager,
//            $roleHr,
//            $roleOperationManager,
//            $roleEditor,
//        ]);
//
//        $alice = new UserCascading('alice', [$roleAdmin]);
//        $identifiers = Acl::extractRequesterIdentifiers($alice);
    }
}
