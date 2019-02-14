<?php

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Dotenv\Dotenv;

/**
 * @package    doctrine-schema-migration-test
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2019 netzmacht David Molineus. All rights reserved
 * @filesource
 *
 */

final class MigrateSchemaWithDataTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    protected function setUp(): void
    {
        $this->setupConnection();
    }

    protected function tearDown(): void
    {
        $this->connection->getSchemaManager()->dropTable('test');
    }

    /** @dataProvider provideTestCases */
    public function testSchemaMigration(
        string $fromType,
        array $fromOptions,
        string $toType,
        array $toOptions,
        array $data
    ): void {
        $schemaManager = $this->connection->getSchemaManager();

        $table = $schemaManager->createSchema()->createTable('test');
        $table->addColumn('test', $fromType, $fromOptions);

        $schemaManager->createTable($table);

        $this->assertTrue($schemaManager->tablesExist(['test']));
        $this->assertCount(1, $schemaManager->listTableColumns('test'));

        foreach ($data as $value) {
            $this->connection->insert('test', ['test' => $value]);
        }

        $this->assertEquals(
            count($data),
            $this->connection->executeQuery('SELECT count(*) FROM test')->fetchColumn()
        );

        $toSchema = new Schema();
        $toTable  = $toSchema->createTable('test');
        $toTable->addColumn('test', $toType, $toOptions);

        $diff = Comparator::compareSchemas($schemaManager->createSchema(), $toSchema);
        $diff->toSql($this->connection->getDatabasePlatform());
    }

    public function provideTestCases(): array
    {
        return [
            ['string', [], 'string', [], ['abs', 123, 12.3]],
            ['string', ['notnull' => false], 'string', [], ['abs', 123, 12.3, null]]
        ];
    }

    private function setupConnection(): void
    {
        if ($this->connection !== null) {
            return;
        }

        $dotenv = new Dotenv();
        $dotenv->load(__DIR__.'/../.env');

        $config = new \Doctrine\DBAL\Configuration();
        $connectionParams = array(
            'dbname' => getenv('DB_DATABASE'),
            'user' => getenv('DB_USER'),
            'password' => getenv('DB_PASS'),
            'host' => getenv('DB_HOST'),
            'driver' => 'pdo_mysql',
            'server_version' => getenv('DB_HOST'),
        );
        $this->connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    }
}
