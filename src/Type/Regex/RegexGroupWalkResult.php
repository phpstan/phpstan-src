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
		private TrinaryLogic $isNumeric,
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
		);
	}

	public function inOptionalQuantification(bool $inOptionalQuantification): self
	{
		return new self(
			$inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$this->isNumeric,
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
			$this->isNumeric,
		);
	}

	public function nonEmpty(TrinaryLogic $nonEmpty): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$nonEmpty,
			$this->isNonFalsy,
			$this->isNumeric,
		);
	}

	public function nonFalsy(TrinaryLogic $nonFalsy): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$nonFalsy,
			$this->isNumeric,
		);
	}

	public function numeric(TrinaryLogic $numeric): self
	{
		return new self(
			$this->inOptionalQuantification,
			$this->onlyLiterals,
			$this->isNonEmpty,
			$this->isNonFalsy,
			$numeric,
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

	public function isNumeric(): TrinaryLogic
	{
		return $this->isNumeric;
	}

}
