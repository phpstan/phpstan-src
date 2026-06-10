<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;
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

	/**
	 * @param (callable(): Type)|null $returnTypeCallback lazily resolves the
	 *        call's return type for the explicit-never and implicit-throws
	 *        checks — mirrors FuncCallHandler's shape; null keeps the guarded
	 *        legacy scope ask (PHPSTAN_FNSR=0) until the call handlers migrate
	 */
	public function getThrowPoint(
		MethodReflection $methodReflection,
		ParametersAcceptor $parametersAcceptor,
		MethodCall|StaticCall $normalizedMethodCall,
		MutatingScope $scope,
		ExpressionContext $context,
		?callable $returnTypeCallback = null,
	): ?InternalThrowPoint
	{
		$returnTypeCallback ??= static fn (): Type => $scope->getType($normalizedMethodCall);
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
			$returnType = $returnTypeCallback();
			if ($returnType instanceof NeverType && $returnType->isExplicit()) {
				$throwType = new ObjectType(Throwable::class);
			}
		}

		if ($throwType !== null) {
			if (!$throwType->isVoid()->yes()) {
				return InternalThrowPoint::createExplicit($scope, $throwType, $normalizedMethodCall, true);
			}
		} elseif ($this->implicitThrows) {
			$methodReturnedType = $returnTypeCallback();
			if (!$context->isInThrow() || !(new ObjectType(Throwable::class))->isSuperTypeOf($methodReturnedType)->yes()) {
				return InternalThrowPoint::createImplicit($scope, $normalizedMethodCall);
			}
		}

		return null;
	}

}
