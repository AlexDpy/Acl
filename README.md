# ACL [![Build Status](https://secure.travis-ci.org/AlexDpy/Acl.png?branch=master)](http://travis-ci.org/AlexDpy/Acl)

> The easiest way to dynamic Access Control List

This library is a PHP implementation of the ACL model.
It has been designed to be very easy to use.


## Install
```sh
$ composer require alexdpy/acl
```


## Update your database schema

You have to create the `acl_permissions` table.  
You can generate the query output by using the `vendor/bin/acl` command in your terminal.
```sh
$ vendor/bin/acl schema:get-create-query 
```
Custom options are:
- the permissions table name
- the requester column length
- the resource column length


## Usage

First, you have to choose a DatabaseProvider.  
This library supports DoctrineDbal/ORM (~2.4), CakephpOrm (~3.0), IlluminateDatabase (>=4.2) or native PDO ([./src/Database/Provider](./src/Database/Provider)).

If you use another database connection library, you have to create a DatabaseProvider that implements the `AlexDpy\Acl\Database\Provider\DatabaseProviderInterface`.

```php
<?php
// example with Doctrine

use AlexDpy\Acl\Database\Provider\DoctrineDbalProvider;
use Doctrine\DBAL\DriverManager;

$connection = DriverManager::getConnection(/* ... */);
$databaseProvider = new DoctrineDbalProvider($connection);
```

Then, all you need to do is to create a new instance of `AlexDpy\Acl\Acl`.

```php
<?php

use AlexDpy\Acl\Acl;

$acl = new Acl($databaseProvider, new PermissionBuffer());
```

$acl uses an `AclSchema` to know what the database schema looks like.  
You can customize the schema options if you have to.
```php
<?php

use AlexDpy\Acl\Acl;
use AlexDpy\Acl\Database\Schema\AclSchema;

$aclSchema = new AclSchema([
    'permissions_table_name' => 'acl_perm',
    'requester_column_length' => 100,
    'resource_column_length' => 100,
]);

$acl = new Acl(
    $databaseProvider,
    new PermissionBuffer(),
    'AlexDpy\Acl\Mask\BasicMaskBuilder',
    $aclSchema
);
```
You can also extends the `AclSchema` and use your own.


### isGranted, grant, revoke

Here is the scenario:
> Given a `user` (the "requester") and a `post` (the "resource")  
> When the `user` wants to `edit` the `post`  
> Then we call `$acl->isGranted($user, $post, 'EDIT')`

$user has to be an instance of `AlexDpy\Acl\Model\RequesterInterface`  
$post has to be an instance of `AlexDpy\Acl\Model\ResourceInterface`

```php
<?php

if (!$acl->isGranted($user, $post, 'EDIT')) {
    throw new \Exception('You can not edit this post !');
}
```

```php
<?php

$acl->grant($user, $post, 'EDIT');
// $user can now edit $post

$acl->revoke($user, $post, 'EDIT');
// $user can not edit $post anymore
```


### The Requester and the Resource

$acl works with a `RequesterInterface` and a `ResourceInterface`.  
Both of them have one method which is used for identify their object.

All is about naming convention. You have to care about identifiers conflicts.  
A good way to do this is to have a prefix representing the object, and a unique id.

```php
<?php

use AlexDpy\Acl\Model\RequesterInterface;

class User implements RequesterInterface
{
    protected $id;
    
    public function getAclRequesterIdentifier()
    {
        return 'user-' . $this->id;
    }
}
```
```php
<?php

use AlexDpy\Acl\Model\ResourceInterface;

class Post implements ResourceInterface
{
    protected $id;
    
    public function getAclResourceIdentifier()
    {
        return 'post-' . $this->id;
    }
}
```

Of course, you can also work with any arbitrary requester or any arbitrary resource:
```php
<?php

use AlexDpy\Acl\Model\Requester;
use AlexDpy\Acl\Model\Resource;

$acl->grant(
    new Requester('user-666'),
    new Resource('post-1337'),
    'VIEW'
);
```

### The CascadingRequesterInterface
Sometimes, It can be useful to work with a requester and his parents. As a user and his security roles.
The `$acl->isGranted()` will take care about all the parents. If one parent is granted, then it will return true.
```php
<?php

use AlexDpy\Acl\Model\CascadingRequesterInterface;

class User implements CascadingRequesterInterface
{
    protected $id;
    
    protected $roles;
    
    public function getAclRequesterIdentifier()
    {
        return 'user-' . $this->id;
    }
    
    public function getAclParentsRequester()
    {
        $parents = [];
        
        foreach ($this->roles as $role) {
            $parents[] = new Requester('role-' . $role);
        }
        
        return $parents;
    }
}
```


### The MaskBuilder

The MaskBuilder is an intanceof `AlexDpy\Acl\Mask\MaskBuilderInterface`.  
Its job is to care about permission level. It works with bitmasks.  
We provide a `BasicMaskBuilder` which has 4 masks :
```
const MASK_VIEW = 1;
const MASK_EDIT = 2;
const MASK_CREATE = 4;
const MASK_DELETE = 8;
```
When you grant a requester with both VIEW and EDIT, the stored mask will be 3 (MASK_VIEW + MASK_EDIT).  
The `public function resolveMask($code);` will convert a readable parameter into the integer mask equivalent.  
It allows you to write `$acl->grant($user, $post, ['view', 'edit']);` or `$acl->grant($user, $post, 3);` for the same result.

If you need more and/or different masks, you can create your own MaskBuilder, extending `AlexDpy\Acl\Mask\AbstractMaskBuilder`.  
And then:
```php
<?php

use AlexDpy\Acl\Acl;

$acl = new Acl($databaseProvider, new PermissionBuffer(), 'My\New\MaskBuilder');
```


### Cache

To avoid useless database requests, $acl needs a `PermissionBuffer`.
PermissionBuffer works with the DoctrineCache library. It needs a `Doctrine\Common\Cache\CacheProvider`.

The easiest way is to use APC:
```php
<?php

use Doctrine\Common\Cache\ApcCache;

$cacheProvider = new ApcCache();
$cacheProvider->setNamespace('acl');

$permissionBuffer = new PermissionBuffer($cacheProvider);
$acl = new Acl($databaseProvider, $permissionBuffer);
```
@see [https://github.com/doctrine/cache](https://github.com/doctrine/cache)


### Filtering lists

@TODO


## LICENSE

[MIT](./LICENSE)
