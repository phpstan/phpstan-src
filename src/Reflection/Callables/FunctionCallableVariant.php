<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use function array_map;

class FunctionCallableVariant implements CallableParametersAcceptor
{

	public function __construct(
		private ParametersAcceptor $variant,
	)
	{
	}

	/**
	 * @param ParametersAcceptor[] $variants
	 * @return self[]
	 */
	public static function createFromVariants(array $variants): array
	{
		return array_map(static fn (ParametersAcceptor $variant) => new self($variant), $variants);
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return $this->variant->getTemplateTypeMap();
	}

	public function getResolvedTemplateTypeMap(): TemplateTypeMap
	{
		return $this->variant->getResolvedTemplateTypeMap();
	}

	public function getParameters(): array
	{
		return $this->variant->getParameters();
	}

	public function isVariadic(): bool
	{
		return $this->variant->isVariadic();
	}

	public function getReturnType(): Type
	{
		return $this->variant->getReturnType();
	}

}
