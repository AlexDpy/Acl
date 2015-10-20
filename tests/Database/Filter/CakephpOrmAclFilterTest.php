<?php

namespace Tests\AlexDpy\Acl\Database\Filter;

use AlexDpy\Acl\Database\Filter\CakephpOrmAclFilter;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class CakephpOrmAclFilterTest extends AbstractDatabaseFilterTest
{
    /**
     * @var Table
     */
    protected $Posts;

    protected function setUp()
    {
        parent::setUp();

        $configured = ConnectionManager::configured();
        if (empty($configured)) {
            ConnectionManager::config('default', [
                'className' => 'Cake\Database\Connection',
                'driver' => 'Cake\Database\Driver\Sqlite',
                'database' => self::SQLITE_PATH,
            ]);
        }

        $this->Posts = TableRegistry::get('Posts');
    }

    protected function getFilteredPostsIds(array $identifiers, $mask, array $orX = [])
    {
        $orX = array_map(function ($value) {
            return str_replace('p.', 'Posts.', $value);
        }, $orX);

        $query = $this->Posts->find();

        $aclFilter = new CakephpOrmAclFilter($query);
        $aclFilter->apply('Posts', 'id', 'post-', $identifiers, $mask, $orX);

        $postsIds = [];
        foreach ($query->all() as $post) {
            $postsIds[] = $post->id;
        }

        return $postsIds;
    }

}