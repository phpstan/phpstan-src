<?php

namespace GenericUnions;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @template T
	 * @param T|null $p
	 * @return T
	 */
	public function doFoo($p)
	{
		if ($p === null) {
			throw new \Exception();
		}

		return $p;
	}

	/**
	 * @template T
	 * @param T $p
	 * @return T
	 */
	public function doBar($p)
	{
		return $p;
	}

	/**
	 * @template T
	 * @param T|int|float $p
	 * @return T
	 */
	public function doBaz($p)
	{
		return $p;
	}

	/**
	 * @param int|string $stringOrInt
	 */
	public function foo(
		?string $nullableString,
		$stringOrInt
	): void
	{
		assertType('string', $this->doFoo($nullableString));
		assertType('int|string', $this->doFoo($stringOrInt));

		assertType('string|null', $this->doBar($nullableString));

		assertType('int', $this->doBaz(1));
		assertType('string', $this->doBaz('foo'));
		assertType('float', $this->doBaz(1.2));
		assertType('string', $this->doBaz($stringOrInt));
	}

}

class InvocableClass
{
	public function __invoke(): string
	{
		return 'foo';
	}
}

/**
 *
 * @template TGetDefault
 * @template TKey
 *
 * @param  TKey  $key
 * @param  TGetDefault|(\Closure(): TGetDefault)  $default
 * @return TKey|TGetDefault
 */
function getWithDefault($key, $default = null)
{
	if(rand(0,10) > 5) {
		return $key;
	}

	if (is_callable($default)) {
		return $default();
	}

	return $default;
}

/**
 *
 * @template TGetDefault
 * @template TKey
 *
 * @param  TKey  $key
 * @param  TGetDefault|(callable(): TGetDefault)  $default
 * @return TKey|TGetDefault
 */
function getWithDefaultCallable($key, $default = null)
{
	if(rand(0,10) > 5) {
		return $key;
	}

	if (is_callable($default)) {
		return $default();
	}

	return $default;
}

assertType('int|null', getWithDefault(3));
assertType('int|null', getWithDefaultCallable(3));
assertType('int|string', getWithDefault(3, 'foo'));
assertType('int|string', getWithDefaultCallable(3, 'foo'));
assertType('int|string', getWithDefault(3, function () {
	return 'foo';
}));
assertType('int|string', getWithDefaultCallable(3, function () {
	return 'foo';
}));
assertType('GenericUnions\Foo|int', getWithDefault(3, function () {
	return new Foo;
}));
assertType('GenericUnions\Foo|int', getWithDefaultCallable(3, function () {
	return new Foo;
}));
assertType('GenericUnions\Foo|int', getWithDefault(3, new Foo));
assertType('GenericUnions\Foo|int', getWithDefaultCallable(3, new Foo));
assertType('int|string', getWithDefaultCallable(3, new InvocableClass));
