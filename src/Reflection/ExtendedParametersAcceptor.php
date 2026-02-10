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
 */
interface ExtendedParametersAcceptor extends ParametersAcceptor
{

	/**
	 * Returns extended parameter reflections with separate PHPDoc/native types.
	 *
	 * @return list<ExtendedParameterReflection>
	 */
	public function getParameters(): array;

	/**
	 * Returns the PHPDoc @return type, separate from the native type.
	 */
	public function getPhpDocReturnType(): Type;

	/**
	 * Returns the native PHP return type declaration.
	 */
	public function getNativeReturnType(): Type;

	/**
	 * Returns the variance map for template types at the call site.
	 *
	 * Used for @template-covariant and other call-site variance specifications.
	 */
	public function getCallSiteVarianceMap(): TemplateTypeVarianceMap;

}
