<?php


namespace Pechynho\DbWrap;


use InvalidArgumentException;
use PDO;
use Pechynho\Utility\ParamsChecker;
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
		ParamsChecker::notWhiteSpaceOrNullString('$host', $host, __METHOD__);
		ParamsChecker::notWhiteSpaceOrNullString('$database', $database, __METHOD__);
		ParamsChecker::isNullOrString('$username', $username, __METHOD__);
		ParamsChecker::isNullOrString('$password', $password, __METHOD__);
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
		ParamsChecker::notWhiteSpaceOrNullString('$host', $dsn, __METHOD__);
		ParamsChecker::isNullOrString('$username', $username, __METHOD__);
		ParamsChecker::isNullOrString('$password', $password, __METHOD__);
		ParamsChecker::isNullOrArray('$options', $options, __METHOD__);
		$pdo = new PDO($dsn, $username, $password, $options);
		return new MySqlDbWrap($pdo);
	}
}
