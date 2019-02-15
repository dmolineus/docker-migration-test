<?php

final class SchemaMigrationTest extends SchemaMigrationTestCase
{
    /** @dataProvider provideStringColumnTypeData */
    public function testChangeStringColumnType(
        string $fromType,
        array $fromOptions,
        string $toType,
        array $toOptions,
        array $data
    ): void {
        $this->createTable($fromType, $fromOptions);
        $this->insertData($data);

        $toSchema = $this->createToSchema($toType, $toOptions);
        $this->migrateToSchema($toSchema);
    }

    public function provideStringColumnTypeData(): array
    {
        return [
            ['string', [], 'decimal', ['default' => 0], [123, 12.3, PHP_INT_SIZE]],
            ['string', [], 'decimal', ['default' => 0], ['foo', 123, PHP_INT_SIZE, 12.3]],
            ['string', [], 'smallint', ['default' => 0], [123, 12.3, PHP_INT_SIZE]],
            ['string', [], 'smallint', ['default' => 0], ['foo', 123, PHP_INT_SIZE, 12.3]],
            ['string', ['length' => 32], 'string', ['length' => 8], [str_repeat('a', 32)]]
        ];
    }

    /** @dataProvider provideNullToNotNullData */
    public function testNullToNotNull(
        string $fromType,
        array $fromOptions,
        string $toType,
        array $toOptions,
        array $data
    ): void {
        $this->createTable($fromType, $fromOptions);
        $this->insertData($data);

        $toSchema = $this->createToSchema($toType, $toOptions);
        $this->migrateToSchema($toSchema);
    }

    public function provideNullToNotNullData(): array
    {
        return [
            ['string', ['notnull' => false], 'string', [], ['abs', 123, 12.3, null]],
            ['string', ['notnull' => false], 'decimal', ['default' => 0], [123, 12.3, null]],
        ];
    }

    /** @dataProvider provideUniqueIndexData */
    public function testUniqueIndex(
        string $fromType,
        array $fromOptions,
        string $toType,
        array $toOptions,
        array $data
    ): void {
        $this->createTable($fromType, $fromOptions);
        $this->insertData($data);

        $toSchema = $this->createToSchema($toType, $toOptions);
        $toSchema->getTable(self::TABLE_NAME)->addUniqueIndex([self::COLUMN_NAME]);

        $this->migrateToSchema($toSchema);
    }

    public function provideUniqueIndexData(): array
    {
        return [
            ['string', [], 'string', [], ['abc', 'foo', 'bar']],
            ['string', [], 'decimal', [], ['abc', 'foo', 'foo']],
        ];
    }
}
