<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;

/**
 * This is the interface type-specifying extensions implement for static methods.
 *
 * To register it in the configuration file use the `phpstan.typeSpecifier.staticMethodTypeSpecifyingExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.typeSpecifier.staticMethodTypeSpecifyingExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/type-specifying-extensions
 *
 * @api
 */
interface StaticMethodTypeSpecifyingExtension
{

	public function getClass(): string;

	public function isStaticMethodSupported(MethodReflection $staticMethodReflection, StaticCall $node, TypeSpecifierContext $context): bool;

	public function specifyTypes(MethodReflection $staticMethodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes;

}
