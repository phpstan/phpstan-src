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

}
