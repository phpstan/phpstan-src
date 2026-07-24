<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;
use function in_array;

#[AutowiredService]
final class MethodThrowPointHelper
{

	/**
	 * @param ExtensionsCollection<DynamicMethodThrowTypeExtension> $dynamicMethodThrowTypeExtensions
	 * @param ExtensionsCollection<DynamicStaticMethodThrowTypeExtension> $dynamicStaticMethodThrowTypeExtensions
	 */
	public function __construct(
		#[AutowiredExtensions(of: DynamicMethodThrowTypeExtension::class)]
		private ExtensionsCollection $dynamicMethodThrowTypeExtensions,
		#[AutowiredExtensions(of: DynamicStaticMethodThrowTypeExtension::class)]
		private ExtensionsCollection $dynamicStaticMethodThrowTypeExtensions,
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
		ExpressionContext $context,
	): ?InternalThrowPoint
	{
		if ($normalizedMethodCall instanceof MethodCall) {
			foreach ($this->dynamicMethodThrowTypeExtensions->getAll() as $extension) {
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
			foreach ($this->dynamicStaticMethodThrowTypeExtensions->getAll() as $extension) {
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
			$returnType = $scope->getType($normalizedMethodCall);
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
			if (!$context->isInThrow() || !(new ObjectType(Throwable::class))->isSuperTypeOf($methodReturnedType)->yes()) {
				return InternalThrowPoint::createImplicit($scope, $normalizedMethodCall);
			}
		}

		return null;
	}

}
