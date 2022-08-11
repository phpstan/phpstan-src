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

	/** @var string */
	public $string;

	/** @param int $test1 */
	public function test($test1, $test2): void {
		$foo = new Foo();
		assertType('array{foo: string}', $foo->array);
		assertType('string', $foo->string);
		assertType('int', $test1);
		assertType('array<string>', $test2);
	}
}
