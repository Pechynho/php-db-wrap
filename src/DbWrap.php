<?php


namespace Pechynho\DbWrap;


use Generator;
use InvalidArgumentException;
use LogicException;
use PDO;
use PDOStatement;
use Pechynho\DbWrap\Criteria\AbstractCriterion;
use Pechynho\DbWrap\Criteria\Equals;
use Pechynho\DbWrap\Criteria\ICriterion;
use Pechynho\DbWrap\Criteria\IsNull;
use Pechynho\DbWrap\Join\AbstractJoin;
use Pechynho\Utility\Arrays;
use Pechynho\Utility\ParamsChecker;
use Pechynho\Utility\Scalars;
use Pechynho\Utility\Strings;
use RuntimeException;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
abstract class DbWrap
{
	/** @var PDO */
	protected $pdo;

	/** @var PDOStatement|null */
	protected $lastStatement = null;

	/** @var string|null */
	protected $lastQuery = null;

	/** @var array|null */
	protected $lastParameters = null;

	/** @var array */
	protected static $pdoDefaultOptions = [
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
	public function setPdo(PDO $pdo)
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
	public static function setPdoDefaultOptions(array $pdoDefaultOptions)
	{
		self::$pdoDefaultOptions = $pdoDefaultOptions;
	}

	/**
	 * @return void
	 */
	public function beginTransaction()
	{
		if ($this->hasActiveTransaction())
		{
			throw new RuntimeException(sprintf("%s instance used by this instance of class %s is already in transaction.", PDO::class, DbWrap::class));
		}
		$this->pdo->beginTransaction();
	}

	/**
	 * @return bool
	 */
	public function hasActiveTransaction()
	{
		return $this->pdo->inTransaction();
	}

	/**
	 * @return void
	 */
	public function commitTransaction()
	{
		if (!$this->hasActiveTransaction())
		{
			throw new RuntimeException(sprintf("%s instance used by this instance of class %s is not in transaction.", PDO::class, DbWrap::class));
		}
		$this->pdo->commit();
	}

	/**
	 * @return void
	 */
	public function rollbackTransaction()
	{
		if (!$this->hasActiveTransaction())
		{
			throw new RuntimeException(sprintf("%s instance used by this instance of class %s is not in transaction.", PDO::class, DbWrap::class));
		}
		$this->pdo->rollBack();
	}

	/**
	 * @param string|null $name
	 * @return int
	 */
	public function getLastInsertedId($name = null)
	{
		ParamsChecker::isNullOrString('$name', $name, __METHOD__);
		return $this->pdo->lastInsertId($name);
	}

	/**
	 * @return int
	 */
	public function getRowsCountAffectedByLastStatement()
	{
		if ($this->lastStatement == null)
		{
			throw new RuntimeException(sprintf("No %s was yet performed by this instance of %s.", PDOStatement::class, DbWrap::class));
		}
		return $this->lastStatement->rowCount();
	}

	/**
	 * @param string              $table
	 * @param string|array        $columns
	 * @param array|null          $criteria
	 * @param AbstractJoin[]|null $joins
	 * @param array|null          $groupBy
	 * @param array|null          $orderBy
	 * @param null                $limit
	 * @param null                $offset
	 * @return array
	 */
	public function buildSelectQuery($table, $columns = "*", array $criteria = null, array $joins = null, array $groupBy = null, array $orderBy = null, $limit = null, $offset = null)
	{
		ParamsChecker::notWhiteSpaceOrNullString('$table', $table, __METHOD__);
		$query = $this->startSelectQuery($columns);
		$query = $this->appendWhiteSpaceIfNecessary($query);
		$query = $query . "FROM $table ";
		$query = $this->appendJoinsToQuery($query, $joins);
		$query = $this->appendWhiteSpaceIfNecessary($query);
		$query = $this->appendCriteriaToQuery($query, $criteria, $parameters);
		$query = $this->appendWhiteSpaceIfNecessary($query);
		$query = $this->appendGroupByToQuery($query, $groupBy);
		$query = $this->appendWhiteSpaceIfNecessary($query);
		$query = $this->appendOrderByToQuery($query, $orderBy);
		$query = $this->appendWhiteSpaceIfNecessary($query);
		$query = $this->appendLimitAndOffset($query, $limit, $offset);
		return ["query" => Strings::trim($query), "parameters" => $parameters];
	}

	/**
	 * @param string              $table
	 * @param string|array        $columns
	 * @param array|null          $criteria
	 * @param AbstractJoin[]|null $joins
	 * @param array|null          $groupBy
	 * @param array|null          $orderBy
	 * @param int|null            $limit
	 * @param int|null            $offset
	 * @return array
	 */
	public function select($table, $columns = "*", array $criteria = null, array $joins = null, array $groupBy = null, array $orderBy = null, $limit = null, $offset = null)
	{
		list($query, $parameters) = array_values($this->buildSelectQuery($table, $columns, $criteria, $joins, $groupBy, $orderBy, $limit, $offset));
		return $this->fetchAll($query, $parameters);
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
	public function findOneBy($table, array $criteria, array $orderBy = null)
	{
		list($query, $parameters) = array_values($this->buildSelectQuery($table, "*", $criteria, null, null, $orderBy, 1));
		return $this->fetchFirstRow($query, $parameters);
	}

	/**
	 * @param string     $table
	 * @param array      $criteria
	 * @param array|null $orderBy
	 * @param int|null   $limit
	 * @param int|null   $offset
	 * @return array
	 */
	public function findBy($table, array $criteria, array $orderBy = null, $limit = null, $offset = null)
	{
		list($query, $parameters) = array_values($this->buildSelectQuery($table, "*", $criteria, null, null, $orderBy, $limit, $offset));
		return $this->fetchAll($query, $parameters);
	}

	/**
	 * @param string     $table
	 * @param array|null $orderBy
	 * @return array
	 */
	public function findAll($table, array $orderBy = null)
	{
		list($query, $parameters) = array_values($this->buildSelectQuery($table, "*", null, null, null, $orderBy));
		return $this->fetchAll($query, $parameters);
	}

	/**
	 * @param string     $table
	 * @param array|null $criteria
	 * @return int
	 */
	public function count($table, array $criteria = null)
	{
		list($query, $parameters) = array_values($this->buildSelectQuery($table, ["COUNT(*)"], $criteria));
		return $this->fetchFirstColumn($query, $parameters);
	}

	/**
	 * @param string     $query
	 * @param array|null $parameters
	 * @return array|null
	 */
	public function fetchFirstRow($query, array $parameters = null)
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
	public function fetchFirstColumn($query, array $parameters = null)
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
	public function fetchAll($query, array $parameters = null)
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
	 * @param array|null $criteria
	 * @param array|null $orderBy
	 * @param int        $batchSize
	 * @return Generator
	 */
	public function iterate($table, array $criteria = null, array $orderBy = null, $batchSize = 500)
	{
		list($query, $parameters) = array_values($this->buildSelectQuery($table, "*", $criteria, null, null, $orderBy));
		return $this->iterateQuery($query, $parameters, $batchSize);
	}

	/**
	 * @param string     $query
	 * @param array|null $parameters
	 * @param int        $batchSize
	 * @return Generator
	 */
	public function iterateQuery($query, array $parameters = null, $batchSize = 500)
	{
		ParamsChecker::notWhiteSpaceOrNullString('$query', $query, __METHOD__);
		ParamsChecker::isInt('$batchSize', $batchSize, __METHOD__);
		$index = 0;
		do
		{
			$limitedQuery = $this->appendLimitAndOffset($query, $batchSize, ($index * $batchSize));
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
	 * @param mixed  $owningID
	 * @param array  $inverseIDs
	 */
	public function updateManyToMany($table, $owningColumnName, $inverseColumnName, $owningID, array $inverseIDs)
	{
		ParamsChecker::notWhiteSpaceOrNullString('$table', $table, __METHOD__);
		ParamsChecker::notWhiteSpaceOrNullString('$owningColumnName', $owningColumnName, __METHOD__);
		ParamsChecker::notWhiteSpaceOrNullString('$inverseColumnName', $inverseColumnName, __METHOD__);
		$originalInverseIDs = $this->fetchAll("SELECT $inverseColumnName FROM $table WHERE  $owningColumnName = :owningId", ["owningId" => $owningID]);
		$originalInverseIDs = Arrays::select($originalInverseIDs, "[$inverseColumnName]");
		$IDsToDelete = array_diff($originalInverseIDs, $inverseIDs);
		$IDsToAdd = array_diff($inverseIDs, $originalInverseIDs);
		$query = "";
		$parameters = ["owningId" => $owningID];
		if (!empty($IDsToDelete))
		{
			$query .= "DELETE FROM $table WHERE $owningColumnName = :owningId AND $inverseColumnName IN (";
			$i = 0;
			foreach ($IDsToDelete as $deleteID)
			{
				$query .= ":inverseId_delete_$i, ";
				$parameters["inverseId_delete_$i"] = $deleteID;
				$i++;
			}
			$query = Strings::remove($query, Strings::length($query) - 2) . ");";
		}
		if (!empty($IDsToAdd))
		{
			$i = 0;
			foreach ($IDsToAdd as $inverseID)
			{
				$query .= "INSERT INTO $table ($owningColumnName, $inverseColumnName) VALUES (:owningId, :inverseId_insert_$i);";
				$parameters["inverseId_insert_$i"] = $inverseID;
				$i++;
			}
		}
		if (!empty($IDsToDelete) || !empty($IDsToAdd))
		{
			$hasActiveTransaction = $this->hasActiveTransaction();
			if (!$hasActiveTransaction)
			{
				$this->beginTransaction();
			}
			$this->executeNonQuery($query, $parameters);
			if (!$hasActiveTransaction)
			{
				$this->commitTransaction();
			}
		}
	}

	/**
	 * @param string     $table
	 * @param array      $data
	 * @param string     $condition
	 * @param array|null $parameters
	 */
	public function update($table, array $data, $condition, array $parameters = null)
	{
		ParamsChecker::notWhiteSpaceOrNullString('$table', $table, __METHOD__);
		ParamsChecker::notEmpty('$data', $data, __METHOD__);
		ParamsChecker::notWhiteSpaceOrNullString('$condition', $condition, __METHOD__);
		$query = "UPDATE $table SET ";
		foreach ($data as $column => $value)
		{
			$query .= "$column = :$column, ";
		}
		$query = Strings::substring($query, 0, Strings::length($query) - 2);
		$query .= " ";
		$query .= Strings::startsWith(Strings::toUpper($condition), "WHERE") ? $condition : "WHERE $condition";
		$this->executeNonQuery($query, $data + ($parameters === null ? [] : $parameters));
	}

	/**
	 * @param string $table
	 * @param array  $data
	 * @param array  $duplicateKeyUpdate
	 */
	public function insert($table, array $data, array $duplicateKeyUpdate = null)
	{
		ParamsChecker::notWhiteSpaceOrNullString('$table', $table, __METHOD__);
		ParamsChecker::notEmpty('$data', $data, __METHOD__);
		$query = "INSERT INTO $table ";
		$columnString = "";
		$valuesString = "";
		foreach ($data as $column => $value)
		{
			$columnString .= "$column, ";
			$valuesString .= ":$column, ";
		}
		$columnString = Strings::remove($columnString, Strings::length($columnString) - 2);
		$valuesString = Strings::remove($valuesString, Strings::length($valuesString) - 2);
		$query .= "($columnString) VALUES ($valuesString)";
		if (!empty($duplicateKeyUpdate))
		{
			$query .= " ON DUPLICATE KEY UPDATE ";
			foreach ($duplicateKeyUpdate as $column => $value)
			{
				$query .= "$column = :{$column}_duplicate_key_update, ";
				$data[$column . "_duplicate_key_update"] = $value;
			}
			$query = Strings::remove($query, Strings::length($query) - 2);
		}
		$this->executeNonQuery($query, $data);
	}

	/**
	 * @param string     $table
	 * @param string     $condition
	 * @param array|null $parameters
	 */
	public function delete($table, $condition, array $parameters = null)
	{
		ParamsChecker::notWhiteSpaceOrNullString('$table', $table, __METHOD__);
		ParamsChecker::notWhiteSpaceOrNullString('$condition', $condition, __METHOD__);
		$query = "DELETE FROM $table ";
		$query .= Strings::startsWith(Strings::toUpper($condition), "WHERE") ? $condition : "WHERE $condition";
		$this->executeNonQuery($query, $parameters);
	}

	/**
	 * @param string     $query
	 * @param array|null $parameters
	 * @return PDOStatement
	 */
	public function createStatement($query, array $parameters = null)
	{
		ParamsChecker::notWhiteSpaceOrNullString('$query', $query, __METHOD__);
		$statement = $this->pdo->prepare($query);
		if (!empty($parameters))
		{
			foreach ($parameters as $name => $value)
			{
				if (!Strings::startsWith($name, ":"))
				{
					$name = ":" . $name;
				}
				$statement->bindValue($name, $value);
			}
		}
		$this->lastStatement = $statement;
		$this->lastQuery = $query;
		$this->lastParameters = $parameters;
		return $statement;
	}

	/**
	 * @param bool $withParameters
	 * @return string
	 */
	public function getLastQuery($withParameters = true)
	{
		ParamsChecker::isBool('$withParameters', $withParameters, __METHOD__);
		if ($this->lastQuery === null)
		{
			throw new RuntimeException("No query was yet performed.");
		}
		return $this->buildQuery($this->lastQuery, $withParameters ? $this->lastParameters : null);
	}

	/**
	 * @param string     $query
	 * @param array|null $parameters
	 * @return string|null
	 */
	protected function buildQuery($query, array $parameters = null)
	{
		$query = $this->lastQuery;
		if (!empty($parameters))
		{
			foreach ($parameters as $name => $value)
			{
				if (!Strings::startsWith($name, ":"))
				{
					$name = ":" . $name;
				}
				if (is_string($value))
				{
					$value = $this->pdo->quote($value);
				}
				else if (is_bool($value))
				{
					$value = (int)$value;
				}
				else if (is_null($value))
				{
					$value = "NULL";
				}
				$query = preg_replace("/({$name})(,|\s+|$|\(|\))/",   "{$value}$2", $query);
			}
		}
		return $query;
	}

	/**
	 * @param string     $query
	 * @param array|null $parameters
	 */
	public function executeNonQuery($query, array $parameters = null)
	{
		$success = $this->createStatement($query, $parameters)->execute();
		if (!$success)
		{
			throw new RuntimeException(sprintf("Executing query '%s' was not successful.", $query));
		}
	}

	/**
	 * @param string     $query
	 * @param array|null $groupBy
	 * @return string
	 */
	protected function appendGroupByToQuery($query, array $groupBy = null)
	{
		if (!empty($groupBy))
		{
			$query = $this->appendWhiteSpaceIfNecessary($query);
			$query .= "GROUP BY ";
			foreach ($groupBy as $column)
			{
				$query .= "$column, ";
			}
			$query = Strings::remove($query, Strings::length($query) - 2);
			$query .= " ";
		}
		return $query;
	}

	/**
	 * @param string $columns
	 * @return string
	 */
	protected function startSelectQuery($columns = "*")
	{
		if ((!is_string($columns) && !is_array($columns)) || (is_array($columns) && empty($columns)) || (is_string($columns) && $columns != "*"))
		{
			throw new InvalidArgumentException('Parameter $columns has to be "*" as string value or non-empty array.');
		}
		if ($columns == "*")
		{
			return "SELECT * ";
		}
		$query = "SELECT ";
		foreach ($columns as $column)
		{
			$query .= "$column, ";
		}
		$query = Strings::remove($query, Strings::length($query) - 2);
		$query .= " ";
		return $query;
	}

	/**
	 * @param string     $query
	 * @param array|null $criteria
	 * @param array|null $parameters
	 * @return string
	 */
	protected function appendCriteriaToQuery($query, $criteria, &$parameters)
	{
		if (!empty($criteria))
		{
			list($condition, $parameters) = array_values($this->buildCondition($criteria));
			$query = $this->appendWhiteSpaceIfNecessary($query);
			$query .= "WHERE ";
			$query .= $condition;
			$query .= " ";
		}
		if (empty($parameters))
		{
			$parameters = null;
		}
		return $query;
	}

	/**
	 * @param array       $criteria
	 * @param string|null $tableOrAlias
	 * @return array
	 */
	public function buildCondition(array $criteria, $tableOrAlias = null)
	{
		ParamsChecker::isNullOrString('$tableOrAlias', $tableOrAlias, __METHOD__);
		$parameters = [];
		$register = [];
		$condition = $this->processCriteria($criteria, $parameters, $register, $tableOrAlias);
		return ["condition" => $condition, "parameters" => $parameters];
	}

	/**
	 * @param array       $criteria
	 * @param array       $parameters
	 * @param array       $register
	 * @param string|null $tableOrAlias
	 * @return string
	 */
	protected function processCriteria($criteria, &$parameters, &$register, $tableOrAlias = null)
	{
		$output = "";
		foreach ($criteria as $columnOrIndex => $item)
		{
			if (is_int($columnOrIndex) && is_array($item))
			{
				$output .= "(" . $this->processCriteria($item, $parameters, $register, $tableOrAlias) . ") AND ";
				continue;
			}
			if (is_int($columnOrIndex) && in_array($item, ["AND", "OR", "XOR"]))
			{
				$output = $this->trimConjunctions($output);
				$output = $this->appendWhiteSpaceIfNecessary($output);
				$output .= $item . " ";
				continue;
			}
			if (is_int($columnOrIndex) && $item == "NOT")
			{
				$output .= "NOT ";
				continue;
			}
			if (is_string($columnOrIndex) && $item !== null)
			{
				$item = new Equals($columnOrIndex, $item);
			}
			else if (is_string($columnOrIndex) && $item === null)
			{
				$item = new IsNull($columnOrIndex);
			}
			if (!$item instanceof ICriterion)
			{
				throw new RuntimeException('Unknown value passed to parameter $criteria.');
			}
			if ($item instanceof AbstractCriterion && $tableOrAlias != null)
			{
				$item->setTable($tableOrAlias);
			}
			$expression = $item->buildExpression();
			$criteriaParameters = $item->getParameters();
			foreach ($criteriaParameters as $parameterName => $value)
			{
				if (!isset($register[$parameterName]))
				{
					$register[$parameterName] = 0;
				}
				$register[$parameterName] = $register[$parameterName] + 1;
				if ($register[$parameterName] > 1)
				{
					$oldParameterName = $parameterName;
					$parameterName = $parameterName . "_" . ($register[$parameterName] - 1);
					$expression = preg_replace("/(:{$oldParameterName})(,|\s+|$|\(|\))/", ":{$parameterName}$2", $expression);
				}
				$parameters[$parameterName] = $value;
			}
			$output .= "$expression AND ";
		}
		if (!empty($output))
		{
			$output = $this->trimConjunctions($output);
		}
		return $output;
	}

	/**
	 * @param string $subject
	 * @return string
	 */
	protected function trimConjunctions($subject)
	{
		$methods = ["startsWith", "endsWith"];
		$conjunctions = ["AND", "OR", "XOR"];
		while (true)
		{
			$trimPerformed = false;
			$subject = Strings::trim($subject);
			foreach ($conjunctions as $conjunction)
			{
				foreach ($methods as $method)
				{
					if (Strings::$method($subject, $conjunction))
					{
						$trimPerformed = true;
						$subject = $method === "startsWith" ? Strings::substring($subject, Strings::length($conjunction)) : Strings::substring($subject, 0, Strings::length($subject) - Strings::length($conjunction));
					}
				}
			}
			if (!$trimPerformed)
			{
				break;
			}
		}
		return Strings::trim($subject);
	}

	/**
	 * @param string     $query
	 * @param array|null $orderBy
	 * @return string
	 */
	protected function appendOrderByToQuery($query, array $orderBy = null)
	{
		if (!empty($orderBy))
		{
			$query = $this->appendWhiteSpaceIfNecessary($query);
			$query .= "ORDER BY ";
			foreach ($orderBy as $column => $direction)
			{
				$query .= "$column $direction, ";
			}
			$query = Strings::substring($query, 0, Strings::length($query) - 2);
			$query .= " ";
		}
		return $query;
	}

	/**
	 * @param string              $query
	 * @param AbstractJoin[]|null $joins
	 * @return string
	 */
	protected function appendJoinsToQuery($query, array $joins = null)
	{
		if (!empty($joins))
		{
			$query = $this->appendWhiteSpaceIfNecessary($query);
			foreach ($joins as $join)
			{
				if (!$join instanceof AbstractJoin)
				{
					throw new InvalidArgumentException(sprintf("Expected instance of %s.", AbstractJoin::class));
				}
				$query .= $join->buildQueryPart() . " ";
			}
		}
		return $query;
	}

	/**
	 * @param string   $query
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return string
	 */
	protected function appendLimitAndOffset($query, $limit = null, $offset = null)
	{
		if ($limit !== null && !Scalars::tryParse($limit, $limit, Scalars::INTEGER))
		{
			throw new InvalidArgumentException('Parameter $limit could not be parsed to an integer.');
		}
		if ($offset !== null && !Scalars::tryParse($offset, $offset, Scalars::INTEGER))
		{
			throw new InvalidArgumentException('Parameter $offset could not be parsed to an integer.');
		}
		if ($limit !== null)
		{
			$query = $this->appendWhiteSpaceIfNecessary($query);
			$query .= "LIMIT $limit ";
		}
		if ($offset !== null)
		{
			$query = $this->appendWhiteSpaceIfNecessary($query);
			$query .= "OFFSET $offset ";
		}
		return $query;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	protected function appendWhiteSpaceIfNecessary($query)
	{
		if (!Strings::endsWith($query, " "))
		{
			$query .= " ";
		}
		return $query;
	}
}
