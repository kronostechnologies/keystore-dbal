<?php

namespace Kronos\Tests\Keystore\Repository\DBAL;

use Doctrine\DBAL\Connection;
use Kronos\Keystore\Exception\KeyNotFoundException;
use Kronos\Keystore\Repository\DBAL\Adaptor;

class AdaptorTest extends \PHPUnit_Framework_TestCase {
	const TABLE_NAME = 'table';
	const KEY_FIELD = 'keyField';
	const VALUE_FIELD = 'valueField';

	const QUOTED_TABLE_NAME = 'quotedTable';
	const QUOTED_KEY_FIELD = 'quotedKeyField';
	const QUOTED_VALUE_FIELD = 'quotedValueFiled';
	const KEY = 'key';
	const VALUE = 'value';

	/**
	 * @var Adaptor
	 */
	private $adaptor;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $connection;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $result;

	public function setUp() {
		$this->connection = $this->createMock(Connection::class);
	}

	public function test_constuctor_ShouldQuoteTableAndFields() {
		$this->connection
			->expects(self::exactly(3))
			->method('quoteIdentifier')
			->withConsecutive(
				[self::TABLE_NAME],
				[self::KEY_FIELD],
				[self::VALUE_FIELD]
			);

		$this->adaptor = new Adaptor($this->connection, self::TABLE_NAME, self::KEY_FIELD, self::VALUE_FIELD);
	}

	public function test_ConfiguredAdaptor_set_ShouldExecuteUpdate() {
		$this->givenConfiguredAdaptor();
		$this->connection
			->expects(self::once())
			->method('executeUpdate')
			->with(
				'INSERT INTO '.self::QUOTED_TABLE_NAME.' ('.self::QUOTED_KEY_FIELD.', '.self::QUOTED_VALUE_FIELD.') '.
				'VALUES (?,?) '.
				'ON DUPLICATE KEY UPDATE '.self::QUOTED_VALUE_FIELD.' = VALUES('.self::QUOTED_VALUE_FIELD.')',
				[
					self::KEY,
					self::VALUE
				]
			);

		$this->adaptor->set(self::KEY, self::VALUE);
	}

	public function test_ConfiguredAdaptor_get_ShouldExecuteQuery() {
		$this->givenConfiguredAdaptor();
		$this->givenRowReturned();
		$this->connection
			->expects(self::once())
			->method('executeQuery')
			->with(
				'SELECT '.self::QUOTED_VALUE_FIELD.' AS value_field FROM '.self::QUOTED_TABLE_NAME.' WHERE '.self::QUOTED_KEY_FIELD.' = ?;',
				[self::KEY]
			);

		$this->adaptor->get(self::KEY);
	}

	public function test_QueryReturnResult_get_ShouldGetRowCount() {
		$this->givenConfiguredAdaptor();
		$this->givenRowReturned();
		$this->result
			->expects(self::once())
			->method('rowCount');

		$this->adaptor->get(self::KEY);
	}

	public function test_RowReturned_get_ShouldFetchRow() {
		$this->givenConfiguredAdaptor();
		$this->givenRowReturned();
		$this->result
			->expects(self::once())
			->method('fetch')
			->with(\PDO::FETCH_ASSOC);

		$this->adaptor->get(self::KEY);
	}

	public function test_RowFetched_get_ShouldCloseResultCursor() {
		$this->givenConfiguredAdaptor();
		$this->givenRowReturned();
		$this->result
			->expects(self::once())
			->method('closeCursor');

		$this->adaptor->get(self::KEY);
	}

	public function test_RowFetched_get_ShouldReturnValue() {
		$this->givenConfiguredAdaptor();
		$this->givenRowReturned();
		$this->result
			->method('fetch')
			->willReturn([Adaptor::VALUE_FIELD_ALIAS => self::VALUE]);

		$actualValue = $this->adaptor->get(self::KEY);

		$this->assertSame(self::VALUE, $actualValue);
	}

	public function test_ZeroRowReturned_get_ShouldCloseResultCursorAndThrowKeyNotFoundException() {
		$this->givenConfiguredAdaptor();
		$this->result
			->method('rowCount')
			->willReturn(0);
		$this->result
			->expects(self::once())
			->method('closeCursor');
		$this->expectException(KeyNotFoundException::class);

		$this->adaptor->get(self::KEY);
	}

	public function test_ConfiguredAdaptor_delete_ShouldExecuteUpdate() {
		$this->givenConfiguredAdaptor();
		$this->connection
			->expects(self::once())
			->method('executeUpdate')
			->with(
				'DELETE FROM '.self::QUOTED_TABLE_NAME.' WHERE '.self::QUOTED_KEY_FIELD.' = ?;',
				[self::KEY]
			)
			->willReturn(1);

		$this->adaptor->delete(self::KEY);
	}

	public function test_NoRowAffected_delete_ShouldThrowKeyNotFoundException() {
		$this->givenConfiguredAdaptor();
		$this->connection
			->method('executeUpdate')
			->willReturn(0);
		$this->expectException(KeyNotFoundException::class);

		$this->adaptor->delete(self::KEY);
	}

	private function givenConfiguredAdaptor() {
		$this->connection
			->method('quoteIdentifier')
			->will($this->onConsecutiveCalls(
				self::QUOTED_TABLE_NAME,
				self::QUOTED_KEY_FIELD,
				self::QUOTED_VALUE_FIELD
			));
		$this->result = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$this->connection
			->method('executeQuery')
			->willReturn($this->result);

		$this->adaptor = new Adaptor($this->connection, self::TABLE_NAME, self::KEY_FIELD, self::VALUE_FIELD);
	}

	private function givenRowReturned() {
		$this->result
			->method('rowCount')
			->willReturn(1);

		$this->result->method('fetch')->willReturn([Adaptor::VALUE_FIELD_ALIAS => 'value']);
	}

}
