<?php

namespace LaminasTest\Db\Adapter\Platform;

use Laminas\Db\Adapter\Platform\Sql92;
use PHPUnit\Framework\TestCase;

class Sql92Test extends TestCase
{
    /** @var Sql92 */
    protected $platform;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->platform = new Sql92();
    }

    /**
     * @covers \Laminas\Db\Adapter\Platform\Sql92::getName
     */
    public function testGetName()
    {
        self::assertEquals('SQL92', $this->platform->getName());
    }

    /**
     * @covers \Laminas\Db\Adapter\Platform\Sql92::getQuoteIdentifierSymbol
     */
    public function testGetQuoteIdentifierSymbol()
    {
        self::assertEquals('"', $this->platform->getQuoteIdentifierSymbol());
    }

    /**
     * @covers \Laminas\Db\Adapter\Platform\Sql92::quoteIdentifier
     */
    public function testQuoteIdentifier()
    {
        self::assertEquals('"identifier"', $this->platform->quoteIdentifier('identifier'));
    }

    /**
     * @covers \Laminas\Db\Adapter\Platform\Sql92::quoteIdentifierChain
     */
    public function testQuoteIdentifierChain()
    {
        self::assertEquals('"identifier"', $this->platform->quoteIdentifierChain('identifier'));
        self::assertEquals('"identifier"', $this->platform->quoteIdentifierChain(['identifier']));
        self::assertEquals('"schema"."identifier"', $this->platform->quoteIdentifierChain(['schema', 'identifier']));
    }

    /**
     * @covers \Laminas\Db\Adapter\Platform\Sql92::getQuoteValueSymbol
     */
    public function testGetQuoteValueSymbol()
    {
        self::assertEquals("'", $this->platform->getQuoteValueSymbol());
    }

    /**
     * @covers \Laminas\Db\Adapter\Platform\Sql92::quoteValue
     */
    public function testQuoteValueRaisesNoticeWithoutPlatformSupport()
    {
        $this->expectNotice();
        $this->expectNoticeMessage(
            'Attempting to quote a value without specific driver level support can introduce security vulnerabilities '
            . 'in a production environment.'
        );
        $this->platform->quoteValue('value');
    }

    /**
     * @covers \Laminas\Db\Adapter\Platform\Sql92::quoteValue
     */
    public function testQuoteValue()
    {
        self::assertEquals("'value'", @$this->platform->quoteValue('value'));
        self::assertEquals("'Foo O\\'Bar'", @$this->platform->quoteValue("Foo O'Bar"));
        self::assertEquals(
            '\'\\\'; DELETE FROM some_table; -- \'',
            @$this->platform->quoteValue('\'; DELETE FROM some_table; -- ')
        );
        self::assertEquals(
            "'\\\\\\'; DELETE FROM some_table; -- '",
            @$this->platform->quoteValue('\\\'; DELETE FROM some_table; -- ')
        );
    }

    /**
     * @covers \Laminas\Db\Adapter\Platform\Sql92::quoteTrustedValue
     */
    public function testQuoteTrustedValue()
    {
        self::assertEquals("'value'", $this->platform->quoteTrustedValue('value'));
        self::assertEquals("'Foo O\\'Bar'", $this->platform->quoteTrustedValue("Foo O'Bar"));
        self::assertEquals(
            '\'\\\'; DELETE FROM some_table; -- \'',
            $this->platform->quoteTrustedValue('\'; DELETE FROM some_table; -- ')
        );

        //                   '\\\'; DELETE FROM some_table; -- '  <- actual below
        self::assertEquals(
            "'\\\\\\'; DELETE FROM some_table; -- '",
            $this->platform->quoteTrustedValue('\\\'; DELETE FROM some_table; -- ')
        );
    }

    /**
     * @covers \Laminas\Db\Adapter\Platform\Sql92::quoteValueList
     */
    public function testQuoteValueList()
    {
        $this->expectError();
        $this->expectErrorMessage(
            'Attempting to quote a value without specific driver level support can introduce security vulnerabilities '
            . 'in a production environment.'
        );
        self::assertEquals("'Foo O\\'Bar'", $this->platform->quoteValueList("Foo O'Bar"));
    }

    /**
     * @covers \Laminas\Db\Adapter\Platform\Sql92::getIdentifierSeparator
     */
    public function testGetIdentifierSeparator()
    {
        self::assertEquals('.', $this->platform->getIdentifierSeparator());
    }

    /**
     * @covers \Laminas\Db\Adapter\Platform\Sql92::quoteIdentifierInFragment
     */
    public function testQuoteIdentifierInFragment()
    {
        self::assertEquals('"foo"."bar"', $this->platform->quoteIdentifierInFragment('foo.bar'));
        self::assertEquals('"foo" as "bar"', $this->platform->quoteIdentifierInFragment('foo as bar'));

        // single char words
        self::assertEquals(
            '("foo"."bar" = "boo"."baz")',
            $this->platform->quoteIdentifierInFragment('(foo.bar = boo.baz)', ['(', ')', '='])
        );

        // case insensitive safe words
        self::assertEquals(
            '("foo"."bar" = "boo"."baz") AND ("foo"."baz" = "boo"."baz")',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and']
            )
        );

        // case insensitive safe words in field
        self::assertEquals(
            '("foo"."bar" = "boo".baz) AND ("foo".baz = "boo".baz)',
            $this->platform->quoteIdentifierInFragment(
                '(foo.bar = boo.baz) AND (foo.baz = boo.baz)',
                ['(', ')', '=', 'and', 'bAz']
            )
        );
    }
}
