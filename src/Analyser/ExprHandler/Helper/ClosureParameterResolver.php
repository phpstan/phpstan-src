<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\ClosureParameterTypes;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Parser\ArrayMapArgVisitor;
use PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_map;
use function count;
use function max;

/** Resolve the same contextual parameters for body analysis and closure type inference. */
#[AutowiredService]
final class ClosureParameterResolver
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private Container $container,
	)
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
				$this->createCallableParameters($scope, $expr, $callArgs, $passedToType),
				$this->createNativeCallableParameters($scope, $expr, $callArgs, $nativePassedToType),
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
	 * @param Node\Arg[]|null $args
	 * @return ParameterReflection[]|null
	 */
	private function createCallableParameters(MutatingScope $scope, Expr $closureExpr, ?array $args, ?Type $passedToType): ?array
	{
		return $this->doCreateCallableParameters($scope, $closureExpr, $args, $passedToType, fn (MutatingScope $s, Expr $e): Type => $this->resolveCallableTypeForScope($e, $s));
	}

	/**
	 * @param Node\Arg[]|null $args
	 * @return ParameterReflection[]|null
	 */
	private function createNativeCallableParameters(MutatingScope $scope, Expr $closureExpr, ?array $args, ?Type $nativePassedToType): ?array
	{
		return $this->doCreateCallableParameters($scope, $closureExpr, $args, $nativePassedToType, fn (MutatingScope $s, Expr $e): Type => $this->resolveCallableTypeForScope($e, $s->doNotTreatPhpDocTypesAsCertain()));
	}

	/**
	 * Resolves the type of an expression a callable parameter is derived from -
	 * either the closure/arrow function whose acceptors describe the parameters,
	 * or a call argument refining them. A closure/arrow function is resolved
	 * directly through ClosureTypeResolver (as Scope::getType() would), not by
	 * processing it on demand: createCallableParameters() runs while that very
	 * closure is being processed, so on-demand processing would re-enter
	 * processClosureNodeInternal() endlessly.
	 */
	public function resolveCallableTypeForScope(Expr $expr, MutatingScope $scope): Type
	{
		if ($expr instanceof Expr\Closure || $expr instanceof Expr\ArrowFunction) {
			return $this->container->getByType(ClosureTypeResolver::class)->getClosureType($scope, $expr, false, $scope->getCurrentExpressionResultStorage());
		}

		return $this->nodeScopeResolver->readTypeOfMaybeStored($expr, $scope);
	}

	/**
	 * @param Node\Arg[]|null $args
	 * @param Closure(MutatingScope, Expr): Type $typeGetter
	 * @return ParameterReflection[]|null
	 */
	private function doCreateCallableParameters(MutatingScope $scope, Expr $closureExpr, ?array $args, ?Type $passedToType, Closure $typeGetter): ?array
	{
		$callableParameters = null;
		if ($args !== null) {
			$closureType = $typeGetter($scope, $closureExpr);

			if ($closureType->isCallable()->no()) {
				return null;
			}

			$acceptors = $closureType->getCallableParametersAcceptors($scope);
			if (count($acceptors) === 1) {
				$callableParameters = $acceptors[0]->getParameters();

				foreach ($callableParameters as $index => $callableParameter) {
					if (!isset($args[$index])) {
						continue;
					}

					if ($callableParameter->isVariadic()) {
						$argTypes = [];
						$argNumber = count($args);
						for ($j = $index; $j < $argNumber; $j++) {
							$argTypes[] = $typeGetter($scope, $args[$j]->value);
						}
						$type = TypeCombinator::union(...$argTypes);
					} else {
						$type = $typeGetter($scope, $args[$index]->value);
					}
					$callableParameters[$index] = new NativeParameterReflection(
						$callableParameter->getName(),
						$callableParameter->isOptional(),
						$type,
						$callableParameter->passedByReference(),
						$callableParameter->isVariadic(),
						$callableParameter->getDefaultValue(),
					);
				}
			}
		} elseif ($passedToType !== null && !$passedToType->isCallable()->no()) {
			if ($passedToType instanceof UnionType) {
				$passedToType = $passedToType->filterTypes(static fn (Type $innerType) => $innerType->isCallable()->yes());

				if ($passedToType->isCallable()->no()) {
					return null;
				}
			}

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
		}

		return $callableParameters;
	}

}
