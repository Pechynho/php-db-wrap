<?php


namespace Pechynho\DbWrap\Criteria;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
class Between extends AbstractCriterion
{
	/** @var int */
	private $lowerLimit;

	/** @var int */
	private $upperLimit;

	/**
	 * Between constructor.
	 * @param string $column
	 * @param int    $lowerLimit
	 * @param int    $upperLimit
	 */
	public function __construct($column, $lowerLimit, $upperLimit)
	{
		parent::__construct($column);
		$this->lowerLimit = $lowerLimit;
		$this->upperLimit = $upperLimit;
	}

	/**
	 * @inheritDoc
	 */
	public function buildExpression()
	{
		$expression = "({$this->getColumnForExpression()} BETWEEN :{$this->getParameterName()}_lower_limit AND :{$this->getParameterName()}_upper_limit)";
		return $expression;
	}

	/**
	 * @inheritDoc
	 */
	public function getParameters()
	{
		return [
			$this->getParameterName() . "_lower_limit" => $this->lowerLimit,
			$this->getParameterName() . "_upper_limit" => $this->upperLimit
		];
	}

	/**
	 * @return int
	 */
	public function getLowerLimit()
	{
		return $this->lowerLimit;
	}

	/**
	 * @param int $lowerLimit
	 * @return Between
	 */
	public function setLowerLimit($lowerLimit)
	{
		$this->lowerLimit = $lowerLimit;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getUpperLimit()
	{
		return $this->upperLimit;
	}

	/**
	 * @param int $upperLimit
	 * @return Between
	 */
	public function setUpperLimit($upperLimit)
	{
		$this->upperLimit = $upperLimit;
		return $this;
	}
}
