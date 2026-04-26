<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Type\UnionTypeMethodReflection;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;
use function count;
use function in_array;

#[AutowiredService]
final class MethodThrowPointHelper
{

	public function __construct(
		private DynamicThrowTypeExtensionProvider $dynamicThrowTypeExtensionProvider,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
	)
	{
	}

	public function getThrowPoint(
		MethodReflection $methodReflection,
		ParametersAcceptor $parametersAcceptor,
		MethodCall|StaticCall $normalizedMethodCall,
		MutatingScope $scope,
	): ?InternalThrowPoint
	{
		if ($methodReflection instanceof UnionTypeMethodReflection) {
			$throwPoints = [];
			foreach ($methodReflection->getMethods() as $innerMethodReflection) {
				$throwPoint = $this->getThrowPoint($innerMethodReflection, $parametersAcceptor, $normalizedMethodCall, $scope);
				if ($throwPoint === null) {
					continue;
				}

				$throwPoints[] = $throwPoint;
			}

			if (count($throwPoints) === 0) {
				return null;
			}

			$throwTypes = [];
			$canContainAnyThrowable = false;
			$isExplicit = false;
			foreach ($throwPoints as $throwPoint) {
				$throwTypes[] = $throwPoint->getType();
				if ($throwPoint->canContainAnyThrowable()) {
					$canContainAnyThrowable = true;
				}
				if (!$throwPoint->isExplicit()) {
					continue;
				}
				$isExplicit = true;
			}

			if (!$isExplicit) {
				return InternalThrowPoint::createImplicit($scope, $normalizedMethodCall);
			}

			return InternalThrowPoint::createExplicit($scope, TypeCombinator::union(...$throwTypes), $normalizedMethodCall, $canContainAnyThrowable);
		}

		if ($normalizedMethodCall instanceof MethodCall) {
			foreach ($this->dynamicThrowTypeExtensionProvider->getDynamicMethodThrowTypeExtensions() as $extension) {
				if (!$extension->isMethodSupported($methodReflection)) {
					continue;
				}

				$throwType = $extension->getThrowTypeFromMethodCall($methodReflection, $normalizedMethodCall, $scope);
				if ($throwType === null) {
					return null;
				}

				return InternalThrowPoint::createExplicit($scope, $throwType, $normalizedMethodCall, false);
			}
		} else {
			foreach ($this->dynamicThrowTypeExtensionProvider->getDynamicStaticMethodThrowTypeExtensions() as $extension) {
				if (!$extension->isStaticMethodSupported($methodReflection)) {
					continue;
				}

				$throwType = $extension->getThrowTypeFromStaticMethodCall($methodReflection, $normalizedMethodCall, $scope);
				if ($throwType === null) {
					return null;
				}

				return InternalThrowPoint::createExplicit($scope, $throwType, $normalizedMethodCall, false);
			}
		}

		if (
			$normalizedMethodCall instanceof MethodCall
			&& in_array($methodReflection->getName(), ['invoke', 'invokeArgs'], true)
			&& in_array($methodReflection->getDeclaringClass()->getName(), [ReflectionMethod::class, ReflectionFunction::class], true)
		) {
			return InternalThrowPoint::createImplicit($scope, $normalizedMethodCall);
		}

		$throwType = $methodReflection->getThrowType();
		if ($throwType === null) {
			$returnType = $parametersAcceptor->getReturnType();
			if ($returnType instanceof NeverType && $returnType->isExplicit()) {
				$throwType = new ObjectType(Throwable::class);
			}
		}

		if ($throwType !== null) {
			if (!$throwType->isVoid()->yes()) {
				return InternalThrowPoint::createExplicit($scope, $throwType, $normalizedMethodCall, true);
			}
		} elseif ($this->implicitThrows) {
			$methodReturnedType = $scope->getType($normalizedMethodCall);
			if (!(new ObjectType(Throwable::class))->isSuperTypeOf($methodReturnedType)->yes()) {
				return InternalThrowPoint::createImplicit($scope, $normalizedMethodCall);
			}
		}

		return null;
	}

}
