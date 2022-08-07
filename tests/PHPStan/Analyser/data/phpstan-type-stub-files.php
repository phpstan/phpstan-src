<?php

namespace PhpstanTypeStubFiles;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type AnotherType = array{foo: int}
 * @phpstan-type CustomType = array{foo: string}
 */
class Foo
{
	/** @var CustomType */
	public $array;

	public function test(): void {
		$foo = new Foo();
		assertType('array{foo: string}', $foo->array);
	}
}
