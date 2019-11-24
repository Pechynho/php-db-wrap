<?php


namespace Pechynho\DbWrap\Criteria;


/**
 * @author Jan Pech <pechynho@gmail.com>
 */
class Like extends AbstractCriterion
{
	/** @var string */
	const STARTS_WITH = "STARTS_WITH";

	/** @var string */
	const ENDS_WITH = "ENDS_WITH";

	/** @var string */
	const CONTAINS = "CONTAINS";

	/** @var mixed */
	protected $value;

	/** @var string */
	protected $mode;

	/**
	 * Like constructor.
	 * @param string $column
	 * @param mixed $value
	 * @param string $mode
	 */
	public function __construct($column, $value, $mode = Like::CONTAINS)
	{
		parent::__construct($column);
		$this->value = $value;
		$this->mode = $mode;
	}

	/**
	 * @inheritDoc
	 */
	public function buildExpression()
	{
		$expression = "{$this->getColumnForExpression()} LIKE :{$this->getParameterName()}";
		return $expression;
	}

	/**
	 * @inheritDoc
	 */
	public function getParameters()
	{
		$value = $this->mode == Like::CONTAINS ? "%" . $this->value . "%" : $this->value;
		$value = $this->mode == Like::ENDS_WITH ? "%" . $value : $value;
		$value = $this->mode == Like::STARTS_WITH ? $value . "%" : $value;
		return [$this->getParameterName() => $value];
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
	 * @return Like
	 */
	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * @param string $mode
	 * @return Like
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;
		return $this;
	}
}
