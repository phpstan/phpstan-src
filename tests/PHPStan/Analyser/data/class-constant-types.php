<?php

namespace ClassConstantTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	const NO_TYPE = 1;

	/** @var string */
	const TYPE = 'foo';

	/** @var string */
	private const PRIVATE_TYPE = 'foo';

	public function doFoo()
	{
		assertType('1', self::NO_TYPE);
		assertType('mixed', static::NO_TYPE);
		assertType('mixed', $this::NO_TYPE);

		assertType('\'foo\'', self::TYPE);
		assertType('string', static::TYPE);
		assertType('string', $this::TYPE);

		assertType('\'foo\'', self::PRIVATE_TYPE);
		assertType('string', static::PRIVATE_TYPE);
		assertType('string', $this::PRIVATE_TYPE);
	}

}

class Bar extends Foo
{

	const TYPE = 'bar';

	private const PRIVATE_TYPE = 'bar';

	public function doFoo()
	{
		assertType('\'bar\'', self::TYPE);
		assertType('string', static::TYPE);
		assertType('string', $this::TYPE);

		assertType('\'bar\'', self::PRIVATE_TYPE);
		assertType('mixed', static::PRIVATE_TYPE);
		assertType('mixed', $this::PRIVATE_TYPE);
	}

}

class Baz extends Foo
{

	/** @var int */
	const TYPE = 1;

	public function doFoo()
	{
		assertType('1', self::TYPE);
		assertType('int', static::TYPE);
		assertType('int', $this::TYPE);
	}

}

class Lorem extends Foo
{

	/** description */
	const TYPE = 1;

	public function doFoo()
	{
		assertType('1', self::TYPE);
		assertType('string', static::TYPE);
		assertType('string', $this::TYPE);
	}

}

final class FinalFoo
{

	const NO_TYPE = 1;

	/** @var string */
	const TYPE = 'foo';

	/** @var string */
	private const PRIVATE_TYPE = 'foo';

	public function doFoo()
	{
		assertType('1', self::NO_TYPE);
		assertType('1', static::NO_TYPE);
		assertType('1', $this::NO_TYPE);

		assertType('\'foo\'', self::TYPE);
		assertType('\'foo\'', static::TYPE);
		assertType('\'foo\'', $this::TYPE);

		assertType('\'foo\'', self::PRIVATE_TYPE);
		assertType('\'foo\'', static::PRIVATE_TYPE);
		assertType('\'foo\'', $this::PRIVATE_TYPE);
	}

}
