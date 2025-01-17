<?php

namespace APY\DataGridBundle\Tests\Grid\Column;

use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Tests\PhpunitTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\Role;

class ActionsColumnTest extends TestCase
{
    use PhpunitTrait;

    /** @var ActionsColumn */
    private $column;

    public function testConstructor(): void
    {
        $columnId = 'columnId';
        $columnTitle = 'columnTitle';

        $rowAction1 = $this->createMock(RowAction::class);
        $rowAction2 = $this->createMock(RowAction::class);
        $column = new ActionsColumn($columnId, $columnTitle, [$rowAction1, $rowAction2]);

        $this->assertEquals([$rowAction1, $rowAction2], $column->getRowActions());
        $this->assertEquals($columnId, $column->getId());
        $this->assertEquals($columnTitle, $column->getTitle());
        $this->assertFalse($column->isSortable());
        $this->assertFalse($column->isVisibleForSource());
        $this->assertTrue($column->isFilterable());
    }

    public function testGetType(): void
    {
        $this->assertEquals('actions', $this->column->getType());
    }

    public function testGetFilterType(): void
    {
        $this->assertEquals('actions', $this->column->getFilterType());
    }

    public function testGetActionsToRender(): void
    {
        $row = $this->createMock(Row::class);

        $rowAction1 = $this->createMock(RowAction::class);
        $rowAction1->method('render')->with($row)->willReturn(null);
        $rowAction2 = $this->createMock(RowAction::class);
        $rowAction2->method('render')->with($row)->willReturn($rowAction2);

        $column = new ActionsColumn('columnId', 'columnTitle', [
            $rowAction1,
            $rowAction2,
        ]);

        $this->assertEquals([1 => $rowAction2], $column->getActionsToRender($row));
    }

    public function testGetRowActions(): void
    {
        $rowAction1 = $this->createMock(RowAction::class);
        $rowAction2 = $this->createMock(RowAction::class);
        $column = new ActionsColumn('columnId', 'columnTitle', [
            $rowAction1,
            $rowAction2,
        ]);

        $this->assertEquals([$rowAction1, $rowAction2], $column->getRowActions());
    }

    public function testSetRowActions(): void
    {
        $rowAction1 = $this->createMock(RowAction::class);
        $rowAction2 = $this->createMock(RowAction::class);
        $column = new ActionsColumn('columnId', 'columnTitle', []);
        $column->setRowActions([$rowAction1, $rowAction2]);

        $this->assertEquals([$rowAction1, $rowAction2], $column->getRowActions());
    }

    public function testIsNotVisibleIfExported(): void
    {
        $isExported = true;
        $this->assertFalse($this->column->isVisible($isExported));
    }

    public function testIsVisibleIfNotExportedAndNoAuthChecker(): void
    {
        $this->assertTrue($this->column->isVisible());
    }

    public function testIsVisibleIfNotExportedNoAuthCheckerAndNotRole(): void
    {
        $this->column->setAuthorizationChecker($this->createMock(AuthorizationCheckerInterface::class));
        $this->assertTrue($this->column->isVisible());
    }

    public function testIsVisibleIfAuthCheckerIsGranted(): void
    {
        $role = $this->createMock(Role::class);
        $this->column->setRole($role);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->with($role)->willReturn(true);
        $this->column->setAuthorizationChecker($authChecker);

        $this->assertTrue($this->column->isVisible());
    }

    public function testIsNotVisibleIfAuthCheckerIsNotGranted(): void
    {
        $role = $this->createMock(Role::class);
        $this->column->setRole($role);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->with($role)->willReturn(false);
        $this->column->setAuthorizationChecker($authChecker);

        $this->assertFalse($this->column->isVisible());
    }

    public function testGetPrimaryFieldAsRouteParametersIfRouteParametersNotSetted(): void
    {
        $row = $this->createMock(Row::class);
        $row->method('getPrimaryField')->willReturn('id');
        $row->method('getPrimaryFieldValue')->willReturn(1);

        $rowAction = $this->createMock(RowAction::class);
        $rowAction->method('getRouteParameters')->willReturn([]);

        $this->assertEquals(['id' => 1], $this->column->getRouteParameters($row, $rowAction));
    }

    public function testGetRouteParameters(): void
    {
        $row = $this->createMock(Row::class);
        $row
            ->method('getField')
            ->with(...self::withConsecutive(['foo.bar'], ['barFoo']))
            ->willReturnOnConsecutiveCalls('testValue', 'aValue');

        $rowAction = $this->createMock(RowAction::class);
        $rowAction
            ->method('getRouteParametersMapping')
            ->with(...self::withConsecutive(['foo.bar'], ['barFoo']))
            ->willReturnOnConsecutiveCalls(null, 'aName');

        $rowAction->method('getRouteParameters')->willReturn([
            'foo' => 1,
            'foo.bar.foobar' => 2,
            1 => 'foo.bar',
            '2' => 'barFoo',
        ]);

        $this->assertEquals([
            'foo' => 1,
            'fooBarFoobar' => 2,
            'fooBar' => 'testValue',
            'aName' => 'aValue',
        ], $this->column->getRouteParameters($row, $rowAction));
    }

    protected function setUp(): void
    {
        $rowAction1 = $this->createMock(RowAction::class);
        $rowAction2 = $this->createMock(RowAction::class);
        $this->column = new ActionsColumn('columnId', 'columnTitle', [
            $rowAction1,
            $rowAction2,
        ]);
    }
}
