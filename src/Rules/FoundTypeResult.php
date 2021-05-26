<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Type\Type;

/** @api */
class FoundTypeResult
{

	private \PHPStan\Type\Type $type;

	/** @var string[] */
	private array $referencedClasses;

	/** @var RuleError[] */
	private array $unknownClassErrors;

	/**
	 * @param \PHPStan\Type\Type $type
	 * @param string[] $referencedClasses
	 * @param RuleError[] $unknownClassErrors
	 */
	public function __construct(
		Type $type,
		array $referencedClasses,
		array $unknownClassErrors
	)
	{
		$this->type = $type;
		$this->referencedClasses = $referencedClasses;
		$this->unknownClassErrors = $unknownClassErrors;
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

}
