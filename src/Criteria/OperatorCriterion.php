<?php


namespace Pechynho\DbWrap\Criteria;


/**
 * @author Jan Pech <pechynho@gmail.com>
 */
abstract class OperatorCriterion extends AbstractCriterion
{
	/** @var string */
	protected $operator;

	/** @var mixed */
	private $value;

	/**
	 * OperatorExpression constructor.
	 * @param string $column
	 * @param mixed $value
	 */
	public function __construct($column, $value)
	{
		parent::__construct($column);
		$this->value = $value;
	}

	/**
	 * @inheritDoc
	 */
	public function buildExpression()
	{
		$expression = "{$this->getColumnForExpression()} {$this->operator} :{$this->getParameterName()}";
		return $expression;
	}

	/**
	 * @inheritDoc
	 */
	public function getParameters()
	{
		return [$this->getParameterName() => $this->value];
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 * @return OperatorCriterion
	 */
	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}
}
