<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

/** @api */
class TrivialParametersAcceptor implements ParametersAcceptorWithPhpDocs, CallableParametersAcceptor
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

	public function getCallSiteVarianceMap(): TemplateTypeVarianceMap
	{
		return TemplateTypeVarianceMap::createEmpty();
	}

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

	public function getPhpDocReturnType(): Type
	{
		return new MixedType();
	}

	public function getNativeReturnType(): Type
	{
		return new MixedType();
	}

	public function getThrowPoints(): array
	{
		return [];
	}

	public function getInvalidateExpressions(): array
	{
		return [];
	}

	public function getUsedVariables(): array
	{
		return [];
	}

}
