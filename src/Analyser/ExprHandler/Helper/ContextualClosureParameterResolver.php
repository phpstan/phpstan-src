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
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_map;
use function count;
use function max;

/**
 * Closure parameters derived from the closure's context alone - the values
 * array_map() or an immediate invocation feeds it, or the callable type it is
 * passed to. Needs no closure type, so ClosureTypeResolver builds parameters
 * through it, while ClosureParameterResolver refines them from call arguments.
 */
#[AutowiredService]
final class ContextualClosureParameterResolver
{

	public function __construct(private NodeScopeResolver $nodeScopeResolver)
	{
	}

	public function hasIntrinsicArgs(Node\Expr\Closure|Node\Expr\ArrowFunction $expr): bool
	{
		return $expr->getAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME) !== null
			|| $expr->getAttribute(ImmediatelyInvokedClosureVisitor::ARGS_ATTRIBUTE_NAME) !== null;
	}

	public function resolve(
		MutatingScope $scope,
		Node\Expr\Closure|Node\Expr\ArrowFunction $expr,
		?ExpressionResultStorage $storage,
		?Type $passedToType,
		?Type $nativePassedToType,
	): ClosureParameterTypes
	{
		$arrayMapArgs = $expr->getAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME);
		$immediatelyInvokedArgs = $expr->getAttribute(ImmediatelyInvokedClosureVisitor::ARGS_ATTRIBUTE_NAME);
		$intrinsicArgs = $arrayMapArgs ?? $immediatelyInvokedArgs;
		if ($intrinsicArgs === null) {
			return new ClosureParameterTypes(
				$this->createPassedToTypeParameters($scope, $passedToType),
				$this->createPassedToTypeParameters($scope, $nativePassedToType),
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

	/**
	 * @return ParameterReflection[]|null
	 */
	private function createPassedToTypeParameters(MutatingScope $scope, ?Type $passedToType): ?array
	{
		if ($passedToType === null || $passedToType->isCallable()->no()) {
			return null;
		}

		if ($passedToType instanceof UnionType) {
			$passedToType = $passedToType->filterTypes(static fn (Type $innerType) => $innerType->isCallable()->yes());

			if ($passedToType->isCallable()->no()) {
				return null;
			}
		}

		$callableParameters = null;
		$acceptors = $passedToType->getCallableParametersAcceptors($scope);
		foreach ($acceptors as $acceptor) {
			$acceptorParameters = array_map(static fn (ParameterReflection $callableParameter) => new NativeParameterReflection(
				$callableParameter->getName(),
				$callableParameter->isOptional(),
				$callableParameter->getType(),
				$callableParameter->passedByReference(),
				$callableParameter->isVariadic(),
				$callableParameter->getDefaultValue(),
			), $acceptor->getParameters());

			if ($callableParameters === null) {
				$callableParameters = $acceptorParameters;
				continue;
			}

			$newParameters = [];
			$parameterCount = max(count($callableParameters), count($acceptorParameters));
			for ($i = 0; $i < $parameterCount; $i++) {
				if (!array_key_exists($i, $acceptorParameters)) {
					$newParameters[] = $callableParameters[$i]->toOptional();
					continue;
				}

				if (!array_key_exists($i, $callableParameters)) {
					$newParameters[] = $acceptorParameters[$i]->toOptional();
					continue;
				}

				$newParameters[] = $callableParameters[$i]->union($acceptorParameters[$i]);
			}

			$callableParameters = $newParameters;
		}

		return $callableParameters;
	}

}
