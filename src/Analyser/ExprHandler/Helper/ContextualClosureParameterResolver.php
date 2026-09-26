<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node;
use PHPStan\Analyser\ClosureParameterTypes;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\Generics\ClosureSignatureInference;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\ArrayMapArgVisitor;
use PHPStan\Parser\ArrowFunctionArgVisitor;
use PHPStan\Parser\ClosureArgVisitor;
use PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
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
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../../turbo-ext/src/ContextualClosureParameterResolver.cpp')]
final class ContextualClosureParameterResolver
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private ClosureSignatureInference $closureSignatureInference,
	)
	{
	}

	public function hasIntrinsicArgs(Node\Expr\Closure|Node\Expr\ArrowFunction $expr): bool
	{
		return $expr->getAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME) !== null
			|| $expr->getAttribute(ImmediatelyInvokedClosureVisitor::ARGS_ATTRIBUTE_NAME) !== null;
	}

	/**
	 * Whether the closure's parameters are typed by what surrounds it
	 * syntactically - array_map() or an immediate invocation - rather than by
	 * a callable type it is passed to.
	 */
	public function hasOwnContext(Node\Expr\Closure|Node\Expr\ArrowFunction $expr): bool
	{
		return $this->hasIntrinsicArgs($expr)
			|| $expr->getAttribute(ClosureArgVisitor::ATTRIBUTE_NAME) !== null
			|| $expr->getAttribute(ArrowFunctionArgVisitor::ATTRIBUTE_NAME) !== null;
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
			if ($passedToType === null && !$this->hasOwnContext($expr)) {
				// nothing types the closure where it is written: the second pass
				// of the enclosing body walks it with what its usages resolved
				return new ClosureParameterTypes(
					$this->closureSignatureInference->getBodyParameters($scope, $expr),
					null,
				);
			}

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
	 * The types the closure's returned expressions are expected to have: the
	 * return type of the callable it is passed to, or - for a closure nothing
	 * types where it is written - the return type of the callables its value is
	 * sent to later (see ClosureSignatureInference).
	 *
	 * @return array{Type|null, Type|null}
	 */
	public function resolveExpectedReturnTypes(
		MutatingScope $scope,
		Node\Expr\Closure|Node\Expr\ArrowFunction $expr,
		?Type $passedToType,
		?Type $nativePassedToType,
	): array
	{
		if ($this->hasOwnContext($expr)) {
			return [null, null];
		}
		if ($passedToType === null) {
			return [$this->closureSignatureInference->getExpectedReturnType($scope, $expr), null];
		}

		return [
			$this->createPassedToTypeReturnType($scope, $passedToType),
			$this->createPassedToTypeReturnType($scope, $nativePassedToType),
		];
	}

	private function createPassedToTypeReturnType(MutatingScope $scope, ?Type $passedToType): ?Type
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

		$returnTypes = [];
		foreach ($passedToType->getCallableParametersAcceptors($scope) as $acceptor) {
			$returnType = $acceptor->getReturnType();
			if ($returnType instanceof MixedType || $returnType->isVoid()->yes() || $returnType->hasTemplateOrLateResolvableType()) {
				return null;
			}
			$returnTypes[] = $returnType;
		}
		if ($returnTypes === []) {
			return null;
		}

		return TypeCombinator::union(...$returnTypes);
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
