<?php

namespace Sqlite3ResultFetchArray;

use SQLite3Result;
use function PHPStan\Testing\assertType;

class Foo
{

	public function fetchArrayDefault(SQLite3Result $result): void
	{
		assertType('non-empty-array<int|string, mixed>|false', $result->fetchArray());
	}

	public function fetchArrayBoth(SQLite3Result $result): void
	{
		assertType('non-empty-array<int|string, mixed>|false', $result->fetchArray(SQLITE3_BOTH));
	}

	public function fetchArrayNum(SQLite3Result $result): void
	{
		assertType('non-empty-list<mixed>|false', $result->fetchArray(SQLITE3_NUM));
	}

	public function fetchArrayAssoc(SQLite3Result $result): void
	{
		assertType('non-empty-array<string, mixed>|false', $result->fetchArray(SQLITE3_ASSOC));
	}

}
