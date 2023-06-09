<?php

namespace LaminasTest\Db\ResultSet;

use ArrayIterator;
use ArrayObject;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\Exception\InvalidArgumentException;
use Laminas\Db\ResultSet\Exception\RuntimeException;
use Laminas\Db\ResultSet\ResultSet;
use PHPUnit\Framework\TestCase;
use SplStack;
use stdClass;

use function is_array;
use function rand;
use function var_export;

class ResultSetIntegrationTest extends TestCase
{
    /** @var ResultSet */
    protected $resultSet;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->resultSet = new ResultSet();
    }

    public function testRowObjectPrototypeIsPopulatedByRowObjectByDefault()
    {
        $row = $this->resultSet->getArrayObjectPrototype();
        self::assertInstanceOf('ArrayObject', $row);
    }

    public function testRowObjectPrototypeIsMutable()
    {
        $row = new ArrayObject();
        $this->resultSet->setArrayObjectPrototype($row);
        self::assertSame($row, $this->resultSet->getArrayObjectPrototype());
    }

    public function testRowObjectPrototypeMayBePassedToConstructor()
    {
        $row       = new ArrayObject();
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT, $row);
        self::assertSame($row, $resultSet->getArrayObjectPrototype());
    }

    public function testReturnTypeIsObjectByDefault()
    {
        self::assertEquals(ResultSet::TYPE_ARRAYOBJECT, $this->resultSet->getReturnType());
    }

    /** @psalm-return array<array-key, array{0: mixed}> */
    public function invalidReturnTypes(): array
    {
        return [
            [1],
            [1.0],
            [true],
            ['string'],
            [['foo']],
            [new stdClass()],
        ];
    }

    /**
     * @dataProvider invalidReturnTypes
     * @param mixed $type
     */
    public function testSettingInvalidReturnTypeRaisesException($type)
    {
        $this->expectException(InvalidArgumentException::class);
        new ResultSet(ResultSet::TYPE_ARRAYOBJECT, $type);
    }

    public function testDataSourceIsNullByDefault()
    {
        self::assertNull($this->resultSet->getDataSource());
    }

    public function testCanProvideIteratorAsDataSource()
    {
        $it = new SplStack();
        $this->resultSet->initialize($it);
        self::assertSame($it, $this->resultSet->getDataSource());
    }

    public function testCanProvideArrayAsDataSource()
    {
        $dataSource = [['foo']];
        $this->resultSet->initialize($dataSource);
        $this->assertEquals($dataSource[0], (array) $this->resultSet->current());

        $returnType = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
        $dataSource = [$returnType];
        $this->resultSet->setArrayObjectPrototype($returnType);
        $this->resultSet->initialize($dataSource);
        $this->assertEquals($dataSource[0], $this->resultSet->current());
        $this->assertContains($dataSource[0], $this->resultSet);
    }

    public function testCanProvideIteratorAggregateAsDataSource()
    {
        $iteratorAggregate = $this->getMockBuilder('IteratorAggregate')
            ->setMethods(['getIterator'])
            ->getMock();
        $iteratorAggregate->expects($this->any())->method('getIterator')->will($this->returnValue($iteratorAggregate));
        $this->resultSet->initialize($iteratorAggregate);
        self::assertSame($iteratorAggregate->getIterator(), $this->resultSet->getDataSource());
    }

    /**
     * @dataProvider invalidReturnTypes
     * @param mixed $dataSource
     */
    public function testInvalidDataSourceRaisesException($dataSource)
    {
        if (is_array($dataSource)) {
            // this is valid
            return;
        }
        $this->expectException(InvalidArgumentException::class);
        $this->resultSet->initialize($dataSource);
    }

    public function testFieldCountIsZeroWithNoDataSourcePresent()
    {
        self::assertEquals(0, $this->resultSet->getFieldCount());
    }

    public function getArrayDataSource(int $count): ArrayIterator
    {
        $array = [];
        for ($i = 0; $i < $count; $i++) {
            $array[] = [
                'id'    => $i,
                'title' => 'title ' . $i,
            ];
        }
        return new ArrayIterator($array);
    }

    public function testFieldCountRepresentsNumberOfFieldsInARowOfData()
    {
        $resultSet  = new ResultSet(ResultSet::TYPE_ARRAY);
        $dataSource = $this->getArrayDataSource(10);
        $resultSet->initialize($dataSource);
        self::assertEquals(2, $resultSet->getFieldCount());
    }

    public function testWhenReturnTypeIsArrayThenIterationReturnsArrays()
    {
        $resultSet  = new ResultSet(ResultSet::TYPE_ARRAY);
        $dataSource = $this->getArrayDataSource(10);
        $resultSet->initialize($dataSource);
        foreach ($resultSet as $index => $row) {
            self::assertEquals($dataSource[$index], $row);
        }
    }

    public function testWhenReturnTypeIsObjectThenIterationReturnsRowObjects()
    {
        $dataSource = $this->getArrayDataSource(10);
        $this->resultSet->initialize($dataSource);
        foreach ($this->resultSet as $index => $row) {
            self::assertInstanceOf('ArrayObject', $row);
            self::assertEquals($dataSource[$index], $row->getArrayCopy());
        }
    }

    public function testCountReturnsCountOfRows()
    {
        $count      = rand(3, 75);
        $dataSource = $this->getArrayDataSource($count);
        $this->resultSet->initialize($dataSource);
        self::assertEquals($count, $this->resultSet->count());
    }

    public function testToArrayRaisesExceptionForRowsThatAreNotArraysOrArrayCastable()
    {
        $count      = rand(3, 75);
        $dataSource = $this->getArrayDataSource($count);
        foreach ($dataSource as $index => $row) {
            $dataSource[$index] = (object) $row;
        }
        $this->resultSet->initialize($dataSource);
        $this->expectException(RuntimeException::class);
        $this->resultSet->toArray();
    }

    public function testToArrayCreatesArrayOfArraysRepresentingRows()
    {
        $count      = rand(3, 75);
        $dataSource = $this->getArrayDataSource($count);
        $this->resultSet->initialize($dataSource);
        $test = $this->resultSet->toArray();
        self::assertEquals($dataSource->getArrayCopy(), $test, var_export($test, 1));
    }

    /**
     * @covers \Laminas\Db\ResultSet\AbstractResultSet::current
     * @covers \Laminas\Db\ResultSet\AbstractResultSet::buffer
     */
    public function testCurrentWithBufferingCallsDataSourceCurrentOnce()
    {
        $mockResult = $this->getMockBuilder(ResultInterface::class)->getMock();
        $mockResult->expects($this->once())->method('current')->will($this->returnValue(['foo' => 'bar']));

        $this->resultSet->initialize($mockResult);
        $this->resultSet->buffer();
        $this->resultSet->current();

        // assertion above will fail if this calls datasource current
        $this->resultSet->current();
    }

    /**
     * @covers \Laminas\Db\ResultSet\AbstractResultSet::current
     * @covers \Laminas\Db\ResultSet\AbstractResultSet::buffer
     */
    public function testBufferCalledAfterIterationThrowsException()
    {
        $this->resultSet->initialize($this->createMock(ResultInterface::class));
        $this->resultSet->current();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Buffering must be enabled before iteration is started');
        $this->resultSet->buffer();
    }

    /**
     * @covers \Laminas\Db\ResultSet\AbstractResultSet::current
     */
    public function testCurrentReturnsNullForNonExistingValues()
    {
        $mockResult = $this->createMock(ResultInterface::class);
        $mockResult->expects($this->once())->method('current')->will($this->returnValue("Not an Array"));

        $this->resultSet->initialize($mockResult);
        $this->resultSet->buffer();

        self::assertNull($this->resultSet->current());
    }
}
