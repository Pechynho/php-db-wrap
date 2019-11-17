<?php


namespace Pechynho\DbWrap\Criteria;


use Pechynho\Utility\Strings;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
class In extends AbstractCriterion
{
	/** @var array */
	private $values;

	/** @var array */
	private $parameters = [];

	/**
	 * In constructor.
	 * @param string $column
	 * @param array $values
	 */
	public function __construct($column, $values)
	{
		parent::__construct($column);
		$this->setValues($values);
	}

	/**
	 * @inheritDoc
	 */
	public function buildExpression()
	{
		$expression = "{$this->getColumnForExpression()} IN (";
		foreach ($this->parameters as $name => $vale)
		{
			$expression .= ":$name, ";
		}
		$expression = Strings::remove($expression, Strings::length($expression) - 2) . ")";
		return  $expression;
	}

	/**
	 * @inheritDoc
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @return array
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * @param array $values
	 * @return In
	 */
	public function setValues($values)
	{
		$this->values = $values;
		$index = 1;
		foreach ($this->values as $value)
		{
			$parameterName = $this->column . "_" . $index;
			$this->parameters[$parameterName] = $value;
			$index++;
		}
		return $this;
	}
}
