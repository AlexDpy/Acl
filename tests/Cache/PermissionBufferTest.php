<?php

namespace Tests\AlexDpy\Acl\Cache;

use AlexDpy\Acl\Cache\PermissionBuffer;
use AlexDpy\Acl\Cache\PermissionBufferInterface;
use AlexDpy\Acl\Mask\BasicMaskBuilder;
use AlexDpy\Acl\Model\Permission;
use AlexDpy\Acl\Model\PermissionInterface;
use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\Resource;

class PermissionBufferTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        $permissionBuffer = $this->getPermissionBuffer();
        $permissionBuffer->add($this->generatePermission('alice', 'foo'));
        $buffer = $this->getReflectionBufferValue($permissionBuffer);

        $this->assertTrue(($isset = isset($buffer['alice']['foo'])));

        if ($isset) {
            $this->assertInstanceOf('AlexDpy\Acl\Model\PermissionInterface', $buffer['alice']['foo']);
        }
    }

    public function testRemove()
    {
        $permissionBuffer = $this->getPermissionBuffer();
        $this->setReflectionBufferValue($permissionBuffer, [
            'alice' => [
                'foo' => $this->generatePermission('alice', 'foo', 4),
                'bar' => $this->generatePermission('alice', 'bar', 4),
            ]
        ]);

        $permissionBuffer->remove($this->generatePermission('alice', 'foo', 4));

        $buffer = $this->getReflectionBufferValue($permissionBuffer);
        $this->assertFalse(isset($buffer['alice']['foo']));
        $this->assertTrue(isset($buffer['alice']['bar']));
    }

    public function testHas()
    {
        $permissionBuffer = $this->getPermissionBuffer();
        $this->setReflectionBufferValue($permissionBuffer, [
            'alice' => ['foo' => $this->generatePermission('alice', 'foo', 4)]
        ]);

        $this->assertTrue($permissionBuffer->has(new Requester('alice'), new Resource('foo')));
        $this->assertFalse($permissionBuffer->has(new Requester('alice'), new Resource('bar')));
        $this->assertFalse($permissionBuffer->has(new Requester('mallory'), new Resource('foo')));
    }

    public function testGet()
    {
        $permissionBuffer = $this->getPermissionBuffer();
        $aliceFooPermission = $this->generatePermission('alice', 'foo', 4);
        $this->setReflectionBufferValue($permissionBuffer, [
            'alice' => ['foo' => $aliceFooPermission]
        ]);

        $this->assertEquals(
            $aliceFooPermission,
            $permissionBuffer->get(new Requester('alice'), new Resource('foo'))
        );
        $this->assertNull($permissionBuffer->get(new Requester('alice'), new Resource('bar')));
        $this->assertNull($permissionBuffer->get(new Requester('mallory'), new Resource('foo')));
    }

    /**
     * @param PermissionBufferInterface $permissionBuffer
     *
     * @return mixed
     */
    private function getReflectionBufferValue(PermissionBufferInterface $permissionBuffer)
    {
        $reflection = new \ReflectionObject($permissionBuffer);
        $bufferProperty = $reflection->getProperty('buffer');
        $bufferProperty->setAccessible(true);

        return $bufferProperty->getValue($permissionBuffer);
    }

    /**
     * @param PermissionBufferInterface $permissionBuffer
     * @param array                     $value
     *
     * @throws \InvalidArgumentException
     */
    private function setReflectionBufferValue(PermissionBufferInterface $permissionBuffer, array $value)
    {
        $reflection = new \ReflectionObject($permissionBuffer);
        $bufferProperty = $reflection->getProperty('buffer');
        $bufferProperty->setAccessible(true);

        $bufferProperty->setValue($permissionBuffer, $value);
    }

    /**
     * @param string $requesterIdentifier
     * @param string $resourceIdentifier
     * @param int    $mask
     *
     * @return PermissionInterface
     */
    private function generatePermission($requesterIdentifier, $resourceIdentifier, $mask = 0)
    {
        return new Permission(
            new Requester($requesterIdentifier),
            new Resource($resourceIdentifier),
            new BasicMaskBuilder($mask)
        );
    }

    /**
     * @return PermissionBufferInterface
     */
    private function getPermissionBuffer()
    {
        return new PermissionBuffer();
    }
}
