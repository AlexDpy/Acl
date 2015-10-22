<?php

namespace Tests\AlexDpy\Acl\Database\Provider;

use AlexDpy\Acl\Database\Provider\DatabaseProviderInterface;
use AlexDpy\Acl\Exception\MaskNotFoundException;
use AlexDpy\Acl\Mask\BasicMaskBuilder;
use AlexDpy\Acl\Model\Permission;
use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\Resource;
use Tests\AlexDpy\Acl\Database\AbstractDatabaseTest;

abstract class AbstractDatabaseProviderTest extends AbstractDatabaseTest
{
    /**
     * @var DatabaseProviderInterface
     */
    protected $databaseProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->databaseProvider = $this->getDatabaseProvider();
    }

    /**
     * @return DatabaseProviderInterface
     */
    abstract public function getDatabaseProvider();

    public function testFindMaskShouldThrowMaskNotFoundExceptionIfNotFound()
    {
        try {
            $this->databaseProvider->findMask(new Requester('i do not exist'), new Resource('i do not exist'));

            $this->fail(get_class($this) . '::findMask should throw a MaskNotFoundException if permission does not exist');
        } catch (MaskNotFoundException $e) {
        }
    }

    public function testFindMaskShouldReturnIntegerWhenFound()
    {
        $this->insertFixture($this->aliceRequester, $this->fooResource, 1);

        $this->assertInternalType('int', $this->databaseProvider->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testDeletePermission()
    {
        $this->insertFixture($this->aliceRequester, $this->fooResource, 1);

        $this->databaseProvider->deletePermission(
            new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(1))
        );

        $this->assertNull($this->findFixture($this->aliceRequester, $this->fooResource));
    }

    public function testUpdatePermission()
    {
        $this->insertFixture($this->aliceRequester, $this->fooResource, 1);

        $this->databaseProvider->updatePermission(
            new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(3))
        );

        $this->assertEquals(3, $this->findFixture($this->aliceRequester, $this->fooResource)['mask']);
    }

    public function testInsertPermission()
    {
        $this->databaseProvider->insertPermission(
            new Permission($this->aliceRequester, $this->fooResource, new BasicMaskBuilder(3))
        );

        $this->assertEquals([
            'requester' => $this->aliceRequester->getAclRequesterIdentifier(),
            'resource' => $this->fooResource->getAclResourceIdentifier(),
            'mask' => 3,
        ], $this->findFixture($this->aliceRequester, $this->fooResource));
    }
}
