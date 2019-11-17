<?php


namespace Pechynho\DbWrap\Criteria;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
class Expression implements ICriterion
{
	/** @var string */
	private $expression;

	/** @var array */
	private $parameters;

	/**
	 * Expression constructor.
	 * @param string $expression
	 * @param array  $parameters
	 */
	public function __construct($expression, $parameters)
	{
		$this->expression = $expression;
		$this->parameters = $parameters;
	}

	/**
	 * @return string
	 */
	public function getExpression()
	{
		return $this->expression;
	}

	/**
	 * @param string $expression
	 * @return Expression
	 */
	public function setExpression($expression)
	{
		$this->expression = $expression;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function buildExpression()
	{
		return $this->expression;
	}

	/**
	 * @inheritDoc
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param array $parameters
	 * @return Expression
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
		return $this;
	}
}
