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

    public function testGrant()
    {
        $this->acl->grant($this->aliceRequester, $this->fooResource, 'view');
        $this->assertEquals(1, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testGrantWithArrayParameter()
    {
        $this->acl->grant($this->aliceRequester, $this->fooResource, ['view']);
        $this->assertEquals(1, $this->findMask($this->aliceRequester, $this->fooResource));
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

    public function testGrantUpdatesAPermissionWhenItHasTo()
    {
        $this->insertPermission($this->malloryRequester, $this->fooResource, 1);

        $this->acl->grant($this->malloryRequester, $this->fooResource, 'edit');
        $this->assertEquals(3, $this->findMask($this->malloryRequester, $this->fooResource));

        $this->acl->grant($this->malloryRequester, $this->barResource, 'edit');
        $this->assertEquals(2, $this->findMask($this->malloryRequester, $this->barResource));
    }

    public function testGrantAnActionAlreadyGranted()
    {
        $this->insertPermission($this->bobRequester, $this->fooResource, 2);

        $this->acl->grant($this->bobRequester, $this->fooResource, 'edit');
        $this->assertEquals(2, $this->findMask($this->bobRequester, $this->fooResource));
    }

    public function testRevoke()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 7);

        $this->acl->revoke($this->aliceRequester, $this->fooResource, 'create');
        $this->assertEquals(3, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testRevokeWithArrayParameter()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 7);

        $this->acl->revoke($this->aliceRequester, $this->fooResource, ['create']);
        $this->assertEquals(3, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testRevokeManyActionsAtDifferentSteps()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 7);

        $this->acl->revoke($this->aliceRequester, $this->fooResource, 'view');
        $this->assertEquals(6, $this->findMask($this->aliceRequester, $this->fooResource));

        $this->acl->revoke($this->aliceRequester, $this->fooResource, 'edit');
        $this->assertEquals(4, $this->findMask($this->aliceRequester, $this->fooResource));
    }

    public function testRevokeManyActionsAtTheSameTime()
    {
        $this->insertPermission($this->aliceRequester, $this->fooResource, 7);

        $this->acl->revoke($this->aliceRequester, $this->fooResource, ['view', 'edit']);
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
            ],
            0,
            [
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR
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
                'mask' => $mask
            ],
            [
                'requester' => \PDO::PARAM_STR,
                'resource' => \PDO::PARAM_STR,
                'mask' => \PDO::PARAM_INT
            ]
        );
    }
}
