<?php

namespace edgardmessias\unit\db\ibm\db2;

use yii\db\Connection;
use yii\db\Transaction;

/**
 * @group ibm_db2
 */
class ConnectionTest extends \yiiunit\framework\db\ConnectionTest
{
    
    use DatabaseTestTrait;

    protected $driverName = 'ibm';
    
    public function testSerialize()
    {
        $connection = $this->getConnection(false, false);
        $connection->open();
        $serialized = serialize($connection);
        $unserialized = unserialize($serialized);
        $this->assertInstanceOf('yii\db\Connection', $unserialized);

        $this->assertEquals(123, $unserialized->createCommand("SELECT 123 from SYSIBM.SYSDUMMY1")->queryScalar());
    }

    public function testQuoteValue()
    {
        $connection = $this->getConnection(false);
        $this->assertEquals(123, $connection->quoteValue(123));
        $this->assertEquals("'string'", $connection->quoteValue('string'));
        $this->assertEquals("'It''s interesting'", $connection->quoteValue("It's interesting"));
    }

    public function testQuoteTableName()
    {
        $connection = $this->getConnection(false, false);
        $this->assertEquals('"table"', $connection->quoteTableName('table'));
        $this->assertEquals('"table"', $connection->quoteTableName('"table"'));
        $this->assertEquals('"schema"."table"', $connection->quoteTableName('schema.table'));
        $this->assertEquals('"schema"."table"', $connection->quoteTableName('schema."table"'));
        $this->assertEquals('"schema"."table"', $connection->quoteTableName('"schema"."table"'));
        $this->assertEquals('{{table}}', $connection->quoteTableName('{{table}}'));
        $this->assertEquals('(table)', $connection->quoteTableName('(table)'));
    }

    public function testQuoteColumnName()
    {
        $connection = $this->getConnection(false, false);
        $this->assertEquals('"column"', $connection->quoteColumnName('column'));
        $this->assertEquals('"column"', $connection->quoteColumnName('"column"'));
        $this->assertEquals('[[column]]', $connection->quoteColumnName('[[column]]'));
        $this->assertEquals('{{column}}', $connection->quoteColumnName('{{column}}'));
        $this->assertEquals('(column)', $connection->quoteColumnName('(column)'));

        $this->assertEquals('"column"', $connection->quoteSql('[[column]]'));
        $this->assertEquals('"column"', $connection->quoteSql('{{column}}'));
    }
    
    public function testQuoteFullColumnName()
    {
        $connection = $this->getConnection(false, false);
        $this->assertEquals('"table"."column"', $connection->quoteColumnName('table.column'));
        $this->assertEquals('"table"."column"', $connection->quoteColumnName('table."column"'));
        $this->assertEquals('"table"."column"', $connection->quoteColumnName('"table".column'));
        $this->assertEquals('"table"."column"', $connection->quoteColumnName('"table"."column"'));

        $this->assertEquals('[[table.column]]', $connection->quoteColumnName('[[table.column]]'));
        $this->assertEquals('{{table}}."column"', $connection->quoteColumnName('{{table}}.column'));
        $this->assertEquals('{{table}}."column"', $connection->quoteColumnName('{{table}}."column"'));
        $this->assertEquals('{{table}}.[[column]]', $connection->quoteColumnName('{{table}}.[[column]]'));
        $this->assertEquals('{{%table}}."column"', $connection->quoteColumnName('{{%table}}.column'));
        $this->assertEquals('{{%table}}."column"', $connection->quoteColumnName('{{%table}}."column"'));

        $this->assertEquals('"table"."column"', $connection->quoteSql('[[table.column]]'));
        $this->assertEquals('"table"."column"', $connection->quoteSql('{{table}}.[[column]]'));
        $this->assertEquals('"table"."column"', $connection->quoteSql('{{table}}."column"'));
        $this->assertEquals('"table"."column"', $connection->quoteSql('{{%table}}.[[column]]'));
        $this->assertEquals('"table"."column"', $connection->quoteSql('{{%table}}."column"'));
    }

    public function testTransaction()
    {
        $connection = $this->getConnection(false);
        $this->assertNull($connection->transaction);
        $transaction = $connection->beginTransaction();
        $this->assertNotNull($connection->transaction);
        $this->assertTrue($transaction->isActive);

        $connection->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();

        $transaction->rollBack();
        $this->assertFalse($transaction->isActive);
        $this->assertNull($connection->transaction);

        $this->assertEquals(0, $connection->createCommand('SELECT COUNT(*) FROM "profile" WHERE "description" = \'test transaction\';')->queryScalar());

        $transaction = $connection->beginTransaction();
        $connection->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();
        $transaction->commit();
        $this->assertFalse($transaction->isActive);
        $this->assertNull($connection->transaction);

        $this->assertEquals(1, $connection->createCommand('SELECT COUNT(*) FROM "profile" WHERE "description" = \'test transaction\';')->queryScalar());
    }
    
    /**
     * @expectedException \Exception
     */
    public function testTransactionShortcutException()
    {
        $connection = $this->getConnection(true);
        $connection->transaction(function () use ($connection) {
            $connection->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            throw new \Exception('Exception in transaction shortcut');
        });

        $profilesCount = $connection->createCommand('SELECT COUNT(*) FROM "profile" WHERE "description" = \'test transaction shortcut\';')->queryScalar();
        $this->assertEquals(0, $profilesCount, 'profile should not be inserted in transaction shortcut');
    }

    public function testTransactionShortcutCorrect()
    {
        $connection = $this->getConnection(true);

        $result = $connection->transaction(function () use ($connection) {
            $connection->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            return true;
        });

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $connection->createCommand('SELECT COUNT(*) FROM "profile" WHERE "description" = \'test transaction shortcut\';')->queryScalar();
        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    public function testTransactionShortcutCustom()
    {
        $connection = $this->getConnection(true);

        $result = $connection->transaction(function (Connection $db) {
            $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            return true;
        }, Transaction::READ_UNCOMMITTED);

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $connection->createCommand('SELECT COUNT(*) FROM "profile" WHERE "description" = \'test transaction shortcut\';')->queryScalar();
        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }
    public function testExceptionContainsRawQuery()
    {
        $this->markTestSkipped("IBM DB2 not support emulatePrepare");
    }
    
    public function testEnableQueryLog()
    {
        $this->markTestSkipped("Need rework");
    }
}
