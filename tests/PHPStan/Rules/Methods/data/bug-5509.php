<?php // lint >= 8.0

namespace Bug5509;

class Foo
{
	public function test(\PDOStatement $stmt): void
	{
		// FETCH_CLASS with class name and constructor args - should not error
		$stmt->fetchAll(\PDO::FETCH_CLASS, \stdClass::class, [new \stdClass]);

		// FETCH_CLASS with just class name - should not error
		$stmt->fetchAll(\PDO::FETCH_CLASS, \stdClass::class);

		// FETCH_COLUMN with column number - should not error
		$stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

		// FETCH_FUNC with callable - should not error
		$stmt->fetchAll(\PDO::FETCH_FUNC, function () {
			return 'test';
		});

		// No args - should not error
		$stmt->fetchAll();

		// With just mode - should not error
		$stmt->fetchAll(\PDO::FETCH_ASSOC);
	}
}
