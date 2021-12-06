<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

/** @api */
class TrivialParametersAcceptor implements ParametersAcceptor
{

	/** @api */
	public function __construct()
	{
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return TemplateTypeMap::createEmpty();
	}

	public function getResolvedTemplateTypeMap(): TemplateTypeMap
	{
		return TemplateTypeMap::createEmpty();
	}

	/**
	 * @return array<int, ParameterReflection>
	 */
	public function getParameters(): array
	{
		return [];
	}

	public function isVariadic(): bool
	{
		return true;
	}

	public function getReturnType(): Type
	{
		return new MixedType();
	}

}
