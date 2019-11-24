<?php


namespace Pechynho\DbWrap\Criteria;

use Pechynho\Utility\Strings;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
abstract class AbstractCriterion implements ICriterion
{
	/** @var string */
	protected $table;

	/** @var string */
	protected $column;

	/**
	 * AbstractCriterion constructor.
	 * @param string $column
	 */
	public function __construct($column)
	{
		$this->column = $column;
	}

	/**
	 * @return string
	 */
	public function getColumn()
	{
		return $this->column;
	}

	/**
	 * @param string $column
	 * @return self
	 */
	public function setColumn($column)
	{
		$this->column = $column;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @param string $table
	 * @return self
	 */
	public function setTable($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @return string
	 */
	protected function getColumnForExpression()
	{
		$columnForExpression = "";
		if (!empty($this->table))
		{
			$columnForExpression .= "{$this->table}.";
		}
		$columnForExpression .= $this->column;
		return $columnForExpression;
	}

	/**
	 * @return string
	 */
	protected function getParameterName()
	{
		$parameterName = $this->column;
		if (Strings::contains($parameterName, "."))
		{
			$parameterName = Strings::replace($parameterName, ".", "_");
		}
		return $parameterName;
	}
}
