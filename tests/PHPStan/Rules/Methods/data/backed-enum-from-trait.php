<?php // lint >= 8.1

declare(strict_types = 1);

namespace BackedEnumFromTraitOverride;

trait IsEnumHelper
{

	public static function fromName(string $name): self
	{
		return static::tryFromName($name) ?? throw new \ValueError(sprintf('"%s" is not a valid case name for enum "%s"', $name, static::class));
	}

	public static function tryFromName(string $name): ?static
	{
		$cases = array_filter(
			static::cases(),
			fn (\UnitEnum $case) => $case->name === $name,
		);

		return array_shift($cases);
	}

	/**
	 * This will not override the `from()` method for Backed enums
	 */
	public static function from(string $case): self
	{
		return static::fromName($case);
	}

	/**
	 * This will not override the `tryFrom()` method for Backed enums
	 */
	public static function tryFrom(string $case): ?static
	{
		return static::tryFromName($case);
	}

}

enum SampleIntegerBackedEnum: int
{

	use IsEnumHelper;

	case SUCCESS = 200;
	case CREATED = 201;

}

enum SampleStringBackedEnum: string
{

	use IsEnumHelper;

	case SUCCESS = 'success';

}

enum SamplePureEnum
{

	use IsEnumHelper;

	case SUCCESS;

}

trait CasesTrait
{

	public static function cases(): string
	{
		return '';
	}

}

enum CasesFromTrait
{

	use CasesTrait;

	case ONE;

}
