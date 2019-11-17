<?php


namespace Pechynho\DbWrap\Utility;


use DateTime;
use InvalidArgumentException;
use LogicException;
use Pechynho\Utility\Dates;
use Pechynho\Utility\ParamsChecker;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
class DbHelper
{
	/**
	 * @param string|null  $value
	 * @param boolean $canBeNull
	 * @return DateTime|null
	 */
	public static function createDateTime($value, $canBeNull = false)
	{
		ParamsChecker::isBool('$canBeNull', $canBeNull, __METHOD__);
		if ($value === null)
		{
			if ($canBeNull)
			{
				return null;
			}
			throw new InvalidArgumentException(sprintf('Parameter $value is NULL while it cannot be NULL (determined by parameter $canBeNull).'));
		}
		else if (preg_match("/^[\d]{4}-[\d]{2}-[\d]{2}$/", $value))
		{
			$dateTime = DateTime::createFromFormat("Y-m-d", $value);
			$dateTime->setTime(0, 0);
		}
		else if (preg_match("/^[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2}$/", $value))
		{
			$dateTime = DateTime::createFromFormat("Y-m-d H:i:s", $value);
		}
		else if (preg_match("/^[\d]+$/", $value))
		{
			$dateTime = Dates::fromTimestamp($value);
		}
		else
		{
			throw new LogicException("Not implemented.");
		}
		return $dateTime;
	}
}
