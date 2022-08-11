<?php

namespace PhpstanTypeStubFiles;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-template T of object
 */
class Bar
{
	/** @var T|null */
	public $object;
}

/**
 * @phpstan-type AnotherType = array{foo: int}
 * @phpstan-type CustomType = array{foo: string}
 *
 * @phpstan-template T of object
 * @phpstan-extends Bar<object>
 */
class Foo extends Bar
{
	/** @var CustomType */
	public $array;

	/** @var string */
	public $string;

	/** @var object */
	public $template;

	/** @param int $test1 */
	public function test($test1, $test2): void {
		/** @var Foo<\DateTime> $foo */
		$foo = new Foo();

		assertType('array{foo: string}', $foo->array);
		assertType('string', $foo->string);
		assertType('DateTime|null', $foo->object);
		assertType('DateTime', $foo->template);
		assertType('int', $test1);
		assertType('array<string>', $test2);
	}
}
