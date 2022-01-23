<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

/** @api */
class MethodTag
{

	/**
	 * @param array<string, MethodTagParameter> $parameters
	 */
	public function __construct(
		private Type $returnType,
		private bool $isStatic,
		private array $parameters,
	)
	{
	}

	public function getReturnType(): Type
	{
		return $this->returnType;
	}

	public function isStatic(): bool
	{
		return $this->isStatic;
	}

	/**
	 * @return array<string, MethodTagParameter>
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

}
