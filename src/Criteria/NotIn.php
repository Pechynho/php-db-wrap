<?php


namespace Pechynho\DbWrap\Criteria;


use Pechynho\Utility\Strings;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
class NotIn extends In
{
	/**
	 * @inheritDoc
	 */
	public function buildExpression()
	{
		$expression = parent::buildExpression();
		$expression = Strings::replace($expression, " IN ", " NOT IN ");
		return $expression;
	}
}
