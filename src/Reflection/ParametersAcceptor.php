<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;

/** @api */
interface ParametersAcceptor
{

	public const VARIADIC_FUNCTIONS = [
		'func_get_args',
		'func_get_arg',
		'func_num_args',
	];

	public function getTemplateTypeMap(): TemplateTypeMap;

	public function getResolvedTemplateTypeMap(): TemplateTypeMap;

	public function getCallSiteVarianceMap(): TemplateTypeVarianceMap;

	/**
	 * @return array<int, ParameterReflection>
	 */
	public function getParameters(): array;

	public function isVariadic(): bool;

	public function getReturnType(): Type;

}
