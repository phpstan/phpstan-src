<?php

namespace ArrayFlipConstantArray;

use function array_flip;
use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		$allowlist = ['name', 'description', 'author', 'type', 'homepage', 'require', 'require-dev', 'stability', 'license', 'autoload'];
		assertType('array{name: 0, description: 1, author: 2, type: 3, homepage: 4, require: 5, require-dev: 6, stability: 7, license: 8, autoload: 9}', array_flip($allowlist));
	}

	public function doOptional(): void
	{
		$allowlist = ['name', 'description', 'author', 'type', 'homepage', 'require', 'require-dev', 'stability', 'license', 'autoload'];
		if (rand(0, 1)) {
			$allowlist[] = 'config';
		}
		assertType('array{name: 0, description: 1, author: 2, type: 3, homepage: 4, require: 5, require-dev: 6, stability: 7, license: 8, autoload: 9, config?: 10}', array_flip($allowlist));
	}

}
