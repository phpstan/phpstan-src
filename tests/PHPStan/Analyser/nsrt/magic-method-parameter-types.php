<?php

namespace MagicMethodParameterTypes;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	public function __get($name)
	{
		assertType('string', $name);
		assertNativeType('string', $name);
	}

	public function __set($name, $value)
	{
		assertType('string', $name);
		assertType('mixed', $value);
	}

	public function __isset($name)
	{
		assertType('string', $name);
		return true;
	}

	public function __unset($name)
	{
		assertType('string', $name);
	}

	public function __call($name, $args)
	{
		assertType('string', $name);
		assertType('array<mixed, mixed>', $args);
	}

	public static function __callStatic($name, $args)
	{
		assertType('string', $name);
		assertType('array<mixed, mixed>', $args);
	}

	public function __unserialize($data)
	{
		assertType('array<mixed, mixed>', $data);
	}

	public static function __set_state($properties)
	{
		assertType('array<mixed, mixed>', $properties);
		return new self();
	}

}

class DeclaredTypes
{

	/** @param non-empty-string $name */
	public function __get($name)
	{
		assertType('non-empty-string', $name);
		assertNativeType('string', $name);
	}

	public function __call(string $name, array $args)
	{
		assertType('string', $name);
		assertType('array', $args);
	}

	public function __construct($a)
	{
		assertType('mixed', $a);
	}

}
