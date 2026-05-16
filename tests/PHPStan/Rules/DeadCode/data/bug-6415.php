<?php

namespace Bug6415;

class Foo
{

	private const SOME_MAP = [
		'a' => 1,
		'b' => 2,
		'c' => 3,
	];

	/** @return value-of<self::SOME_MAP> */
	public function getValueOf(): int
	{
		return 1;
	}

}

class Bar
{

	private const SOME_MAP = [
		'x' => 'hello',
		'y' => 'world',
	];

	/** @return key-of<self::SOME_MAP> */
	public function getKeyOf(): string
	{
		return 'x';
	}

}

class Baz
{

	private const STATUS_ACTIVE = 'active';
	private const STATUS_INACTIVE = 'inactive';

	private const STATUSES = [
		self::STATUS_ACTIVE,
		self::STATUS_INACTIVE,
	];

	/** @return value-of<self::STATUSES> */
	public function getStatus(): string
	{
		return self::STATUS_ACTIVE;
	}

}

class PropertyPhpDoc
{

	private const ALLOWED_KEYS = ['foo', 'bar', 'baz'];

	/** @var key-of<self::ALLOWED_KEYS> */
	private int $selectedIndex = 0;

}

class IntMaskOf
{

	private const FLAG_A = 1;
	private const FLAG_B = 2;
	private const FLAG_C = 4;

	/** @return int-mask-of<self::FLAG_*> */
	public function getFlags(): int
	{
		return self::FLAG_A | self::FLAG_B;
	}

}

class ConstTypeNotation
{

	private const MODE_READ = 'read';
	private const MODE_WRITE = 'write';

	/** @param self::MODE_* $mode */
	public function setMode(string $mode): void
	{
	}

}

/**
 * @phpstan-type AllowedValue value-of<self::ALLOWED_VALUES>
 */
class TypeAlias
{

	private const ALLOWED_VALUES = ['a', 'b', 'c'];

	/** @return AllowedValue */
	public function getValue(): string
	{
		return 'a';
	}

}

class ParamType
{

	private const FORMATS = ['json', 'xml', 'csv'];

	/** @param value-of<self::FORMATS> $format */
	public function export(string $format): void
	{
	}

}

class AssertType
{

	private const VALID_TYPES = ['foo', 'bar'];

	/**
	 * @phpstan-assert value-of<self::VALID_TYPES> $value
	 */
	public function assertValid(string $value): void
	{
	}

}

class MixedUsage
{

	private const USED_IN_PHPDOC = ['a', 'b'];
	private const ACTUALLY_UNUSED = 'unused';

	/** @return value-of<self::USED_IN_PHPDOC> */
	public function getValue(): string
	{
		return 'a';
	}

}
