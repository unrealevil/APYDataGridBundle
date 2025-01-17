<?php

namespace APY\DataGridBundle\Tests\Grid\Column;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Column\DateTimeColumn;
use APY\DataGridBundle\Grid\Filter;
use APY\DataGridBundle\Grid\Row;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class DateTimeColumnTest extends TestCase
{
    public function testGetType(): void
    {
        $column = new DateTimeColumn();
        $this->assertEquals('datetime', $column->getType());
    }

    public function testGetFormat(): void
    {
        $format = 'Y-m-d';

        $column = new DateTimeColumn();
        $column->setFormat($format);

        $this->assertEquals($format, $column->getFormat());
    }

    public function testGetTimezone(): void
    {
        $timezone = 'UTC';

        $column = new DateTimeColumn();
        $column->setTimezone($timezone);

        $this->assertEquals($timezone, $column->getTimezone());
    }

    public function testRenderCellWithoutCallback(): void
    {
        $column = new DateTimeColumn();
        $column->setFormat('Y-m-d H:i:s');

        $dateTime = '2000-01-01 01:00:00';
        $now = new \DateTime($dateTime);

        $this->assertEquals(
            $dateTime,
            $column->renderCell(
                $now,
                $this->createMock(Row::class),
                $this->createMock(Router::class)
            )
        );
    }

    public function testRenderCellWithCallback(): void
    {
        $column = new DateTimeColumn();
        $column->setFormat('Y-m-d H:i:s');
        $column->manipulateRenderCell(static function($value, $row, $router) {
            return '01:00:00';
        });

        $dateTime = '2000-01-01 01:00:00';
        $now = new \DateTime($dateTime);

        $this->assertEquals(
            '01:00:00',
            $column->renderCell(
                $now,
                $this->createMock(Row::class),
                $this->createMock(Router::class)
            )
        );
    }

    public function testFilterWithValue(): void
    {
        $column = new DateTimeColumn();
        $column->setData(['operator' => Column::OPERATOR_BTW, 'from' => '2017-03-22', 'to' => '2017-03-23']);

        $this->assertEquals([
            new Filter(Column::OPERATOR_GT, new \DateTime('2017-03-22')),
            new Filter(Column::OPERATOR_LT, new \DateTime('2017-03-23')),
        ], $column->getFilters('asource'));
    }

    public function testFilterWithoutValue(): void
    {
        $column = new DateTimeColumn();
        $column->setData(['operator' => Column::OPERATOR_ISNULL]);

        $this->assertEquals([new Filter(Column::OPERATOR_ISNULL)], $column->getFilters('asource'));
    }

    public function testQueryIsValid(): void
    {
        $column = new DateTimeColumn();

        $this->assertTrue($column->isQueryValid('2017-03-22 23:00:00'));
    }

    public function testQueryIsInvalid(): void
    {
        $column = new DateTimeColumn();

        $this->assertFalse($column->isQueryValid('foo'));
    }

    public function testInitializeDefaultParams(): void
    {
        $column = new DateTimeColumn();

        $this->assertNull($column->getFormat());
        $this->assertEquals([
            Column::OPERATOR_EQ,
            Column::OPERATOR_NEQ,
            Column::OPERATOR_LT,
            Column::OPERATOR_LTE,
            Column::OPERATOR_GT,
            Column::OPERATOR_GTE,
            Column::OPERATOR_BTW,
            Column::OPERATOR_BTWE,
            Column::OPERATOR_ISNULL,
            Column::OPERATOR_ISNOTNULL,
        ], $column->getOperators());
        $this->assertEquals(Column::OPERATOR_EQ, $column->getDefaultOperator());
        $this->assertEquals(\date_default_timezone_get(), $column->getTimezone());
    }

    public function testInitialize(): void
    {
        $format = 'Y-m-d H:i:s';
        $timezone = 'UTC';

        $params = [
            'format' => $format,
            'operators' => [Column::OPERATOR_LT, Column::OPERATOR_LTE],
            'defaultOperator' => Column::OPERATOR_LT,
            'timezone' => $timezone,
        ];

        $column = new DateTimeColumn($params);

        $this->assertEquals($format, $column->getFormat());
        $this->assertEquals([
            Column::OPERATOR_LT, Column::OPERATOR_LTE,
        ], $column->getOperators());
        $this->assertEquals(Column::OPERATOR_LT, $column->getDefaultOperator());
        $this->assertEquals($timezone, $column->getTimezone());
    }

    /**
     * @dataProvider provideDisplayInput
     */
    public function testCorrectDisplayOut($value, $expectedOutput, $timeZone = null): void
    {
        $column = new DateTimeColumn();
        $column->setFormat('Y-m-d H:i:s');

        if (null !== $timeZone) {
            $column->setTimezone($timeZone);
        }

        $this->assertEquals($expectedOutput, $column->getDisplayedValue($value));
    }

    public function testDisplayValueForDateTimeImmutable(): void
    {
        $now = new \DateTimeImmutable();

        $column = new DateTimeColumn();
        $column->setFormat('Y-m-d H:i:s');
        $this->assertEquals($now->format('Y-m-d H:i:s'), $column->getDisplayedValue($now));
    }

    public function testDateTimeZoneForDisplayValueIsTheSameAsTheColumn(): void
    {
        $column = new DateTimeColumn();
        $column->setFormat('Y-m-d H:i:s');
        $column->setTimezone('UTC');

        $now = new \DateTime('2000-01-01 01:00:00', new \DateTimeZone('Europe/Amsterdam'));

        $this->assertEquals('2000-01-01 00:00:00', $column->getDisplayedValue($now));
    }

    //    public function testDisplayValueWithDefaultFormats(): void
    //    {
    //        $column = new DateTimeColumn();
    //        $now = new \DateTime('2017-03-22 22:52:00');
    //
    //        $this->assertEquals('Mar 22, 2017, 10:52:00 PM', $column->getDisplayedValue($now));
    //    }
    //
    //    public function testDisplayValueWithoutFormatButTimeZone(): void
    //    {
    //        $column = new DateTimeColumn();
    //        $column->setTimezone('UTC');
    //
    //        $now = new \DateTime('2017-03-22 22:52:00', new \DateTimeZone('Europe/Amsterdam'));
    //
    //        $this->assertEquals('Mar 22, 2017, 9:52:00 PM', $column->getDisplayedValue($now));
    //    }
    //
    //    public function testDisplayValueWithFallbackFormat(): void
    //    {
    //        $column = new DateTimeColumn();
    //        $column->setTimezone(\IntlDateFormatter::NONE);
    //
    //        $now = new \DateTime('2017/03/22 22:52:00');
    //
    //        $this->assertEquals('2017-03-22 20:52:00', $column->getDisplayedValue($now));
    //    }

    public static function provideDisplayInput(): array
    {
        $now = new \DateTime();

        return [
            [$now, $now->format('Y-m-d H:i:s')],
            ['2016/01/01 12:13:14', '2016-01-01 12:13:14'],
            [1, '1970-01-01 00:00:01', 'UTC'],
            ['', ''],
        ];
    }
}
