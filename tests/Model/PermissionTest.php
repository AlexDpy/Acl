<?php

namespace tests\Model;

use AlexDpy\Acl\Model\Permission;
use AlexDpy\Acl\Model\PermissionInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class PermissionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectProphecy
     */
    protected $maskBuilder;

    /**
     * @var PermissionInterface
     */
    protected $permission;

    protected function setUp()
    {
        $this->maskBuilder = $this->prophesize('AlexDpy\Acl\Mask\MaskBuilderInterface');

        $this->permission = new Permission(
            $this->prophesize('AlexDpy\Acl\Model\RequesterInterface')->reveal(),
            $this->prophesize('AlexDpy\Acl\Model\ResourceInterface')->reveal(),
            $this->maskBuilder->reveal()
        );
    }

    public function testConstructPersistentShouldBeFalse()
    {
        $permission = new Permission(
            $this->prophesize('AlexDpy\Acl\Model\RequesterInterface')->reveal(),
            $this->prophesize('AlexDpy\Acl\Model\ResourceInterface')->reveal(),
            $this->maskBuilder->reveal()
        );

        $this->assertFalse($permission->isPersistent());
    }

    public function testGetMaskShouldCallMaskBuilderGet()
    {
        $this->maskBuilder->get()->shouldBeCalled(1);

        $this->permission->getMask();
    }

    public function testGrantShouldCallMaskBuilderAdd()
    {
        $this->maskBuilder->add(Argument::exact('view'))->shouldBeCalled(1);

        $this->permission->grant('view');
    }

    public function testRevokeShouldCallMaskBuilderRemove()
    {
        $this->maskBuilder->remove(Argument::exact('view'))->shouldBeCalled(1);

        $this->permission->revoke('view');
    }

    public function testIsGrantedShouldCallMaskBuilderResolveMask()
    {
        $this->maskBuilder->resolveMask(Argument::exact('view'))->shouldBeCalled(1);
        $this->maskBuilder->get()->shouldBeCalled(1);

        $this->permission->isGranted('view');
    }

    /**
     * @dataProvider isGrantedMaskComparisonProvider
     */
    public function testIsGrantedMaskComparison($action, $requiredMask, $mask, $result)
    {
        $this->maskBuilder->resolveMask(Argument::exact($action))->willReturn($requiredMask);
        $this->maskBuilder->get()->willReturn($mask);

        if ($result) {
            $this->assertTrue($this->permission->isGranted($action));
        } else {
            $this->assertfalse($this->permission->isGranted($action));
        }
    }

    public function isGrantedMaskComparisonProvider()
    {
        return [
            ['view', 1, 0, false],
            ['view', 1, 1, true],
            ['view', 1, 2, false],
            ['view', 1, 3, true],
            ['view', 1, 4, false],
            ['view', 1, 5, true],
            ['view', 1, 6, false],
            ['view', 1, 7, true],
            ['view', 1, 8, false],
            ['view', 1, 9, true],
            ['view', 1, 10, false],
            ['view', 1, 11, true],
            ['view', 1, 12, false],
            ['view', 1, 13, true],
            ['view', 1, 14, false],
            ['view', 1, 15, true],
            ['view', 1, 16, false],

            ['view', 1, 128, false],
            ['view', 1, 129, true],
            ['view', 1, 130, false],
            ['view', 1, 131, true],
            ['view', 1, 132, false],
            ['view', 1, 133, true],
            ['view', 1, 134, false],
            ['view', 1, 135, true],
            ['view', 1, 136, false],
            ['view', 1, 137, true],
            ['view', 1, 138, false],
            ['view', 1, 139, true],
            ['view', 1, 140, false],
            ['view', 1, 141, true],
            ['view', 1, 142, false],
            ['view', 1, 143, true],
            ['view', 1, 144, false],

            ['edit', 2, 0, false],
            ['edit', 2, 1, false],
            ['edit', 2, 2, true],
            ['edit', 2, 3, true],
            ['edit', 2, 4, false],
            ['edit', 2, 5, false],
            ['edit', 2, 6, true],
            ['edit', 2, 7, true],
            ['edit', 2, 8, false],
            ['edit', 2, 9, false],
            ['edit', 2, 10, true],
            ['edit', 2, 11, true],
            ['edit', 2, 12, false],
            ['edit', 2, 13, false],
            ['edit', 2, 14, true],
            ['edit', 2, 15, true],
            ['edit', 2, 16, false],

            ['edit', 2, 128, false],
            ['edit', 2, 129, false],
            ['edit', 2, 130, true],
            ['edit', 2, 131, true],
            ['edit', 2, 132, false],
            ['edit', 2, 133, false],
            ['edit', 2, 134, true],
            ['edit', 2, 135, true],
            ['edit', 2, 136, false],
            ['edit', 2, 137, false],
            ['edit', 2, 138, true],
            ['edit', 2, 139, true],
            ['edit', 2, 140, false],
            ['edit', 2, 141, false],
            ['edit', 2, 142, true],
            ['edit', 2, 143, true],
            ['edit', 2, 144, false],

            ['create', 4, 0, false],
            ['create', 4, 1, false],
            ['create', 4, 2, false],
            ['create', 4, 3, false],
            ['create', 4, 4, true],
            ['create', 4, 5, true],
            ['create', 4, 6, true],
            ['create', 4, 7, true],
            ['create', 4, 8, false],
            ['create', 4, 9, false],
            ['create', 4, 10, false],
            ['create', 4, 11, false],
            ['create', 4, 12, true],
            ['create', 4, 13, true],
            ['create', 4, 14, true],
            ['create', 4, 15, true],
            ['create', 4, 16, false],

            ['create', 4, 128, false],
            ['create', 4, 129, false],
            ['create', 4, 130, false],
            ['create', 4, 131, false],
            ['create', 4, 132, true],
            ['create', 4, 133, true],
            ['create', 4, 134, true],
            ['create', 4, 135, true],
            ['create', 4, 136, false],
            ['create', 4, 137, false],
            ['create', 4, 138, false],
            ['create', 4, 139, false],
            ['create', 4, 140, true],
            ['create', 4, 141, true],
            ['create', 4, 142, true],
            ['create', 4, 143, true],
            ['create', 4, 144, false],

            ['delete', 8, 0, false],
            ['delete', 8, 1, false],
            ['delete', 8, 2, false],
            ['delete', 8, 3, false],
            ['delete', 8, 4, false],
            ['delete', 8, 5, false],
            ['delete', 8, 6, false],
            ['delete', 8, 7, false],
            ['delete', 8, 8, true],
            ['delete', 8, 9, true],
            ['delete', 8, 10, true],
            ['delete', 8, 11, true],
            ['delete', 8, 12, true],
            ['delete', 8, 13, true],
            ['delete', 8, 14, true],
            ['delete', 8, 15, true],
            ['delete', 8, 16, false],

            ['delete', 8, 128, false],
            ['delete', 8, 129, false],
            ['delete', 8, 130, false],
            ['delete', 8, 131, false],
            ['delete', 8, 132, false],
            ['delete', 8, 133, false],
            ['delete', 8, 134, false],
            ['delete', 8, 135, false],
            ['delete', 8, 136, true],
            ['delete', 8, 137, true],
            ['delete', 8, 138, true],
            ['delete', 8, 139, true],
            ['delete', 8, 140, true],
            ['delete', 8, 141, true],
            ['delete', 8, 142, true],
            ['delete', 8, 143, true],
            ['delete', 8, 144, false],
        ];
    }
}
