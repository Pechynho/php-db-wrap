<?php


namespace Pechynho\DbWrap\Join;


abstract class AbstractJoin
{
	/** @var string */
	protected $table;

	/** @var string */
	protected $type;

	/** @var string */
	protected $onClause;

	/**
	 * Join constructor.
	 * @param string $table
	 * @param string $type
	 * @param string $onClause
	 */
	public function __construct($table, $type, $onClause)
	{
		$this->table = $table;
		$this->type = $type;
		$this->onClause = $onClause;
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
	 * @return AbstractJoin
	 */
	public function setTable($table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getOnClause()
	{
		return $this->onClause;
	}

	/**
	 * @param string $onClause
	 * @return AbstractJoin
	 */
	public function setOnClause($onClause)
	{
		$this->onClause = $onClause;
		return $this;
	}

	/**
	 * @return string
	 */
	public function buildQueryPart()
	{
		return "{$this->type} JOIN {$this->table} ON {$this->onClause}";
	}
}
