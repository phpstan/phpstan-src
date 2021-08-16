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

		assertType('string', self::TYPE);
		assertType('string', static::TYPE);

		assertType('string', self::PRIVATE_TYPE);
		assertType('string', static::PRIVATE_TYPE);
	}

}

class Bar extends Foo
{

	const TYPE = 'bar';

	private const PRIVATE_TYPE = 'bar';

	public function doFoo()
	{
		assertType('string', self::TYPE);
		assertType('string', static::TYPE);

		assertType('\'bar\'', self::PRIVATE_TYPE);
		assertType('mixed', static::PRIVATE_TYPE);
	}

}

class Baz extends Foo
{

	/** @var int */
	const TYPE = 1;

	public function doFoo()
	{
		assertType('int', self::TYPE);
		assertType('int', static::TYPE);
	}

}

class Lorem extends Foo
{

	/** description */
	const TYPE = 1;

	public function doFoo()
	{
		assertType('string', self::TYPE);
		assertType('string', static::TYPE);
	}

}
