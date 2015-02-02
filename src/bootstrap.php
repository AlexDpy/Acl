<?php

require __DIR__ . '/../vendor/autoload.php';

use Doctrine\Common\Cache\ApcCache;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use AlexDpy\Acl\Acl;

$configuration = new Configuration();

$configuration->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

$configuration->setResultCacheImpl(new ApcCache());

$connection = DriverManager::getConnection(
    [
        'driver' => 'pdo_mysql',
        'host' => 'localhost',
        'user' => 'root',
        'password' => '',
        'dbname' => 'acl',
    ],
    $configuration
);

/*
$schema = new Schema();

$permissionsTable = $schema->createTable('acl_permissions');
$permissionsTable->addColumn('requester', Type::STRING, ['length' => 255]);
$permissionsTable->addColumn('resource', Type::STRING, ['length' => 255]);
$permissionsTable->addColumn('mask', Type::INTEGER);
$permissionsTable
    ->setPrimaryKey(['requester', 'resource'])
    ->addUniqueIndex(['resource', 'requester'])
    ->addIndex(['resource', 'requester']);

$connection->beginTransaction();
try {
    foreach ($schema->toDropSql($connection->getDatabasePlatform()) as $query) {
        $connection->exec($query);
    }
    $connection->commit();
} catch (TableNotFoundException $e) {
    $connection->rollBack();
}

$connection->transactional(function(Connection $connection) use ($schema) {
    foreach ($schema->toSql($connection->getDatabasePlatform()) as $query) {
        $connection->exec($query);
    }
});
*/

$acl = new Acl($connection);

$alex = new \AlexDpy\Acl\Model\Requester('user-1');
$pageContact = new \AlexDpy\Acl\Model\Resource('page-1');

$acl->grant($alex, $pageContact, ['view', 'edit', 'delete']);

var_dump($acl->isGranted($alex, $pageContact, 'view'));
var_dump($acl->isGranted($alex, $pageContact, 'edit'));
var_dump($acl->isGranted($alex, $pageContact, 'create'));
var_dump($acl->isGranted($alex, $pageContact, 'delete'));


$acl->revoke($alex, $pageContact, 'view');

var_dump($acl->isGranted($alex, $pageContact, 'view'));

