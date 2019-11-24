<?php


namespace Pechynho\DbWrap\Criteria;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
class NotLike extends Like
{
	/**
	 * Like constructor.
	 * @param string $column
	 * @param mixed $value
	 * @param string $mode
	 */
	public function __construct($column, $value, $mode = NotLike::CONTAINS)
	{
		parent::__construct($column, $value, $mode);
	}

	/**
	 * @inheritDoc
	 */
	public function buildExpression()
	{
		$expression = "{$this->getColumnForExpression()} NOT LIKE :{$this->getParameterName()}";
		return $expression;
	}
}
