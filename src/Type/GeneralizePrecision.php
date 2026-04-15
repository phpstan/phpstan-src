<?php declare(strict_types = 1);

namespace PHPStan\Type;

/**
 * Controls how aggressively Type::generalize() widens a type.
 *
 * Generalization is the process of widening a specific type to a broader one.
 * For example, generalizing ConstantStringType('hello') yields StringType.
 * This is used when PHPStan needs to merge types across loop iterations or
 * branches where tracking precise constant values is impractical.
 *
 * Three levels of precision:
 * - **lessSpecific**: Aggressive generalization — constant values become their
 *   general type (e.g. 'hello' → string, array{foo: int} → array<string, int>)
 * - **moreSpecific**: Preserves more detail — e.g. non-empty-string stays
 *   non-empty-string instead of widening to string
 * - **templateArgument**: Used when generalizing template type arguments,
 *   preserving template-specific structure
 *
 * Used as a parameter to Type::generalize():
 *
 *     $type->generalize(GeneralizePrecision::lessSpecific()) //
 */
final class GeneralizePrecision
{

	private const LESS_SPECIFIC = 1;
	private const MORE_SPECIFIC = 2;
	private const TEMPLATE_ARGUMENT = 3;

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

	/** @api */
	public static function templateArgument(): self
	{
		return self::create(self::TEMPLATE_ARGUMENT);
	}

	public function isMoreSpecific(): bool
	{
		return $this->value === self::MORE_SPECIFIC;
	}

	public function isTemplateArgument(): bool
	{
		return $this->value === self::TEMPLATE_ARGUMENT;
	}

}
