<?php

namespace APY\DataGridBundle\Tests\Grid\Mapping\Metadata;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Columns;
use APY\DataGridBundle\Grid\Mapping\Metadata\Metadata;
use PHPUnit\Framework\TestCase;

class MetadataTest extends TestCase
{
    private Metadata $metadata;

    protected function setUp(): void
    {
        $this->metadata = new Metadata();
    }

    public function testSetFields(): void
    {
        $field = ['foo' => 'bar'];

        $this->metadata->setFields($field);

        $this->assertEquals($field, $this->metadata->getFields());
    }

    public function testGetFields(): void
    {
        $field = ['foo' => 'bar'];

        $this->metadata->setFields($field);

        $this->assertEquals($field, $this->metadata->getFields());
    }

    public function testHasFieldMappingWithField(): void
    {
        $field = 'foo';
        $value = 'bar';
        $fieldMapping = [$field => ['type' => $value]];

        $this->metadata->setFieldsMappings($fieldMapping);

        $this->assertTrue($this->metadata->hasFieldMapping($field));
        $this->assertFalse($this->metadata->hasFieldMapping('notAddedField'));
    }

    public function testGetterFieldMappingReturnDefaultTypeText(): void
    {
        $field = 'foo';
        $value = 'bar';
        $fieldMapping = [$field => $value];

        $this->metadata->setFieldsMappings($fieldMapping);

        $this->assertEquals('text', $this->metadata->getFieldMappingType($field));
    }

    public function testSetterMappingFieldWithType(): void
    {
        $field = 'foo';
        $value = 'bar';
        $fieldMapping = [$field => ['type' => $value]];

        $this->metadata->setFieldsMappings($fieldMapping);

        $this->assertEquals($fieldMapping[$field], $this->metadata->getFieldMapping($field));
    }

    public function testGetterMappingFieldWithType(): void
    {
        $field = 'foo';
        $value = 'bar';
        $fieldMapping = [$field => ['type' => $value]];

        $this->metadata->setFieldsMappings($fieldMapping);
        $this->assertEquals($value, $this->metadata->getFieldMappingType($field));
    }

    public function testGetterGroupBy(): void
    {
        $groupBy = ['groupBy'];

        $this->metadata->setGroupBy($groupBy);
        $this->assertEquals($groupBy, $this->metadata->getGroupBy());
    }

    public function testGetterName(): void
    {
        $name = 'name';

        $this->metadata->setName($name);

        $this->assertEquals($name, $this->metadata->getName());
    }

    public function testGetColumnsFromMappingWithoutTypeReturnException(): void
    {
        $this->expectException(\Exception::class);

        $field = 'foo';
        $value = 'bar';
        $fieldMapping = [$field => ['type' => $value]];

        $columnsMock = $this->createMock(Columns::class);
        $columnsMock->method('hasExtensionForColumnType')
                    ->with($value)
                    ->willReturn(false);

        $this->metadata->setFields(['foo' => $field]);
        $this->metadata->setFieldsMappings($fieldMapping);
        $this->metadata->getColumnsFromMapping($columnsMock);
    }

    public function testGetColumnsFromMapping(): void
    {
        $field = 'foo';
        $field2 = 'foo2';
        $value = 'bar';
        $value2 = 'bar';
        $fieldMapping = [
            $field => [
                'type' => $value,
            ],
            $field2 => [
                'type' => $value2,
            ],
        ];

        $columnsMockClone = $this->getMockForAbstractClass(Column::class);

        $columnsMock = $this->createMock(Columns::class);
        $columnsMock->method('hasExtensionForColumnType')
                    ->with($value)
                    ->willReturn(true);

        $columnsMock->method('getExtensionForColumnType')
                    ->with($value)
                    ->willReturn($columnsMockClone);

        $this->metadata->setFields(['foo' => $field]);
        $this->metadata->setFieldsMappings($fieldMapping);
        $columns = $this->metadata->getColumnsFromMapping($columnsMock);

        $this->assertInstanceOf('\SplObjectStorage', $columns);
    }
}
