<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Nette\Utils\Strings;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantStringType;
use function count;
use function in_array;
use function is_numeric;
use function rtrim;
use function strtolower;
use function strtoupper;

/**
 * The strings of a ValueSet: a list of constant strings plus the type of the other
 * strings. The strings numbers cast to say more than their type: they never end in
 * whitespace, and those of non-negative integers are digits only.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/StringSet.cpp')]
final class StringSet
{

	// the characters PCRE's \s matches, which PHP 8 allows after a numeric string
	private const WHITESPACE = " \t\n\v\f\r";

	/**
	 * @param list<ConstantStringType> $constants
	 */
	private function __construct(
		private array $constants,
		private ?Type $type,
		private bool $digits,
		private bool $whitespaceFree,
	)
	{
	}

	public static function createEmpty(): self
	{
		return new self([], null, false, false);
	}

	public static function createConstant(ConstantStringType $string): self
	{
		return new self([$string], null, false, false);
	}

	/** Strings of a string type: StringType, or an accessory such as AccessoryNumericStringType. */
	public static function createType(Type $strings): self
	{
		return new self([], $strings, false, $strings->isDecimalIntegerString()->yes());
	}

	/** The same strings, cast from numbers: of non-negative integers only when $digits. */
	public function toNumberStrings(bool $digits): self
	{
		return new self($this->constants, $this->type, $digits, true);
	}

	/** @return list<ConstantStringType> */
	public function getConstantStrings(): array
	{
		return $this->constants;
	}

	/** The strings that are not constants, null when there are none. */
	public function getType(): ?Type
	{
		return $this->type;
	}

	public function isEmpty(): bool
	{
		return $this->constants === [] && $this->type === null;
	}

	public function union(self $other): self
	{
		$constants = $this->constants;
		foreach ($other->constants as $constant) {
			if (self::contains($constants, $constant->getValue())) {
				continue;
			}

			$constants[] = $constant;
		}

		if ($this->type === null || $other->type === null) {
			$typed = $this->type === null ? $other : $this;

			return new self($constants, $typed->type, $typed->digits, $typed->whitespaceFree);
		}

		return new self(
			$constants,
			TypeCombinator::union($this->type, $other->type),
			$this->digits && $other->digits,
			$this->whitespaceFree && $other->whitespaceFree,
		);
	}

	public function intersect(self $other): self
	{
		$constants = [];
		foreach ([[$this, $other], [$other, $this]] as [$set, $otherSet]) {
			foreach ($set->constants as $constant) {
				if (
					self::contains($constants, $constant->getValue())
					|| (
						!self::contains($otherSet->constants, $constant->getValue())
						&& ($otherSet->type === null || $otherSet->type->isSuperTypeOf($constant)->no())
					)
				) {
					continue;
				}

				$constants[] = $constant;
			}
		}

		if ($this->type === null || $other->type === null) {
			return new self($constants, null, false, false);
		}

		return new self(
			$constants,
			TypeCombinator::intersect($this->type, $other->type),
			$this->digits || $other->digits,
			$this->whitespaceFree || $other->whitespaceFree,
		);
	}

	public function toType(): Type
	{
		$types = $this->constants;
		if ($this->type !== null) {
			$types[] = $this->type;
		}

		return TypeCombinator::union(...$types);
	}

	/** Models the . operator. */
	public function concatenate(self $right, PhpVersion $phpVersion): Type
	{
		if ($this->isEmpty() || $right->isEmpty()) {
			return new NeverType();
		}
		if ($this->isEmptyString()) {
			return $right->toType();
		}
		if ($right->isEmptyString()) {
			return $this->toType();
		}

		// we limit the number of union-types for performance reasons
		if (
			$this->type === null
			&& $right->type === null
			&& count($this->constants) * count($right->constants) <= InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT
		) {
			$strings = [];
			foreach ($this->constants as $leftConstant) {
				if ($leftConstant->getValue() === '') {
					foreach ($right->constants as $rightConstant) {
						$strings[] = $rightConstant;
					}

					continue;
				}

				foreach ($right->constants as $rightConstant) {
					$strings[] = $rightConstant->getValue() === '' ? $leftConstant : $leftConstant->append($rightConstant);
				}
			}

			return TypeCombinator::union(...$strings);
		}

		$accessoryTypes = [];
		if ($this->isNonEmpty() && $right->isNonEmpty()) {
			$accessoryTypes[] = new AccessoryNonFalsyStringType();
		} elseif ($this->isNonFalsy() || $right->isNonFalsy()) {
			$accessoryTypes[] = new AccessoryNonFalsyStringType();
		} elseif ($this->isNonEmpty() || $right->isNonEmpty()) {
			$accessoryTypes[] = new AccessoryNonEmptyStringType();
		}

		if ($this->isLiteral() && $right->isLiteral()) {
			$accessoryTypes[] = new AccessoryLiteralStringType();
		}

		if ($this->isLowercase() && $right->isLowercase()) {
			$accessoryTypes[] = new AccessoryLowercaseStringType();
		}

		if ($this->isUppercase() && $right->isUppercase()) {
			$accessoryTypes[] = new AccessoryUppercaseStringType();
		}

		if (
			$this->isNumericOrEmpty($phpVersion)
			// '' . '' is not numeric
			&& ($this->isNonEmpty() || $right->isNonEmpty())
			// '1 ' is numeric on PHP 8, '1 5' is not
			&& !$this->canEndInWhitespace($phpVersion)
			&& $right->isNumberSuffix($this->isDecimalInteger(), $phpVersion)
		) {
			$accessoryTypes[] = new AccessoryNumericStringType();
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();
			return new IntersectionType($accessoryTypes);
		}

		return new StringType();
	}

	private function isEmptyString(): bool
	{
		return $this->type === null && count($this->constants) === 1 && $this->constants[0]->getValue() === '';
	}

	private function isNonEmpty(): bool
	{
		foreach ($this->constants as $constant) {
			if ($constant->getValue() === '') {
				return false;
			}
		}

		return $this->type === null || $this->type->isNonEmptyString()->yes();
	}

	private function isNonFalsy(): bool
	{
		foreach ($this->constants as $constant) {
			if (in_array($constant->getValue(), ['', '0'], true)) {
				return false;
			}
		}

		return $this->type === null || $this->type->isNonFalsyString()->yes();
	}

	private function isLiteral(): bool
	{
		return $this->type === null || $this->type->isLiteralString()->yes();
	}

	private function isLowercase(): bool
	{
		foreach ($this->constants as $constant) {
			if (strtolower($constant->getValue()) !== $constant->getValue()) {
				return false;
			}
		}

		return $this->type === null || $this->type->isLowercaseString()->yes();
	}

	private function isUppercase(): bool
	{
		foreach ($this->constants as $constant) {
			if (strtoupper($constant->getValue()) !== $constant->getValue()) {
				return false;
			}
		}

		return $this->type === null || $this->type->isUppercaseString()->yes();
	}

	private function isDecimalInteger(): bool
	{
		foreach ($this->constants as $constant) {
			if ((string) (int) $constant->getValue() !== $constant->getValue()) {
				return false;
			}
		}

		return $this->type === null || $this->type->isDecimalIntegerString()->yes();
	}

	/** Whether the strings other than '' are numeric. */
	private function isNumericOrEmpty(PhpVersion $phpVersion): bool
	{
		foreach ($this->constants as $constant) {
			if ($constant->getValue() !== '' && !self::isNumeric($constant->getValue(), $phpVersion)) {
				return false;
			}
		}

		return $this->type === null || $this->type->isNumericString()->yes();
	}

	/** Whether a string can end in whitespace, which PHP 8 allows in numeric strings and PHP 7 does not. */
	private function canEndInWhitespace(PhpVersion $phpVersion): bool
	{
		foreach ($this->constants as $constant) {
			if (rtrim($constant->getValue(), self::WHITESPACE) !== $constant->getValue()) {
				return true;
			}
		}

		return $this->type !== null && !$this->whitespaceFree && $phpVersion->allowsTrailingWhitespaceInNumericStrings();
	}

	/**
	 * Whether the strings keep a numeric string numeric when appended to it: digits,
	 * and after the digits of an integer also a fraction and an exponent. After them a
	 * sign or a whitespace is not numeric, and PHP 7 does not allow a trailing newline.
	 */
	private function isNumberSuffix(bool $afterInteger, PhpVersion $phpVersion): bool
	{
		foreach ($this->constants as $constant) {
			$value = $constant->getValue();
			if ($value === '') {
				continue;
			}

			$pattern = $afterInteger ? '#^\d+(\.\d*)?([eE][+-]?\d+)?\z#' : '#^\d+\z#';
			if (!self::isNumeric($value, $phpVersion) || Strings::match($value, $pattern) === null) {
				return false;
			}
		}

		return $this->type === null || $this->digits;
	}

	/** is_numeric() of the PHP version, which PHP 8 extends to trailing whitespace. */
	private static function isNumeric(string $value, PhpVersion $phpVersion): bool
	{
		$trimmed = rtrim($value, self::WHITESPACE);
		if ($trimmed !== $value && !$phpVersion->allowsTrailingWhitespaceInNumericStrings()) {
			return false;
		}

		return is_numeric($trimmed);
	}

	/** @param list<ConstantStringType> $constants */
	private static function contains(array $constants, string $value): bool
	{
		foreach ($constants as $constant) {
			if ($constant->getValue() === $value) {
				return true;
			}
		}

		return false;
	}

}
