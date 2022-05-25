<?php declare(strict_types = 1);

namespace Bug7318;

class HelloWorld
{
	public function test(): void
	{
		/** @var array<string, array{prop: array{unique: boolean}}> $types */
		$types = ['Foo' => ['prop' => ['unique' => true]]];

		if ($types['Bar']['prop']['unique'] ?? false) {
		}

		if (isset($types['Bar']['prop']['unique'])) {
		}

		if (empty($types['Bar']['prop']['unique'])) {
		}
	}
}
