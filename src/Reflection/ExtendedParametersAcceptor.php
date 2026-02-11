<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;

/**
 * Extended function/method signature with separate PHPDoc and native types.
 *
 * Extends ParametersAcceptor with:
 * - Extended parameter reflections (separate PHPDoc/native types per parameter)
 * - Separate PHPDoc and native return types (vs the combined return type from ParametersAcceptor)
 * - Call-site variance map for template type parameters
 *
 * This is the return type of FunctionReflection::getVariants() and
 * ExtendedMethodReflection::getVariants().
 *
 * @api
 * @api-do-not-implement
 */
interface ExtendedParametersAcceptor extends ParametersAcceptor
{

	/** @return list<ExtendedParameterReflection> */
	public function getParameters(): array;

	public function getPhpDocReturnType(): Type;

	public function getNativeReturnType(): Type;

	public function getCallSiteVarianceMap(): TemplateTypeVarianceMap;

}
