<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;

/**
 * @api
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/FoundTypeResult.cpp')]
final class FoundTypeResult
{

	/**
	 * @param string[] $referencedClasses
	 * @param list<IdentifierRuleError> $unknownClassErrors
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
	 * @return list<IdentifierRuleError>
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
