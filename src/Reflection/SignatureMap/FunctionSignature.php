<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\Type\Type;

final class FunctionSignature
{

	/**
	 * @param array<int, ParameterSignature> $parameters
	 */
	public function __construct(
		private array $parameters,
		private Type $returnType,
		private Type $nativeReturnType,
		private bool $variadic,
	)
	{
	}

	/**
	 * @return array<int, ParameterSignature>
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function getReturnType(): Type
	{
		return $this->returnType;
	}

	public function getNativeReturnType(): Type
	{
		return $this->nativeReturnType;
	}

	public function isVariadic(): bool
	{
		return $this->variadic;
	}

}
