<?php

namespace Tests\AlexDpy\Acl\Mask;

use AlexDpy\Acl\Mask\AbstractMaskBuilder;
use AlexDpy\Acl\Mask\BasicMaskBuilder;
use AlexDpy\Acl\Mask\MaskBuilderInterface;

class MaskBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testSet()
    {
        $maskBuilder = $this->getMaskBuilder();
        $maskBuilder->set(32);

        $this->assertEquals(32, $this->getReflectionMaskValue($maskBuilder));
    }

    public function testSetWithANonIntegerParameter()
    {
        $maskBuilder = $this->getMaskBuilder();
        try {
            $maskBuilder->set('im not an integer');
            $this->fail();
        } catch (\InvalidArgumentException $e) {

        }
    }

    public function testGet()
    {
        $maskBuilder = $this->getMaskBuilder();
        $this->setReflectionMaskValue($maskBuilder, 16);
        $this->assertEquals(16, $maskBuilder->get());
    }

    public function testAdd()
    {
        $maskBuilder = $this->getMaskBuilder();
        $this->setReflectionMaskValue($maskBuilder, 1);
        $maskBuilder->add(2);
        $this->assertEquals(1 + 2, $this->getReflectionMaskValue($maskBuilder));
    }

    public function testAddTheSameMask()
    {
        $maskBuilder = $this->getMaskBuilder();
        $this->setReflectionMaskValue($maskBuilder, 1);
        $maskBuilder->add(1);
        $this->assertEquals(1, $this->getReflectionMaskValue($maskBuilder));

        $maskBuilder = $this->getMaskBuilder();
        $this->setReflectionMaskValue($maskBuilder, 1 + 2);
        $maskBuilder->add(1);
        $this->assertEquals(1 + 2, $this->getReflectionMaskValue($maskBuilder));
    }

    public function testRemove()
    {
        $maskBuilder = $this->getMaskBuilder();
        $this->setReflectionMaskValue($maskBuilder, 1 + 2 + 8);
        $maskBuilder->remove(2);
        $this->assertEquals(1 + 8, $this->getReflectionMaskValue($maskBuilder));

        $maskBuilder = $this->getMaskBuilder();
        $this->setReflectionMaskValue($maskBuilder, 1 + 2 + 8);
        $maskBuilder->remove(4);
        $this->assertEquals(1 + 2 + 8, $this->getReflectionMaskValue($maskBuilder));

        $maskBuilder = $this->getMaskBuilder();
        $this->setReflectionMaskValue($maskBuilder, 1 + 2 + 8);
        $maskBuilder->remove(1)->remove(2)->remove(16);
        $this->assertEquals(8, $this->getReflectionMaskValue($maskBuilder));
    }

    public function testReset()
    {
        $maskBuilder = $this->getMaskBuilder();
        $this->setReflectionMaskValue($maskBuilder, 4);
        $maskBuilder->reset();
        $this->assertEquals(0, $this->getReflectionMaskValue($maskBuilder));
    }

    public function testResolveMask()
    {
        $maskBuilder = $this->getMaskBuilder();
        $this->assertEquals(2, $maskBuilder->resolveMask('edit'));
        $this->assertEquals(2, $maskBuilder->resolveMask(2));
    }

    public function testResolveMaskThrowsInvalidArgumentException()
    {
        $maskBuilder = $this->getMaskBuilder();
        try {
            $maskBuilder->resolveMask('im a fake');
            $this->fail();
        } catch (\InvalidArgumentException $e) {

        }
    }

    /**
     * @param AbstractMaskBuilder $maskBuilder
     *
     * @return mixed
     */
    private function getReflectionMaskValue(AbstractMaskBuilder $maskBuilder)
    {
        $reflection = new \ReflectionObject($maskBuilder);
        $maskProperty = $reflection->getProperty('mask');
        $maskProperty->setAccessible(true);

        return $maskProperty->getValue($maskBuilder);
    }

    /**
     * @param AbstractMaskBuilder $maskBuilder
     * @param int                 $value
     *
     * @throws \InvalidArgumentException
     */
    private function setReflectionMaskValue(AbstractMaskBuilder $maskBuilder, $value)
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException('$value must be an integer.');
        }

        $reflection = new \ReflectionObject($maskBuilder);
        $maskProperty = $reflection->getProperty('mask');
        $maskProperty->setAccessible(true);

        $maskProperty->setValue($maskBuilder, $value);
    }

    /**
     * @return BasicMaskBuilder
     */
    private function getMaskBuilder()
    {
        return new BasicMaskBuilder();
    }
}
