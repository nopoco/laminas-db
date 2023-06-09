<?php

namespace LaminasTest\Db\Adapter\Driver\IbmDb2;

use Laminas\Db\Adapter\Driver\IbmDb2\Connection;
use Laminas\Db\Adapter\Driver\IbmDb2\IbmDb2;
use Laminas\Db\Adapter\Driver\IbmDb2\Result;

use function ini_get;
use function php_uname;

/**
 * @group integration
 * @group integration-ibm_db2
 */
class ConnectionIntegrationTest extends AbstractIntegrationTest
{
    /**
     * @covers \Laminas\Db\Adapter\Driver\IbmDb2\Connection::getCurrentSchema
     */
    public function testGetCurrentSchema()
    {
        $connection = new Connection($this->variables);
        self::assertInternalType('string', $connection->getCurrentSchema());
    }

    /**
     * @covers \Laminas\Db\Adapter\Driver\IbmDb2\Connection::setResource
     */
    public function testSetResource()
    {
        $resource   = db2_connect(
            $this->variables['database'],
            $this->variables['username'],
            $this->variables['password']
        );
        $connection = new Connection([]);
        self::assertSame($connection, $connection->setResource($resource));

        $connection->disconnect();
        unset($connection);
        unset($resource);
    }

    /**
     * @covers \Laminas\Db\Adapter\Driver\IbmDb2\Connection::getResource
     */
    public function testGetResource()
    {
        $connection = new Connection($this->variables);
        $connection->connect();
        self::assertInternalType('resource', $connection->getResource());

        $connection->disconnect();
        unset($connection);
    }

    /**
     * @covers \Laminas\Db\Adapter\Driver\IbmDb2\Connection::connect
     */
    public function testConnect()
    {
        $connection = new Connection($this->variables);
        self::assertSame($connection, $connection->connect());
        self::assertTrue($connection->isConnected());

        $connection->disconnect();
        unset($connection);
    }

    /**
     * @covers \Laminas\Db\Adapter\Driver\IbmDb2\Connection::isConnected
     */
    public function testIsConnected()
    {
        $connection = new Connection($this->variables);
        self::assertFalse($connection->isConnected());
        self::assertSame($connection, $connection->connect());
        self::assertTrue($connection->isConnected());

        $connection->disconnect();
        unset($connection);
    }

    /**
     * @covers \Laminas\Db\Adapter\Driver\IbmDb2\Connection::disconnect
     */
    public function testDisconnect()
    {
        $connection = new Connection($this->variables);
        $connection->connect();
        self::assertTrue($connection->isConnected());
        $connection->disconnect();
        self::assertFalse($connection->isConnected());
    }

    /**
     * @covers \Laminas\Db\Adapter\Driver\IbmDb2\Connection::beginTransaction
     */
    public function testBeginTransaction()
    {
        if (! $this->isTransactionEnabled()) {
            $this->markTestIncomplete(
                'I cannot test without the DB2 transactions enabled'
            );
        }
        $connection = new Connection($this->variables);
        $connection->beginTransaction();
        self::assertTrue($connection->inTransaction());
        self::assertEquals(0, db2_autocommit($connection->getResource()));
    }

    /**
     * @covers \Laminas\Db\Adapter\Driver\IbmDb2\Connection::commit
     */
    public function testCommit()
    {
        if (! $this->isTransactionEnabled()) {
            $this->markTestIncomplete(
                'I cannot test without the DB2 transactions enabled'
            );
        }
        $connection = new Connection($this->variables);
        $connection->beginTransaction();

        $oldValue = db2_autocommit($connection->getResource());
        $connection->beginTransaction();
        self::assertTrue($connection->inTransaction());
        $connection->commit();
        self::assertFalse($connection->inTransaction());
        self::assertEquals($oldValue, db2_autocommit($connection->getResource()));
    }

    /**
     * @covers \Laminas\Db\Adapter\Driver\IbmDb2\Connection::rollback
     */
    public function testRollback()
    {
        if (! $this->isTransactionEnabled()) {
            $this->markTestIncomplete(
                'I cannot test without the DB2 transactions enabled'
            );
        }
        $connection = new Connection($this->variables);
        $connection->beginTransaction();

        $oldValue = db2_autocommit($connection->getResource());
        $connection->beginTransaction();
        self::assertTrue($connection->inTransaction());
        $connection->rollback();
        self::assertFalse($connection->inTransaction());
        self::assertEquals($oldValue, db2_autocommit($connection->getResource()));
    }

    /**
     * Return true if the transaction is enabled for DB2
     *
     * @return bool
     */
    protected function isTransactionEnabled()
    {
        $os = php_uname('s') === 'OS400';
        if ($os) {
            return ini_get('ibm_db2.i5_allow_commit') === 1;
        }

        return true;
    }

    /**
     * @covers \Laminas\Db\Adapter\Driver\IbmDb2\Connection::execute
     */
    public function testExecute()
    {
        $ibmdb2     = new IbmDb2($this->variables);
        $connection = $ibmdb2->getConnection();

        $result = $connection->execute('SELECT \'foo\' FROM SYSIBM.SYSDUMMY1');
        self::assertInstanceOf(Result::class, $result);
    }

    /**
     * @covers \Laminas\Db\Adapter\Driver\IbmDb2\Connection::getLastGeneratedValue
     */
    public function testGetLastGeneratedValue()
    {
        $this->markTestIncomplete('Need to create a temporary sequence.');
        $connection = new Connection($this->variables);
        $connection->getLastGeneratedValue();
    }

    /**
     * @group laminas3469
     */
    public function testConnectReturnsConnectionWhenResourceSet()
    {
        $resource   = db2_connect(
            $this->variables['database'],
            $this->variables['username'],
            $this->variables['password']
        );
        $connection = new Connection([]);
        $connection->setResource($resource);
        self::assertSame($connection, $connection->connect());

        $connection->disconnect();
        unset($connection);
        unset($resource);
    }
}
