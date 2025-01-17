<?php

namespace APY\DataGridBundle\Tests\Grid\Helper;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Helper\ColumnsIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ColumnsIteratorTest extends TestCase
{
    /** @var MockObject|ColumnsIterator */
    private $iterator;

    public function testAcceptAnyColumn(): void
    {
        $this->setUpMocks();
        $columnsIterator = new ColumnsIterator($this->iterator, false);

        $this->assertTrue($columnsIterator->accept());
    }

    public function testAcceptSourceColumnThatsVisibile(): void
    {
        $this->setUpMocks(true);
        $columnsIterator = new ColumnsIterator($this->iterator, true);

        $this->assertTrue($columnsIterator->accept());
    }

    public function testNotAcceptSourceColumnThatsNotVisibile(): void
    {
        $this->setUpMocks(false);
        $columnsIterator = new ColumnsIterator($this->iterator, true);

        $this->assertFalse($columnsIterator->accept());
    }

    /**
     * @param bool|null $isVisibleForSource
     */
    protected function setUpMocks($isVisibleForSource = null)
    {
        $column = $this->getMockBuilder(Column::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (null === $isVisibleForSource) {
            $column->expects($this->never())->method('isVisibleForSource');
        } else {
            $column->expects($this->any())->method('isVisibleForSource')->willReturn($isVisibleForSource);
        }

        $this->iterator = $this->getMockBuilder(\Iterator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->iterator->expects($this->any())->method('current')->willReturn($column);
    }
}
