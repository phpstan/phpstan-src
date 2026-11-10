<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Nette\Utils\Strings;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
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
use function is_float;
use function is_numeric;
use function preg_match;
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

	// the whitespace PHP allows before a numeric string, and since PHP 8.0 after it, the characters PCRE's \s matches
	private const WHITESPACE = " \t\n\r\v\f";

	// the integer digits are captured only for a number without a fraction, the exponent separately
	private const NUMBER = '([+-]?(?:\d+\.\d*|\.\d+|(\d+))([eE][+-]?\d+)?)';

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

	/** @var array<string, true>|null */
	private ?array $values = null;

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
		$values = $this->getValues();
		foreach ($other->constants as $constant) {
			$value = $constant->getValue();
			if (isset($values[$value])) {
				continue;
			}

			$values[$value] = true;
			$constants[] = $constant;
		}

		if ($this->type === null || $other->type === null) {
			$typed = $this->type === null ? $other : $this;

			$union = new self($constants, $typed->type, $typed->digits, $typed->whitespaceFree);
		} else {
			$union = new self(
				$constants,
				TypeCombinator::union($this->type, $other->type),
				$this->digits && $other->digits,
				$this->whitespaceFree && $other->whitespaceFree,
			);
		}

		// a union of many members is folded one member at a time - handing the
		// lookup on spares rebuilding it from the constants at every step
		$union->values = $values;

		return $union;
	}

	public function intersect(self $other): self
	{
		$constants = [];
		$seen = [];
		foreach ([[$this, $other], [$other, $this]] as [$set, $otherSet]) {
			$otherValues = $otherSet->getValues();
			foreach ($set->constants as $constant) {
				$value = $constant->getValue();
				if (
					isset($seen[$value])
					|| (
						!isset($otherValues[$value])
						&& ($otherSet->type === null || $otherSet->type->isSuperTypeOf($constant)->no())
					)
				) {
					continue;
				}

				$seen[$value] = true;
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

	/**
	 * The value of a numeric string and the direction an integer overflowed in (1, -1, or 0).
	 * Leading whitespace is allowed in every PHP version, trailing whitespace since PHP 8.0.
	 *
	 * @return array{int|float, int}|null
	 */
	public static function parseNumericString(string $value, PhpVersion $phpVersion): ?array
	{
		$pattern = $phpVersion->allowsTrailingWhitespaceInNumericStrings()
			? '/^[' . self::WHITESPACE . ']*' . self::NUMBER . '[' . self::WHITESPACE . ']*$/D'
			: '/^[' . self::WHITESPACE . ']*' . self::NUMBER . '$/D';
		if (preg_match($pattern, $value, $matches) !== 1) {
			return null;
		}

		return self::toNumber($matches);
	}

	/** The number a string starts with, as PHP 7 compares a string with a number. */
	public static function parseNumericPrefix(string $value): int|float|null
	{
		if (preg_match('/^[' . self::WHITESPACE . ']*' . self::NUMBER . '/', $value, $matches) !== 1) {
			return null;
		}

		return self::toNumber($matches)[0];
	}

	/**
	 * @param array<int, string> $matches
	 * @return array{int|float, int}
	 */
	private static function toNumber(array $matches): array
	{
		$numericString = $matches[1];
		if (!is_numeric($numericString)) {
			throw new ShouldNotHappenException();
		}

		$number = +$numericString;
		$isIntegerSyntax = ($matches[2] ?? '') !== '' && ($matches[3] ?? '') === '';
		if ($isIntegerSyntax && is_float($number)) {
			return [$number, $number < 0 ? -1 : 1];
		}

		return [$number, 0];
	}

	private static function isNumeric(string $value, PhpVersion $phpVersion): bool
	{
		return self::parseNumericString($value, $phpVersion) !== null;
	}

	/**
	 * The values of the constants as array keys, so membership is a lookup
	 * rather than a scan - scanning made merging two sets quadratic. Distinct
	 * strings stay distinct keys: PHP turns only canonical decimal integer
	 * strings into integer keys, and no other string maps to the same one.
	 *
	 * @return array<string, true>
	 */
	private function getValues(): array
	{
		if ($this->values !== null) {
			return $this->values;
		}

		$values = [];
		foreach ($this->constants as $constant) {
			$values[$constant->getValue()] = true;
		}

		return $this->values = $values;
	}

}
