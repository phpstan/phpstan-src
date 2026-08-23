<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;

#[AutowiredService]
final class PropertyHookThrowPointsResolver
{

	public function __construct(
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
	)
	{
	}

	/**
	 * @param 'get'|'set' $hookName
	 * @return InternalThrowPoint[]
	 */
	public function getThrowPointsFromPropertyHook(
		MutatingScope $scope,
		PropertyFetch $propertyFetch,
		PhpPropertyReflection $propertyReflection,
		string $hookName,
	): array
	{
		$scopeFunction = $scope->getFunction();
		if (
			$scopeFunction instanceof PhpMethodFromParserNodeReflection
			&& $scopeFunction->isPropertyHook()
			&& $propertyFetch->var instanceof Variable
			&& $propertyFetch->var->name === 'this'
			&& $propertyFetch->name instanceof Identifier
			&& $propertyFetch->name->toString() === $scopeFunction->getHookedPropertyName()
		) {
			return [];
		}
		$declaringClass = $propertyReflection->getDeclaringClass();
		if (!$propertyReflection->hasHook($hookName)) {
			if (
				$propertyReflection->isPrivate()
				|| $propertyReflection->isFinal()->yes()
				|| $declaringClass->isFinal()
			) {
				return [];
			}

			if ($this->implicitThrows) {
				return [InternalThrowPoint::createImplicit($scope, $propertyFetch)];
			}

			return [];
		}

		$getHook = $propertyReflection->getHook($hookName);
		$throwType = $getHook->getThrowType();

		if ($throwType !== null) {
			if (!$throwType->isVoid()->yes()) {
				return [InternalThrowPoint::createExplicit($scope, $throwType, $propertyFetch, true)];
			}
		} elseif ($this->implicitThrows) {
			return [InternalThrowPoint::createImplicit($scope, $propertyFetch)];
		}

		return [];
	}

}
