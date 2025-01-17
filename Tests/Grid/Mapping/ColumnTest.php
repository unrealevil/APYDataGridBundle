<?php

namespace APY\DataGridBundle\Tests\Grid\Mapping;

use APY\DataGridBundle\Grid\Mapping\Column;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    private string $stringMetadata;
    private array $arrayMetadata;

    protected function setUp(): void
    {
        $this->stringMetadata = 'foo';
        $this->arrayMetadata = ['foo' => 'bar', 'groups' => 'baz'];
    }

    public function testColumnMetadataCanBeEmpty(): void
    {
        $column = new Column([]);
        $this->assertEmpty($column->getMetadata());
        $this->assertEquals(['default'], $column->getGroups());
    }

    public function testColumnArrayMetadataInjectedInConstructor(): void
    {
        $column = new Column($this->arrayMetadata);
        $this->assertEquals($this->arrayMetadata, $column->getMetadata());
    }
}
