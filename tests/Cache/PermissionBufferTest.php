<?php

namespace Tests\AlexDpy\Acl\Cache;

use AlexDpy\Acl\Cache\PermissionBuffer;
use AlexDpy\Acl\Cache\PermissionBufferInterface;
use AlexDpy\Acl\Mask\BasicMaskBuilder;
use AlexDpy\Acl\Model\Permission;
use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\Resource;
use AlexDpy\Acl\Model\ResourceInterface;

class PermissionBufferTest extends \PHPUnit_Framework_TestCase
{
    protected $cache;

    public function setup()
    {
        $this->cache = $this->prophesize('Doctrine\Common\Cache\CacheProvider');

        parent::setUp();
    }

    protected function generateCacheId(RequesterInterface $requester, ResourceInterface $resource)
    {
        $reflection = new \ReflectionClass($permissionBuffer = new PermissionBuffer());
        $method = $reflection->getMethod('getCacheId');
        $method->setAccessible(true);

        return $method->invokeArgs($permissionBuffer, [$requester, $resource]);
    }

    protected function tearDown()
    {
        $this->cache = null;
        parent::tearDown();
    }

    public function permissionProvider()
    {
        return [
            [
                $requester = new Requester('alice'),
                $resource = new Resource('foo'),
                new Permission($requester, $resource, new BasicMaskBuilder(4)),
                $this->generateCacheId($requester, $resource),
            ],
            [
                $requester = new Requester('alice'),
                $resource = new Resource('bar'),
                new Permission($requester, $resource, new BasicMaskBuilder(4)),
                $this->generateCacheId($requester, $resource),
            ],
        ];
    }

    /**
     * @dataProvider permissionProvider
     */
    public function testAdd(Requester $requester, Resource $resource, Permission $permission, $cacheId)
    {
        $this->cache->save($cacheId, $permission)->shouldBeCalled();
        $permissionBuffer = $this->getPermissionBuffer($this->cache->reveal());
        $permissionBuffer->add($permission);
    }

    /**
     * @dataProvider permissionProvider
     */
    public function testRemove(Requester $requester, Resource $resource, Permission $permission, $cacheId)
    {
        $this->cache->delete($cacheId)->shouldBeCalled();
        $permissionBuffer = $this->getPermissionBuffer($this->cache->reveal());
        $permissionBuffer->remove($permission);
    }

    /**
     * @dataProvider permissionProvider
     */
    public function testGet(Requester $requester, Resource $resource, Permission $permission, $cacheId)
    {
        $this->cache->fetch($cacheId)->willReturn($permission)->shouldBeCalled();
        $permissionBuffer = $this->getPermissionBuffer($this->cache->reveal());
        $this->assertEquals($permissionBuffer->get($requester, $resource), $permission);
    }

    /**
     * @return PermissionBufferInterface
     */
    private function getPermissionBuffer($cacheProvider = null)
    {
        return new PermissionBuffer($cacheProvider);
    }
}
