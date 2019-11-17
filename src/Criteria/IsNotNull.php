<?php


namespace Pechynho\DbWrap\Criteria;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
class IsNotNull extends IsNull
{
	/**
	 * @inheritDoc
	 */
	public function buildExpression()
	{
		$expression = "{$this->getColumnForExpression()} IS NOT NULL";
		return $expression;
	}
}
