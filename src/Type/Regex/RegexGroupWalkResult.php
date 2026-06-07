<?php declare(strict_types = 1);

namespace PHPStan\Type\Regex;

use PHPStan\TrinaryLogic;

/** @immutable */
final class RegexGroupWalkResult
{

	/**
	 * @param array<string>|null $onlyLiterals
	 */
	public function __construct(
		private bool $inOptionalQuantification,
		private ?array $onlyLiterals,
		private TrinaryLogic $isNonEmpty,
		private TrinaryLogic $isNonFalsy,
		private TrinaryLogic $isDecimalInteger,
		private bool $seenDecimalIntegerSign,
		private bool $decimalLeadingResolved,
		private bool $decimalSeenDigit,
		private bool $decimalLeadCanBeZero,
		private bool $decimalBad,
		private bool $decimalAtomRepeats,
		private bool $decimalAtomOptional,
	)
	{
	}

	public static function createEmpty(): self
	{
		return new self(
			false,
			[],
			TrinaryLogic::createMaybe(),
			TrinaryLogic::createMaybe(),
			TrinaryLogic::createMaybe(),
			false,
			false,
			false,
			false,
			false,
			false,
			false,
		);
	}

	public function inOptionalQuantification(bool $inOptionalQuantification): self
	{
		return new self(
			$inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$this->isDecimalInteger,
			$this->seenDecimalIntegerSign,
			$this->decimalLeadingResolved,
			$this->decimalSeenDigit,
			$this->decimalLeadCanBeZero,
			$this->decimalBad,
			$this->decimalAtomRepeats,
			$this->decimalAtomOptional,
		);
	}

	/**
	 * @param array<string>|null $onlyLiterals
	 */
	public function onlyLiterals(?array $onlyLiterals): self
	{
		return new self(
			$this->inOptionalQuantification,
			$onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$this->isDecimalInteger,
			$this->seenDecimalIntegerSign,
			$this->decimalLeadingResolved,
			$this->decimalSeenDigit,
			$this->decimalLeadCanBeZero,
			$this->decimalBad,
			$this->decimalAtomRepeats,
			$this->decimalAtomOptional,
		);
	}

	public function nonEmpty(TrinaryLogic $nonEmpty): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$nonEmpty,
			$this->isNonFalsy,
			$this->isDecimalInteger,
			$this->seenDecimalIntegerSign,
			$this->decimalLeadingResolved,
			$this->decimalSeenDigit,
			$this->decimalLeadCanBeZero,
			$this->decimalBad,
			$this->decimalAtomRepeats,
			$this->decimalAtomOptional,
		);
	}

	public function nonFalsy(TrinaryLogic $nonFalsy): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$nonFalsy,
			$this->isDecimalInteger,
			$this->seenDecimalIntegerSign,
			$this->decimalLeadingResolved,
			$this->decimalSeenDigit,
			$this->decimalLeadCanBeZero,
			$this->decimalBad,
			$this->decimalAtomRepeats,
			$this->decimalAtomOptional,
		);
	}

	/** A decimal integer string is composed only of digits, optionally preceded by a single leading minus sign. */
	public function decimalInteger(TrinaryLogic $decimalInteger): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$decimalInteger,
			$this->seenDecimalIntegerSign,
			$this->decimalLeadingResolved,
			$this->decimalSeenDigit,
			$this->decimalLeadCanBeZero,
			$this->decimalBad,
			$this->decimalAtomRepeats,
			$this->decimalAtomOptional,
		);
	}

	public function seenDecimalIntegerSign(bool $seenDecimalIntegerSign): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$this->isDecimalInteger,
			$seenDecimalIntegerSign,
			$this->decimalLeadingResolved,
			$this->decimalSeenDigit,
			$this->decimalLeadCanBeZero,
			$this->decimalBad,
			$this->decimalAtomRepeats,
			$this->decimalAtomOptional,
		);
	}

	public function decimalLeadingResolved(bool $decimalLeadingResolved): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$this->isDecimalInteger,
			$this->seenDecimalIntegerSign,
			$decimalLeadingResolved,
			$this->decimalSeenDigit,
			$this->decimalLeadCanBeZero,
			$this->decimalBad,
			$this->decimalAtomRepeats,
			$this->decimalAtomOptional,
		);
	}

	public function decimalSeenDigit(bool $decimalSeenDigit): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$this->isDecimalInteger,
			$this->seenDecimalIntegerSign,
			$this->decimalLeadingResolved,
			$decimalSeenDigit,
			$this->decimalLeadCanBeZero,
			$this->decimalBad,
			$this->decimalAtomRepeats,
			$this->decimalAtomOptional,
		);
	}

	public function decimalLeadCanBeZero(bool $decimalLeadCanBeZero): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$this->isDecimalInteger,
			$this->seenDecimalIntegerSign,
			$this->decimalLeadingResolved,
			$this->decimalSeenDigit,
			$decimalLeadCanBeZero,
			$this->decimalBad,
			$this->decimalAtomRepeats,
			$this->decimalAtomOptional,
		);
	}

	public function decimalBad(bool $decimalBad): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$this->isDecimalInteger,
			$this->seenDecimalIntegerSign,
			$this->decimalLeadingResolved,
			$this->decimalSeenDigit,
			$this->decimalLeadCanBeZero,
			$decimalBad,
			$this->decimalAtomRepeats,
			$this->decimalAtomOptional,
		);
	}

	public function decimalAtomRepeats(bool $decimalAtomRepeats): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$this->isDecimalInteger,
			$this->seenDecimalIntegerSign,
			$this->decimalLeadingResolved,
			$this->decimalSeenDigit,
			$this->decimalLeadCanBeZero,
			$this->decimalBad,
			$decimalAtomRepeats,
			$this->decimalAtomOptional,
		);
	}

	public function decimalAtomOptional(bool $decimalAtomOptional): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$this->isDecimalInteger,
			$this->seenDecimalIntegerSign,
			$this->decimalLeadingResolved,
			$this->decimalSeenDigit,
			$this->decimalLeadCanBeZero,
			$this->decimalBad,
			$this->decimalAtomRepeats,
			$decimalAtomOptional,
		);
	}

	public function isInOptionalQuantification(): bool
	{
		return $this->inOptionalQuantification;
	}

	/**
	 * @return array<string>|null
	 */
	public function getOnlyLiterals(): ?array
	{
		return $this->onlyLiterals;
	}

	public function mightContainEmptyStringLiteral(): bool
	{
		if ($this->onlyLiterals === null) {
			return false;
		}
		foreach ($this->onlyLiterals as $onlyLiteral) {
			if ($onlyLiteral === '') {
				return true;
			}
		}

		return false;
	}

	public function isNonEmpty(): TrinaryLogic
	{
		return $this->isNonEmpty;
	}

	public function isNonFalsy(): TrinaryLogic
	{
		return $this->isNonFalsy;
	}

	public function isDecimalInteger(): TrinaryLogic
	{
		return $this->isDecimalInteger;
	}

	public function hasSeenDecimalIntegerSign(): bool
	{
		return $this->seenDecimalIntegerSign;
	}

	public function isDecimalLeadingResolved(): bool
	{
		return $this->decimalLeadingResolved;
	}

	public function hasDecimalSeenDigit(): bool
	{
		return $this->decimalSeenDigit;
	}

	public function isDecimalLeadCanBeZero(): bool
	{
		return $this->decimalLeadCanBeZero;
	}

	public function isDecimalBad(): bool
	{
		return $this->decimalBad;
	}

	public function isDecimalAtomRepeats(): bool
	{
		return $this->decimalAtomRepeats;
	}

	public function isDecimalAtomOptional(): bool
	{
		return $this->decimalAtomOptional;
	}

	/**
	 * Whether a decimal-integer match is guaranteed to be a canonical decimal
	 * integer string, i.e. it can never have a leading zero (like "00" or "007")
	 * or be a negative zero ("-0"). Both of those stay strings as array keys,
	 * so they must not be narrowed to decimal-int-string.
	 */
	public function isDecimalIntegerLeadingZeroSafe(): bool
	{
		return !$this->decimalBad && !($this->seenDecimalIntegerSign && $this->decimalLeadCanBeZero);
	}

}
