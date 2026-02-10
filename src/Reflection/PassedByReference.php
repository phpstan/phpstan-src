<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use function array_key_exists;

/**
 * Describes how a function/method parameter is passed: by value or by reference.
 *
 * Three modes:
 * - **No**: Passed by value — the argument expression is evaluated and its value is copied.
 * - **ReadsArgument**: Passed by reference, but the function reads the existing variable.
 *   The variable must already exist. Example: `sort(&$array)`.
 * - **CreatesNewVariable**: Passed by reference, and the function may create the variable
 *   if it doesn't exist. Example: `preg_match($pattern, $subject, &$matches)` where
 *   `$matches` doesn't need to be defined beforehand.
 *
 * This distinction matters for PHPStan's scope analysis — when a function takes a
 * parameter by reference with "creates new variable" semantics, PHPStan knows the
 * variable will exist after the call even if it wasn't defined before.
 *
 * Used as the return type of ParameterReflection::passedByReference().
 *
 * @api
 */
final class PassedByReference
{

	private const NO = 1;
	private const READS_ARGUMENT = 2;
	private const CREATES_NEW_VARIABLE = 3;

	/** @var self[] */
	private static array $registry = [];

	private function __construct(private int $value)
	{
	}

	private static function create(int $value): self
	{
		if (!array_key_exists($value, self::$registry)) {
			self::$registry[$value] = new self($value);
		}

		return self::$registry[$value];
	}

	public static function createNo(): self
	{
		return self::create(self::NO);
	}

	public static function createCreatesNewVariable(): self
	{
		return self::create(self::CREATES_NEW_VARIABLE);
	}

	public static function createReadsArgument(): self
	{
		return self::create(self::READS_ARGUMENT);
	}

	public function no(): bool
	{
		return $this->value === self::NO;
	}

	public function yes(): bool
	{
		return !$this->no();
	}

	public function equals(self $other): bool
	{
		return $this->value === $other->value;
	}

	public function createsNewVariable(): bool
	{
		return $this->value === self::CREATES_NEW_VARIABLE;
	}

	/** CreatesNewVariable > ReadsArgument > No. */
	public function combine(self $other): self
	{
		if ($this->value > $other->value) {
			return $this;
		} elseif ($this->value < $other->value) {
			return $other;
		}

		return $this;
	}

}
