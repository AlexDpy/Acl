<?php

namespace Tests\AlexDpy\Acl;

use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\RequesterInterface;
use AlexDpy\Acl\Model\Resource;
use AlexDpy\Acl\Model\ResourceInterface;

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
     * @var ResourceInterface
     */
    protected $fooResource;

    protected function setUp()
    {
        parent::setUp();

        $this->aliceRequester = new Requester('alice');
        $this->bobRequester = new Requester('bob');

        $this->fooResource = new Resource('foo');
    }


    public function testGrant()
    {
        $this->acl->grant($this->aliceRequester, $this->fooResource, 'view');
        $this->assertEquals(1, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testGrantWithArrayParameter()
    {
        $this->acl->grant($this->bobRequester, $this->fooResource, ['view']);
        $this->assertEquals(1, $this->findMask($this->bobRequester, $this->fooResource));
    }

    public function testGrantManyActionsAtDifferentSteps()
    {
        $this->acl->grant($this->aliceRequester, $this->fooResource, 'view');
        $this->assertEquals(1, $this->findMask($this->aliceRequester, $this->fooResource));

        $this->acl->grant($this->aliceRequester, $this->fooResource, 'edit');
        $this->assertEquals(3, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testGrantManyActionsAtTheSameTime()
    {
        $this->acl->grant($this->aliceRequester, $this->fooResource, ['view', 'edit']);
        $this->assertEquals(3, $this->findMask($this->aliceRequester, $this->fooResource));
    }

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
                'resource' => $resource->getAclResourceIdentifier()
            ]
        );
    }
}
