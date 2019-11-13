<?php


namespace Pechynho\DbWrap;


use InvalidArgumentException;
use PDO;
use Pechynho\Utility\Strings;

/**
 * @author Jan Pech <pechynho@gmail.com>
 */
class MySqlDbWrap extends DbWrap
{
	/**
	 * @param string      $host
	 * @param string      $database
	 * @param string|null $username
	 * @param string|null $password
	 * @return MySqlDbWrap
	 */
	public static function createFromCredentials($host, $database, $username = null, $password = null)
	{
		if (Strings::isNullOrWhiteSpace($host))
		{
			throw new InvalidArgumentException('Parameter $host cannot be NULL, empty string ("") or only white-space characters.');
		}
		if (Strings::isNullOrWhiteSpace($database))
		{
			throw new InvalidArgumentException('Parameter $host cannot be NULL, empty string ("") or only white-space characters.');
		}
		if ($username !== null && !is_string($username))
		{
			throw new InvalidArgumentException('Parameter $username has to be NULL or string.');
		}
		if ($password !== null && !is_string($password))
		{
			throw new InvalidArgumentException('Parameter $username has to be NULL or string.');
		}
		$dsn = "mysql:host=$host;port=3306;dbname=$database;charset=utf8";
		return MySqlDbWrap::createFromDsn($dsn, $username, $password, self::getPdoDefaultOptions());
	}

	/**
	 * @param string      $dsn
	 * @param string|null $username
	 * @param string|null $password
	 * @param array|null  $options
	 * @return MySqlDbWrap
	 */
	public static function createFromDsn($dsn, $username = null, $password = null, $options = null)
	{
		if (Strings::isNullOrWhiteSpace($dsn))
		{
			throw new InvalidArgumentException('Parameter $dsn cannot be NULL, empty string ("") or only white-space characters.');
		}
		if ($username !== null && !is_string($username))
		{
			throw new InvalidArgumentException('Parameter $username has to be NULL or string.');
		}
		if ($password !== null && !is_string($password))
		{
			throw new InvalidArgumentException('Parameter $username has to be NULL or string.');
		}
		if ($options !== null && !is_array($options))
		{
			throw new InvalidArgumentException('Parameter $options has to be NULL or an array.');
		}
		$pdo = new PDO($dsn, $username, $password, $options);
		return new MySqlDbWrap($pdo);
	}
}
