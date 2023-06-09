<?php

namespace LaminasTest\Db\Sql\Predicate;

use Laminas\Db\Sql\Predicate\In;
use Laminas\Db\Sql\Select;
use PHPUnit\Framework\TestCase;

class InTest extends TestCase
{
    public function testEmptyConstructorYieldsNullIdentifierAndValueSet()
    {
        $in = new In();
        self::assertNull($in->getIdentifier());
        self::assertNull($in->getValueSet());
    }

    public function testCanPassIdentifierAndValueSetToConstructor()
    {
        $in = new In('foo.bar', [1, 2]);
        self::assertEquals('foo.bar', $in->getIdentifier());
        self::assertEquals([1, 2], $in->getValueSet());
    }

    public function testCanPassIdentifierAndEmptyValueSetToConstructor()
    {
        $in = new In('foo.bar', []);
        $this->assertEquals('foo.bar', $in->getIdentifier());
        $this->assertEquals([], $in->getValueSet());
    }

    public function testIdentifierIsMutable()
    {
        $in = new In();
        $in->setIdentifier('foo.bar');
        self::assertEquals('foo.bar', $in->getIdentifier());
    }

    public function testValueSetIsMutable()
    {
        $in = new In();
        $in->setValueSet([1, 2]);
        self::assertEquals([1, 2], $in->getValueSet());
    }

    public function testRetrievingWherePartsReturnsSpecificationArrayOfIdentifierAndValuesAndArrayOfTypes()
    {
        $in = new In();
        $in->setIdentifier('foo.bar')
            ->setValueSet([1, 2, 3]);
        $expected = [
            [
                '%s IN (%s, %s, %s)',
                ['foo.bar', 1, 2, 3],
                [In::TYPE_IDENTIFIER, In::TYPE_VALUE, In::TYPE_VALUE, In::TYPE_VALUE],
            ],
        ];
        self::assertEquals($expected, $in->getExpressionData());

        $in->setIdentifier('foo.bar')
            ->setValueSet([
                [1 => In::TYPE_LITERAL],
                [2 => In::TYPE_VALUE],
                [3 => In::TYPE_LITERAL],
            ]);
        $expected = [
            [
                '%s IN (%s, %s, %s)',
                ['foo.bar', 1, 2, 3],
                [In::TYPE_IDENTIFIER, In::TYPE_LITERAL, In::TYPE_VALUE, In::TYPE_LITERAL],
            ],
        ];
        $qqq      = $in->getExpressionData();
        self::assertEquals($expected, $in->getExpressionData());
    }

    public function testGetExpressionDataWithSubselect()
    {
        $select   = new Select();
        $in       = new In('foo', $select);
        $expected = [
            [
                '%s IN %s',
                ['foo', $select],
                [$in::TYPE_IDENTIFIER, $in::TYPE_VALUE],
            ],
        ];
        self::assertEquals($expected, $in->getExpressionData());
    }

    public function testGetExpressionDataWithEmptyValues()
    {
        $select   = new Select();
        $in       = new In('foo', []);
        $expected = [
            [
                '%s IN (NULL)',
                ['foo'],
                [$in::TYPE_IDENTIFIER],
            ],
        ];
        $this->assertEquals($expected, $in->getExpressionData());
    }

    public function testGetExpressionDataWithSubselectAndIdentifier()
    {
        $select   = new Select();
        $in       = new In('foo', $select);
        $expected = [
            [
                '%s IN %s',
                ['foo', $select],
                [$in::TYPE_IDENTIFIER, $in::TYPE_VALUE],
            ],
        ];
        self::assertEquals($expected, $in->getExpressionData());
    }

    public function testGetExpressionDataWithSubselectAndArrayIdentifier()
    {
        $select   = new Select();
        $in       = new In(['foo', 'bar'], $select);
        $expected = [
            [
                '(%s, %s) IN %s',
                ['foo', 'bar', $select],
                [$in::TYPE_IDENTIFIER, $in::TYPE_IDENTIFIER, $in::TYPE_VALUE],
            ],
        ];
        self::assertEquals($expected, $in->getExpressionData());
    }
}
