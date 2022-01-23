<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Type\Type;

/** @api */
class FoundTypeResult
{

	/**
	 * @param string[] $referencedClasses
	 * @param RuleError[] $unknownClassErrors
	 */
	public function __construct(
		private Type $type,
		private array $referencedClasses,
		private array $unknownClassErrors,
		private ?string $tip,
	)
	{
	}

	public function getType(): Type
	{
		return $this->type;
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return $this->referencedClasses;
	}

	/**
	 * @return RuleError[]
	 */
	public function getUnknownClassErrors(): array
	{
		return $this->unknownClassErrors;
	}

	public function getTip(): ?string
	{
		return $this->tip;
	}

}
