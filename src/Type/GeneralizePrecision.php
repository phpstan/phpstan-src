<?php declare(strict_types = 1);

namespace PHPStan\Type;

class GeneralizePrecision
{

	private const LESS_SPECIFIC = 1;
	private const MORE_SPECIFIC = 2;

	/** @var self[] */
	private static array $registry;

	private function __construct(private int $value)
	{
	}

	private static function create(int $value): self
	{
		self::$registry[$value] ??= new self($value);
		return self::$registry[$value];
	}

	/** @api */
	public static function lessSpecific(): self
	{
		return self::create(self::LESS_SPECIFIC);
	}

	/** @api */
	public static function moreSpecific(): self
	{
		return self::create(self::MORE_SPECIFIC);
	}

	public function isMoreSpecific(): bool
	{
		return $this->value === self::MORE_SPECIFIC;
	}

}
