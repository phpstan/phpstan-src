<?php // lint >= 8.5

namespace Sqlite3ResultFetchAll;

use SQLite3Result;
use function PHPStan\Testing\assertType;

class Foo
{

	public function fetchAllDefault(SQLite3Result $result): void
	{
		assertType('list<non-empty-array<int|string, mixed>>|false', $result->fetchAll());
	}

	public function fetchAllBoth(SQLite3Result $result): void
	{
		assertType('list<non-empty-array<int|string, mixed>>|false', $result->fetchAll(SQLITE3_BOTH));
	}

	public function fetchAllNum(SQLite3Result $result): void
	{
		assertType('list<non-empty-list<mixed>>|false', $result->fetchAll(SQLITE3_NUM));
	}

	public function fetchAllAssoc(SQLite3Result $result): void
	{
		assertType('list<non-empty-array<string, mixed>>|false', $result->fetchAll(SQLITE3_ASSOC));
	}

}
