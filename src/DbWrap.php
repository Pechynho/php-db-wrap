<?php


namespace Pechynho\DbWrap;


use Generator;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use Pechynho\Utility\Arrays;
use Pechynho\Utility\Scalars;
use Pechynho\Utility\Strings;
use RuntimeException;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
abstract class DbWrap
{
	/** @var PDO */
	private $pdo;

	/** @var array */
	private static $pdoDefaultOptions = [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
		PDO::ATTR_EMULATE_PREPARES   => false
	];

	/**
	 * DbWrap constructor.
	 * @param PDO $pdo
	 */
	public function __construct($pdo)
	{
		if (!$pdo instanceof PDO)
		{
			throw new InvalidArgumentException(sprintf('Argument $pdo has to be instance of class %s.', PDO::class));
		}
		$this->pdo = $pdo;
	}

	/**
	 * @return PDO
	 */
	public function getPdo()
	{
		return $this->pdo;
	}

	/**
	 * @param PDO $pdo
	 * @return DbWrap
	 */
	public function setPdo($pdo)
	{
		$this->pdo = $pdo;
		return $this;
	}

	/**
	 * @return array
	 */
	public static function getPdoDefaultOptions()
	{
		return self::$pdoDefaultOptions;
	}

	/**
	 * @param array $pdoDefaultOptions
	 */
	public static function setPdoDefaultOptions($pdoDefaultOptions)
	{
		self::$pdoDefaultOptions = $pdoDefaultOptions;
	}

	/**
	 * @param string $name
	 * @param array  $arguments
	 * @return array
	 */
	public function __call($name, $arguments)
	{
		if (Strings::startsWith($name, "findOneBy"))
		{
			$method = "findOneBy";
			$by = Strings::replace($name, "findOneBy", "");
		}
		else if (Strings::startsWith($name, "findBy"))
		{
			$method = "findBy";
			$by = Strings::replace($name, "findBy", "");
		}
		else
		{
			throw new RuntimeException("Unknown method '$name' called.");
		}
		if (count($arguments) != 2)
		{
			throw new InvalidArgumentException("Wrong arguments passed to '$name'. First argument has to be a table name and the second one has to be a search criteria value - e.g. findOneById('article', 5).");
		}
		return $this->$method($arguments[0], [Strings::caseToUnderscores($by) => $arguments[1]]);
	}

	/**
	 * @param string     $table
	 * @param array      $criteria
	 * @param array|null $orderBy
	 * @return array
	 */
	public function findOneBy($table, $criteria, $orderBy = null)
	{
		if (Strings::isNullOrWhiteSpace($table))
		{
			throw new InvalidArgumentException('Parameter $table cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (!is_array($criteria))
		{
			throw new InvalidArgumentException('Parameter $criteria has to be an array.');
		}
		if (!is_array($orderBy) && $orderBy != null)
		{
			throw new InvalidArgumentException('Parameter $orderBy has to be NULL or an array.');
		}
		$query = "SELECT * FROM $table ";
		if (!empty($criteria))
		{
			$query .= "WHERE ";
			foreach ($criteria as $column => $value)
			{
				$query .= "`$column` = :$column AND ";
			}
			$query = Strings::substring($query, 0, Strings::length($query) - 4);
			$query .= " ";
		}
		if (!empty($orderBy))
		{
			$query .= "ORDER BY ";
			foreach ($orderBy as $column => $direction)
			{
				$query .= "`$column` $direction, ";
			}
			$query = Strings::substring($query, 0, Strings::length($query) - 2);
			$query .= " ";
		}
		$query .= "LIMIT 1";
		return $this->fetchFirstRow($query, $criteria);
	}

	/**
	 * @param string     $table
	 * @param array      $criteria
	 * @param array|null $orderBy
	 * @param int|null   $limit
	 * @param int|null   $offset
	 * @return array
	 */
	public function findBy($table, $criteria, $orderBy = null, $limit = null, $offset = null)
	{
		if (Strings::isNullOrWhiteSpace($table))
		{
			throw new InvalidArgumentException('Parameter $table cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (!is_array($criteria))
		{
			throw new InvalidArgumentException('Parameter $criteria has to be an array.');
		}
		if (!is_array($orderBy) && $orderBy != null)
		{
			throw new InvalidArgumentException('Parameter $orderBy has to be NULL or an array.');
		}
		if ($limit !== null && !Scalars::tryParse($limit, $limit, Scalars::INTEGER))
		{
			throw new InvalidArgumentException('Parameter $limit could not be parsed to an integer.');
		}
		if ($offset !== null && !Scalars::tryParse($offset, $offset, Scalars::INTEGER))
		{
			throw new InvalidArgumentException('Parameter $offset could not be parsed to an integer.');
		}
		$query = "SELECT * FROM $table ";
		if (!empty($criteria))
		{
			$query .= "WHERE ";
			foreach ($criteria as $column => $value)
			{
				$query .= "`$column` = :$column AND ";
			}
			$query = Strings::substring($query, 0, Strings::length($query) - 4);
			$query .= " ";
		}
		if (!empty($orderBy))
		{
			$query .= "ORDER BY ";
			foreach ($orderBy as $column => $direction)
			{
				$query .= "`$column` $direction, ";
			}
			$query = Strings::substring($query, 0, Strings::length($query) - 2);
			$query .= " ";
		}
		if ($limit !== null)
		{
			$query .= "LIMIT $limit ";
		}
		if ($offset !== null)
		{
			$query .= "OFFSET $offset";
		}
		return $this->fetchAll($query, $criteria);
	}

	/**
	 * @param string     $table
	 * @param array|null $orderBy
	 * @return array
	 */
	public function findAll($table, $orderBy = null)
	{
		if (Strings::isNullOrWhiteSpace($table))
		{
			throw new InvalidArgumentException('Parameter $table cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (!is_array($orderBy) && $orderBy != null)
		{
			throw new InvalidArgumentException('Parameter $orderBy has to be NULL or an array.');
		}
		$query = "SELECT * FROM $table ";
		if (!empty($orderBy))
		{
			$query .= "ORDER BY ";
			foreach ($orderBy as $column => $direction)
			{
				$query .= "`$column` $direction, ";
			}
			$query = Strings::substring($query, 0, Strings::length($query) - 2);
		}
		return $this->fetchAll($query);
	}

	/**
	 * @param string     $table
	 * @param array|null $criteria
	 * @return int
	 */
	public function count($table, $criteria = null)
	{
		if (Strings::isNullOrWhiteSpace($table))
		{
			throw new InvalidArgumentException('Parameter $table cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (!is_array($criteria) && $criteria != null)
		{
			throw new InvalidArgumentException('Parameter $criteria has to be NULL or an array.');
		}
		$query = "SELECT COUNT(*) FROM $table ";
		if (!empty($criteria))
		{
			$query .= "WHERE ";
			foreach ($criteria as $column => $value)
			{
				$query .= "`$column` = :$column AND ";
			}
			$query = Strings::substring($query, 0, Strings::length($query) - 4);
			$query .= " ";
		}
		return $this->fetchFirstColumn($query, $criteria);
	}

	/**
	 * @param string     $query
	 * @param array|null $parameters
	 * @return array|null
	 */
	public function fetchFirstRow($query, $parameters = null)
	{
		$statement = $this->createStatement($query, $parameters);
		$success = $statement->execute();
		if (!$success)
		{
			throw new RuntimeException(sprintf("Executing query '%s' was not successful.", $query));
		}
		$row = $statement->fetch(PDO::FETCH_ASSOC);
		return $row === false ? null : $row;
	}

	/**
	 * @param string     $query
	 * @param array|null $parameters
	 * @return mixed
	 */
	public function fetchFirstColumn($query, $parameters = null)
	{
		$row = $this->fetchFirstRow($query, $parameters);
		if ($row === null)
		{
			throw new RuntimeException("Unable to return first column. No row was found.");
		}
		return Arrays::firstValue($row);
	}

	/**
	 * @param string     $query
	 * @param array|null $parameters
	 * @return array
	 */
	public function fetchAll($query, $parameters = null)
	{
		$statement = $this->createStatement($query, $parameters);
		$success = $statement->execute();
		if (!$success)
		{
			throw new RuntimeException(sprintf("Executing query '%s' was not successful.", $query));
		}
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * @param string     $table
	 * @param array      $criteria
	 * @param array|null $orderBy
	 * @param int        $batchSize
	 * @return Generator
	 */
	public function iterate($table, $criteria, $orderBy = null, $batchSize = 500)
	{
		if (Strings::isNullOrWhiteSpace($table))
		{
			throw new InvalidArgumentException('Parameter $table cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (!is_array($criteria))
		{
			throw new InvalidArgumentException('Parameter $criteria has to be an array.');
		}
		if (!is_array($orderBy) && $orderBy != null)
		{
			throw new InvalidArgumentException('Parameter $orderBy has to be NULL or an array.');
		}
		if ($batchSize !== null && !Scalars::tryParse($batchSize, $batchSize, Scalars::INTEGER))
		{
			throw new InvalidArgumentException('Parameter $batchSize has to be NULL or an integer.');
		}
		$query = "SELECT * FROM $table ";
		if (!empty($criteria))
		{
			$query .= "WHERE ";
			foreach ($criteria as $column => $value)
			{
				$query .= "`$column` = :$column AND ";
			}
			$query = Strings::substring($query, 0, Strings::length($query) - 4);
			$query .= " ";
		}
		if (!empty($criteria))
		{
			$query .= "WHERE ";
			foreach ($criteria as $column => $value)
			{
				$query .= "`$column` = :$column AND ";
			}
			$query = Strings::substring($query, 0, Strings::length($query) - 4);
			$query .= " ";
		}
		if (!empty($orderBy))
		{
			$query .= "ORDER BY ";
			foreach ($orderBy as $column => $direction)
			{
				$query .= "`$column` $direction, ";
			}
			$query = Strings::substring($query, 0, Strings::length($query) - 2);
			$query .= " ";
		}
		return $this->iterateQuery($query, $criteria, $batchSize);
	}

	/**
	 * @param string     $query
	 * @param array|null $parameters
	 * @param int        $batchSize
	 * @return Generator
	 */
	public function iterateQuery($query, $parameters = null, $batchSize = 500)
	{
		if (Strings::isNullOrWhiteSpace($query))
		{
			throw new InvalidArgumentException('Parameter $query cannot be NULL, empty string ("") or only white-space characters.');
		}
		if ($parameters != null && !is_array($parameters))
		{
			throw new InvalidArgumentException('Parameter $parameters has to be NULL or an array.');
		}
		if ($batchSize !== null && !Scalars::tryParse($batchSize, $batchSize, Scalars::INTEGER))
		{
			throw new InvalidArgumentException('Parameter $batchSize has to be NULL or an integer.');
		}
		$index = 0;
		do
		{
			$limitedQuery = $query .= " LIMIT $batchSize OFFSET " . ($index * $batchSize);
			$index++;
			$result = $this->fetchAll($limitedQuery, $parameters);
			if (!empty($result))
			{
				foreach ($result as $row)
				{
					yield $row;
				}
			}
		} while (!empty($result));
	}

	/**
	 * @param string $table
	 * @param string $owningColumnName
	 * @param string $inverseColumnName
	 * @param int    $owningID
	 * @param int[]  $inverseIDs
	 */
	public function updateManyToMany($table, $owningColumnName, $inverseColumnName, $owningID, $inverseIDs)
	{
		if (Strings::isNullOrWhiteSpace($table))
		{
			throw new InvalidArgumentException('Parameter $table cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (Strings::isNullOrWhiteSpace($owningColumnName))
		{
			throw new InvalidArgumentException('Parameter $owningColumnName cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (Strings::isNullOrWhiteSpace($inverseColumnName))
		{
			throw new InvalidArgumentException('Parameter $inverseColumnName cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (!Scalars::tryParse($owningID, $owningID, Scalars::INTEGER))
		{
			throw new InvalidArgumentException('Parameter $owningID has to be an integer.');
		}
		if (!is_array($inverseIDs) || empty($inverseIDs))
		{
			throw new InvalidArgumentException('Parameter $inverseIDs has to be non-empty array containing integer values.');
		}
		$originalInverseIDs = Arrays::select($this->fetchAll("SELECT `$inverseColumnName` FROM $table WHERE `$owningColumnName` = $owningID"), "[$inverseColumnName]");
		$IDsToDelete = array_diff($originalInverseIDs, $inverseIDs);
		$IDsToAdd = array_merge($inverseIDs, $originalInverseIDs);
		$query = "START TRANSACTION;";
		if (!empty($IDsToDelete))
		{
			$query .= "DELETE FROM $table WHERE `$owningColumnName` = $owningID AND `$inverseColumnName` IN (" . Strings::join($IDsToDelete, ",") . ");";
		}
		if (!empty($IDsToAdd))
		{
			foreach ($IDsToAdd as $inverseID)
			{
				$query .= "INSERT INTO $table (`$owningColumnName`, `$inverseColumnName`) VALUES ($owningID, $inverseID);";
			}
		}
		$query = "COMMIT;";
		if (!empty($IDsToDelete) || !empty($IDsToAdd))
		{
			$this->executeNonQuery($query);
		}
	}

	/**
	 * @param string     $table
	 * @param array      $data
	 * @param string     $condition
	 * @param array|null $parameters
	 */
	public function update($table, $data, $condition, $parameters = null)
	{
		if (Strings::isNullOrWhiteSpace($table))
		{
			throw new InvalidArgumentException('Parameter $table cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (!is_array($data) || empty($data))
		{
			throw new InvalidArgumentException('Parameter $data has to be non empty array.');
		}
		if (Strings::isNullOrWhiteSpace($condition))
		{
			throw new InvalidArgumentException('Parameter $condition cannot be NULL, empty string ("") or only white-space characters.');
		}
		if ($parameters != null && !is_array($parameters))
		{
			throw new InvalidArgumentException('Parameter $parameters has to be NULL or an array.');
		}
		$query = "UPDATE $table SET ";
		foreach ($data as $column => $value)
		{
			$query .= "`$column` = :$column, ";
		}
		$query = Strings::substring($query, 0, Strings::length($query) - 2);
		$query .= " ";
		$query .= Strings::startsWith(Strings::toUpper($condition), "WHERE") ? $condition : "WHERE $condition";
		$this->executeNonQuery($query, $data + ($parameters === null ? [] : $parameters));
	}

	/**
	 * @param string $table
	 * @param array  $data
	 */
	public function insert($table, $data)
	{
		if (Strings::isNullOrWhiteSpace($table))
		{
			throw new InvalidArgumentException('Parameter $table cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (!is_array($data) || empty($data))
		{
			throw new InvalidArgumentException('Parameter $data has to be non empty array.');
		}
		$query = "INSERT INTO $table ";
		$columnString = "";
		$valuesString = "";
		foreach ($data as $column => $value)
		{
			$columnString .= "`$column`,";
			$valuesString .= ":$column,";
		}
		$columnString = Strings::trimEnd($columnString, [","]);
		$valuesString = Strings::trimEnd($valuesString, [","]);
		$query .= "($columnString) VALUES ($valuesString)";
		$this->executeNonQuery($query, $data);
	}

	/**
	 * @param string     $table
	 * @param string     $condition
	 * @param array|null $parameters
	 */
	public function delete($table, $condition, $parameters = null)
	{
		if (Strings::isNullOrWhiteSpace($table))
		{
			throw new InvalidArgumentException('Parameter $table cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (Strings::isNullOrWhiteSpace($condition))
		{
			throw new InvalidArgumentException('Parameter $condition cannot be NULL, empty string ("") or only white-space characters.');
		}
		if ($parameters != null && !is_array($parameters))
		{
			throw new InvalidArgumentException('Parameter $parameters has to be NULL or an array.');
		}
		$query = "DELETE FROM $table ";
		$query .= Strings::startsWith(Strings::toUpper($condition), "WHERE") ? $condition : "WHERE $condition";
		$this->executeNonQuery($query, $parameters);
	}

	/**
	 * @param string     $query
	 * @param array|null $parameters
	 * @return PDOStatement
	 */
	public function createStatement($query, $parameters = null)
	{
		if (Strings::isNullOrWhiteSpace($query))
		{
			throw new InvalidArgumentException('Parameter $query cannot be NULL, empty string ("") or only white-space characters.');
		}
		if ($parameters != null && !is_array($parameters))
		{
			throw new InvalidArgumentException('Parameter $parameters has to be NULL or array.');
		}
		$statement = $this->pdo->prepare($query);
		if (!empty($parameters))
		{
			foreach ($parameters as $name => $value)
			{
				if (!Strings::startsWith($name, ":"))
				{
					$name = ":" . $name;
				}
				$statement->bindParam($name, $value);
			}
		}
		return $statement;
	}

	/**
	 * @param string     $query
	 * @param array|null $parameters
	 */
	public function executeNonQuery($query, $parameters = null)
	{
		$success = $this->createStatement($query, $parameters)->execute();
		if (!$success)
		{
			throw new RuntimeException(sprintf("Executing query '%s' was not successful.", $query));
		}
	}
}
