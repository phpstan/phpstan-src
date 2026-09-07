<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node;
use PHPStan\Analyser\ClosureParameterTypes;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\ArrayMapArgVisitor;
use PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Type\Type;

/** Resolve the same contextual parameters for body analysis and closure type inference. */
#[AutowiredService]
final class ClosureParameterResolver
{

	public function __construct(private NodeScopeResolver $nodeScopeResolver)
	{
	}

	/** @param Node\Arg[]|null $callArgs */
	public function resolve(
		MutatingScope $scope,
		Node\Expr\Closure|Node\Expr\ArrowFunction $expr,
		?ExpressionResultStorage $storage,
		?array $callArgs,
		?Type $passedToType,
		?Type $nativePassedToType,
	): ClosureParameterTypes
	{
		$arrayMapArgs = $expr->getAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME);
		$immediatelyInvokedArgs = $expr->getAttribute(ImmediatelyInvokedClosureVisitor::ARGS_ATTRIBUTE_NAME);
		$intrinsicArgs = $arrayMapArgs ?? $immediatelyInvokedArgs;
		if ($intrinsicArgs === null) {
			return new ClosureParameterTypes(
				$this->nodeScopeResolver->createCallableParameters($scope, $expr, $callArgs, $passedToType),
				$this->nodeScopeResolver->createNativeCallableParameters($scope, $expr, $callArgs, $nativePassedToType),
			);
		}

		$parameters = [];
		$nativeParameters = [];
		foreach ($intrinsicArgs as $arg) {
			$result = $storage !== null ? $storage->findExpressionResult($arg->value) : null;
			$type = $result !== null ? $result->getType() : $this->nodeScopeResolver->readScopeStateOrSyntheticType($arg->value, $scope);
			$nativeType = $result !== null ? $result->getNativeType() : $this->nodeScopeResolver->readScopeStateOrSyntheticType($arg->value, $scope->doNotTreatPhpDocTypesAsCertain());
			if ($arrayMapArgs !== null) {
				$type = $type->getIterableValueType();
				$nativeType = $nativeType->getIterableValueType();
			}
			$parameters[] = new DummyParameter('item', $type, optional: false, passedByReference: PassedByReference::createNo(), variadic: false, defaultValue: null);
			$nativeParameters[] = new DummyParameter('item', $nativeType, optional: false, passedByReference: PassedByReference::createNo(), variadic: false, defaultValue: null);
		}

		return new ClosureParameterTypes($parameters, $nativeParameters);
	}

}
