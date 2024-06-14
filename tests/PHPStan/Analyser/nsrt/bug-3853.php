<?php

namespace Bug3853;

use function PHPStan\Testing\assertType;

class Connection {}
class SubConnection extends Connection {}

class DriverManager
{
	/**
	 * @template T of Connection
	 * @param array{wrapperClass?: class-string<T>} $params
	 * @return ($params is array{wrapperClass:mixed} ? T : Connection)
	 */
	public static function getConnection(array $params): Connection {
		return new Connection();
	}

	public static function test(): void {
		assertType('Bug3853\Connection', DriverManager::getConnection([]));
		assertType('Bug3853\SubConnection', DriverManager::getConnection(['wrapperClass' => SubConnection::class]));
	}
}
