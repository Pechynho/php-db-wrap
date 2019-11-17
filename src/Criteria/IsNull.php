<?php


namespace Pechynho\DbWrap\Criteria;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
class IsNull extends AbstractCriterion
{
	/**
	 * @inheritDoc
	 */
	public function buildExpression()
	{
		$expression = "{$this->getColumnForExpression()} IS NULL";
		return $expression;
	}

	/**
	 * @inheritDoc
	 */
	public function getParameters()
	{
		return [];
	}
}
