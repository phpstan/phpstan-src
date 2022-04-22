<?php declare(strict_types = 1);

namespace TemplateTypeInOneBranchOfConditional;

use stdClass;
use function PHPStan\Testing\assertType;

class Connection {}

class ChildConnection extends Connection {}

class DriverManager
{
	/**
	 * @param array{wrapperClass?: class-string<T>} $params
	 * @phpstan-return ($params is array{wrapperClass:mixed} ? T : Connection)
	 * @template T of Connection
	 */
	public static function getConnection(array $params): Connection {
		return new Connection();
	}

	public static function test(): void
	{
		assertType(Connection::class, DriverManager::getConnection([]));
		assertType(ChildConnection::class, DriverManager::getConnection(['wrapperClass' => ChildConnection::class]));
		assertType(Connection::class, DriverManager::getConnection(['wrapperClass' => stdClass::class]));
	}
}
