<?php


namespace Pechynho\DbWrap\Criteria;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
interface ICriterion
{
	/**
	 * @return string
	 */
	public function buildExpression();

	/**
	 * @return array
	 */
	public function getParameters();
}
