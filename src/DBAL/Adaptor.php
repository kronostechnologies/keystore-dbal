<?php

namespace Kronos\Keystore\Repository\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Kronos\Keystore\Exception\KeyNotFoundException;
use Kronos\Keystore\Repository\RepositoryInterface;
use PDO;

class Adaptor implements RepositoryInterface {
	const VALUE_FIELD_ALIAS = 'value_field';

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var string
	 */
	private $keyField;

	/**
	 * @var string
	 */
	private $valueField;

	/**
	 * Adaptor constructor.
	 * @param Connection $connection
	 * @param string $tableName
	 * @param string $keyField
	 * @param string $valueField
	 */
	public function __construct(Connection $connection, $tableName, $keyField, $valueField) {
		$this->connection = $connection;

		$this->tableName = $this->connection->quoteIdentifier($tableName);
		$this->keyField = $this->connection->quoteIdentifier($keyField);
		$this->valueField = $this->connection->quoteIdentifier($valueField);
	}

    	/**
     	 * @param string $key
     	 * @param mixed $value
     	 * @throws DBALException
     	 */
	public function set($key, $value): void {
		$query = 'INSERT INTO ' . $this->tableName . ' (' . $this->keyField . ', ' . $this->valueField . ') ' .
			'VALUES (?,?) ' .
			'ON DUPLICATE KEY UPDATE ' . $this->valueField . ' = VALUES(' . $this->valueField . ')';

		$this->connection->executeUpdate($query, [$key, $value]);
	}

	/**
	 * @param string $key
	 * @return mixed
	 * @throws KeyNotFoundException
	 */
	public function get($key) {
		$query = 'SELECT ' . $this->valueField . ' AS ' . self::VALUE_FIELD_ALIAS . ' FROM ' . $this->tableName . ' WHERE ' . $this->keyField . ' = ?;';

		$result = $this->connection->executeQuery($query, [$key]);
		if($result->rowCount()) {
			$row = $result->fetch(PDO::FETCH_ASSOC);
			$result->closeCursor();

            		if (isset($row[self::VALUE_FIELD_ALIAS])) {
                		return $row[self::VALUE_FIELD_ALIAS];
            		}

			return null;
		}
		else {
			$result->closeCursor();

			throw new KeyNotFoundException('Key '.$key.' does not exists');
		}
	}

    	/**
     	 * @param string $key
     	 * @throws KeyNotFoundException
     	 * @throws DBALException
     	 */
	public function delete($key): void {
		$query = 'DELETE FROM '.$this->tableName.' WHERE '.$this->keyField.' = ?;';

		$affectedRows = $this->connection->executeUpdate($query, [$key]);

		if(!$affectedRows) {
			throw new KeyNotFoundException('No rows where deleted');
		}
	}

}
