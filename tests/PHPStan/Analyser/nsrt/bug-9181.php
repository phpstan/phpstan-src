<?php declare(strict_types = 1);

namespace Bug9181;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	private function possiblyModifyObject(object $data): void {}

	public function sayHello(): void
	{
		$data = (object)[
			'search' => null,
		];

		assertType('null', $data->search);

		$this->possiblyModifyObject($data);

		assertType('mixed', $data->search);

		if (($search = $data->search) !== null) {
			assertType('mixed~null', $search);
		}
	}
}
