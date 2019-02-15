<?php

declare(strict_types=1);

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

abstract class SchemaMigrationTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TABLE_NAME = 'test_table';
    protected const COLUMN_NAME = 'test_column';

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    protected function setUp(): void
    {
        $this->setupConnection();
    }

    protected function tearDown(): void
    {
        $this->connection->getSchemaManager()->dropTable(self::TABLE_NAME);
    }

    private function setupConnection(): void
    {
        if ($this->connection !== null) {
            return;
        }

        $config           = new Configuration();
        $connectionParams = [
            'dbname'   => getenv('DB_DATABASE'),
            'user'     => getenv('DB_USER'),
            'password' => getenv('DB_PASS'),
            'host'     => 'db',
            'driver'   => 'pdo_mysql',
        ];
        $this->connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    }

    protected function createTable(string $fromType, array $fromOptions): void
    {
        $schemaManager = $this->connection->getSchemaManager();

        $table = $schemaManager->createSchema()->createTable(self::TABLE_NAME);
        $table->addColumn(self::COLUMN_NAME, $fromType, $fromOptions);

        $schemaManager->createTable($table);

        $this->assertTrue($schemaManager->tablesExist([self::TABLE_NAME]));
        $this->assertCount(1, $schemaManager->listTableColumns(self::TABLE_NAME));
    }

    protected function insertData(array $data): void
    {
        foreach ($data as $value) {
            $this->connection->insert(self::TABLE_NAME, [self::COLUMN_NAME => $value]);
        }

        $this->assertEquals(
            count($data),
            $this->connection->executeQuery('SELECT count(*) FROM ' . self::TABLE_NAME)->fetchColumn()
        );
    }

    protected function createToSchema(string $toType, array $toOptions): Schema
    {
        $toSchema = new Schema();
        $toTable  = $toSchema->createTable(self::TABLE_NAME);
        $toTable->addColumn(self::COLUMN_NAME, $toType, $toOptions);

        return $toSchema;
    }

    protected function migrateToSchema(Schema $toSchema): void
    {
        $diff = Comparator::compareSchemas($this->connection->getSchemaManager()->createSchema(), $toSchema);

        try {
            foreach ($diff->toSql($this->connection->getDatabasePlatform()) as $sql) {
                $this->connection->exec($sql);
            }

        } catch (\Doctrine\DBAL\DBALException $e) {
            $this->fail($e->getMessage());
        }

        $this->assertTrue(true);
    }
}
