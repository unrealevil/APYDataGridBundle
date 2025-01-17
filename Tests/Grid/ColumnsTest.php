<?php

namespace APY\DataGridBundle\Tests\Grid;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Columns;
use APY\DataGridBundle\Grid\Helper\ColumnsIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ColumnsTest extends TestCase
{
    /** @var Columns */
    private $columns;

    /** @var AuthorizationCheckerInterface */
    private $authChecker;

    public function testGetIterator(): void
    {
        $iterator = $this->columns->getIterator();
        $this->assertInstanceOf(ColumnsIterator::class, $iterator);
    }

    public function testAddColumn(): void
    {
        $column = $this->buildColumnMocks(1);
        $this->columns->addColumn($column);

        $this->equalTo(1, $this->columns->count());
    }

    public function testAddColumnsOrder(): void
    {
        self::markTestSkipped();
        [$column1, $column2, $column3, $column4, $column5] = $this->buildColumnMocks(5);

        $this->columns
            ->addColumn($column1)
            ->addColumn($column2, 1)
            ->addColumn($column3, 2)
            ->addColumn($column4, -1)
            ->addColumn($column5, 'foo');

        $this->assertAttributeSame([$column2, $column3, $column4, $column1, $column5], 'columns', $this->columns);
    }

    public function testRaiseExceptionIfGetColumnByIdDoesNotExists(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $column = $this->buildColumnMocks(1);
        $this->columns->addColumn($column);

        $this->columns->getColumnById('foo');
    }

    public function testGetColumnById(): void
    {
        $column = $this->buildColumnMocks(1);
        $column->method('getId')->willReturn('foo');
        $this->columns->addColumn($column);

        $this->assertSame($column, $this->columns->getColumnById('foo'));
    }

    public function testHasColumnById(): void
    {
        $column = $this->buildColumnMocks(1);
        $column->method('getId')->willReturn('foo');
        $this->columns->addColumn($column);

        $this->assertSame($column, $this->columns->hasColumnById('foo', true));
        $this->assertTrue($this->columns->hasColumnById('foo', false));
    }

    public function testRaiseExceptionIfGetPrimaryColumnDoesNotExists(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $column = $this->buildColumnMocks(1);
        $column->method('isPrimary')->willReturn(false);
        $this->columns->addColumn($column);

        $this->columns->getPrimaryColumn();
    }

    public function testGetPrimaryColumn(): void
    {
        [$column1, $column2, $column3] = $this->buildColumnMocks(3);

        $column1->method('isPrimary')->willReturn(false);
        $this->columns->addColumn($column1);

        $column2->method('isPrimary')->willReturn(true);
        $this->columns->addColumn($column2);

        $column3->method('isPrimary')->willReturn(true);
        $this->columns->addColumn($column3);

        $this->assertSame($column2, $this->columns->getPrimaryColumn());
    }

    public function testAddExtension(): void
    {
        self::markTestSkipped();
        $column1 = $this->createMock(Column::class);
        $column1->method('getType')->willReturn('foo');

        $column2 = $this->createMock(Column::class);
        $column2->method('getType')->willReturn('bar');

        $this->columns
            ->addExtension($column1)
            ->addExtension($column2);

        $this->assertAttributeEquals(['foo' => $column1, 'bar' => $column2], 'extensions', $this->columns);
    }

    public function testHasExtensionForColumnType(): void
    {
        $column1 = $this->createMock(Column::class);
        $column1->method('getType')->willReturn('foo');

        $this->columns->addExtension($column1);

        $this->assertTrue($this->columns->hasExtensionForColumnType('foo'));
        $this->assertFalse($this->columns->hasExtensionForColumnType('bar'));
    }

    public function testGetExtensionForColumnType(): void
    {
        $column1 = $this->createMock(Column::class);
        $column1->method('getType')->willReturn('foo');

        $this->columns->addExtension($column1);

        $this->assertEquals($column1, $this->columns->getExtensionForColumnType('foo'));
    }

    public function testGetHash(): void
    {
        $this->assertEquals('', $this->columns->getHash());

        [$column1, $column2, $column3, $column4] = $this->buildColumnMocks(4);

        $column1->method('getId')->willReturn('this');
        $column2->method('getId')->willReturn('Is');
        $column3->method('getId')->willReturn('The');
        $column4->method('getId')->willReturn('Hash');

        $this->columns
            ->addColumn($column1)
            ->addColumn($column2)
            ->addColumn($column3)
            ->addColumn($column4);

        $this->assertEquals('thisIsTheHash', $this->columns->getHash());
    }

    public function testSetColumnsOrder(): void
    {
        self::markTestSkipped();
        [$column1, $column2, $column3] = $this->buildColumnMocks(3);

        $column1->method('getId')->willReturn('col1');
        $column2->method('getId')->willReturn('col2');
        $column3->method('getId')->willReturn('col3');

        $this->columns
            ->addColumn($column1)
            ->addColumn($column2)
            ->addColumn($column3);
        $this->columns->setColumnsOrder(['col3', 'col1', 'col2']);

        $this->assertAttributeSame([$column3, $column1, $column2], 'columns', $this->columns);
    }

    public function testPartialSetColumnsOrderAndKeepOthers(): void
    {
        self::markTestSkipped();
        [$column1, $column2, $column3] = $this->buildColumnMocks(3);

        $column1->method('getId')->willReturn('col1');
        $column2->method('getId')->willReturn('col2');
        $column3->method('getId')->willReturn('col3');

        $this->columns
            ->addColumn($column1)
            ->addColumn($column2)
            ->addColumn($column3);
        $this->columns->setColumnsOrder(['col3', 'col2'], true);

        $this->assertAttributeSame([$column3, $column2, $column1], 'columns', $this->columns);
    }

    public function testPartialSetColumnsOrderWithoutKeepOthers(): void
    {
        self::markTestSkipped();
        [$column1, $column2, $column3] = $this->buildColumnMocks(3);

        $column1->method('getId')->willReturn('col1');
        $column2->method('getId')->willReturn('col2');
        $column3->method('getId')->willReturn('col3');

        $this->columns
            ->addColumn($column1)
            ->addColumn($column2)
            ->addColumn($column3);
        $this->columns->setColumnsOrder(['col3', 'col2'], false);

        $this->assertAttributeSame([$column3, $column2], 'columns', $this->columns);
    }

    /**
     * @param int $number
     *
     * @return array|MockObject[]|MockObject|Column
     */
    private function buildColumnMocks($number)
    {
        $mocks = [];
        for ($i = 0; $i < $number; ++$i) {
            $column = $this->createMock(Column::class);
            $column
                ->expects($this->once())
                ->method('setAuthorizationChecker')
                ->with($this->authChecker);

            $mocks[] = $column;
        }

        if (1 == $number) {
            return \current($mocks);
        }

        return $mocks;
    }

    protected function setUp(): void
    {
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->columns = new Columns($this->authChecker);
    }
}
