<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SideEffects;
use PHPStan\Rules\Functions\CallToFunctionStatementWithoutSideEffectsRule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ResourceType;
use PHPStan\Type\Type;
use function array_filter;
use function array_key_exists;
use function array_merge;
use function count;
use function in_array;
use function is_string;
use function lcfirst;
use function sprintf;

#[AutowiredService]
final class FunctionPurityCheck
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	/**
	 * @param 'Function'|'Method'|'PropertyHook' $identifier
	 * @param ExtendedParameterReflection[] $parameters
	 * @param ImpurePoint[] $impurePoints
	 * @param ThrowPoint[] $throwPoints
	 * @param Stmt[] $statements
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		Scope $scope,
		string $functionDescription,
		string $identifier,
		FunctionReflection|ExtendedMethodReflection $functionReflection,
		array $parameters,
		Type $returnType,
		array $impurePoints,
		array $throwPoints,
		array $statements,
		bool $isConstructor,
	): array
	{
		$errors = [];
		$isPure = $functionReflection->isPure();

		$pureUnlessCallableParameters = $functionReflection->getPureUnlessCallableIsImpureParameters();
		$pureUnlessCallableParamNames = [];
		foreach ($parameters as $parameter) {
			if (!array_key_exists($parameter->getName(), $pureUnlessCallableParameters)) {
				continue;
			}

			$pureUnlessCallableParamNames[$parameter->getName()] = true;

			$acceptors = $parameter->getType()->getCallableParametersAcceptors($scope);
			if (count($acceptors) === 0) {
				continue;
			}

			$allPure = true;
			foreach ($acceptors as $acceptor) {
				if ($acceptor->isPure()->yes()) {
					continue;
				}

				$allPure = false;
				break;
			}

			if (!$allPure) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s is marked @pure-unless-callable-is-impure for parameter $%s, but $%s is already a pure callable, so %s can be marked @phpstan-pure instead.',
				$functionDescription,
				$parameter->getName(),
				$parameter->getName(),
				lcfirst($functionDescription),
			))->identifier(sprintf('pure%s.redundantUnlessCallable', $identifier))->build();
		}

		$pureUnlessParameterPassedParameters = $functionReflection->getPureUnlessParameterPassedParameters();
		$pureUnlessParameterPassedParamNames = [];
		foreach ($parameters as $parameter) {
			if (!array_key_exists($parameter->getName(), $pureUnlessParameterPassedParameters)) {
				continue;
			}

			$pureUnlessParameterPassedParamNames[$parameter->getName()] = true;

			if (!$parameter->isOptional()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s is marked @pure-unless-parameter-passed for parameter $%s, but $%s is not optional, so %s is never pure.',
					$functionDescription,
					$parameter->getName(),
					$parameter->getName(),
					lcfirst($functionDescription),
				))->identifier(sprintf('pure%s.nonOptionalParameterPassed', $identifier))->build();
			}

			// Passing a by-value parameter cannot introduce a side effect on its own,
			// so the body would have to be impure for the tag to mean anything - and
			// that impurity is not conditional on the argument being passed. Only a
			// by-ref out parameter is written through without producing an impure
			// point, which is what makes the conditional verdict checkable.
			if (!$parameter->passedByReference()->no()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s is marked @pure-unless-parameter-passed for parameter $%s, but $%s is not passed by reference.',
				$functionDescription,
				$parameter->getName(),
				$parameter->getName(),
			))->identifier(sprintf('pure%s.parameterPassedNotByRef', $identifier))->build();
		}

		if ($isPure->yes()) {
			foreach ($parameters as $parameter) {
				if (!$parameter->passedByReference()->createsNewVariable()) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s is marked as pure but parameter $%s is passed by reference.',
					$functionDescription,
					$parameter->getName(),
				))->identifier(sprintf('pure%s.parameterByRef', $identifier))->build();
			}

			$throwType = $functionReflection->getThrowType();
			if (
				$returnType->isVoid()->yes()
				&& !$isConstructor
				&& ($throwType === null || $throwType->isVoid()->yes())
				&& $functionReflection->getAsserts()->getAll() === []
			) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s is marked as pure but returns void.',
					$functionDescription,
				))->identifier(sprintf('pure%s.void', $identifier))->build();
			}

			$errors = array_merge($errors, $this->reportImpurePoints($impurePoints, $pureUnlessCallableParamNames, $functionDescription));
		} elseif ($pureUnlessCallableParamNames !== [] || $pureUnlessParameterPassedParamNames !== []) {
			// A function declared @pure-unless-callable-is-impure is pure except
			// for the flagged callables, so its body is checked for purity while
			// the flagged callables' own invocations are exempt. The same holds for
			// @pure-unless-parameter-passed: writing through the flagged by-ref
			// parameter is not an impure point, so the rest of the body still has
			// to be pure for the conditional verdict to hold.
			$errors = array_merge($errors, $this->reportImpurePoints($impurePoints, $pureUnlessCallableParamNames, $functionDescription));
		} elseif ($isPure->no()) {
			if (
				count($throwPoints) === 0
				&& count($impurePoints) === 0
				&& count($functionReflection->getAsserts()->getAll()) === 0
				&& (
					!$functionReflection instanceof ExtendedMethodReflection
					|| $functionReflection->isFinal()->yes()
					|| $functionReflection->getDeclaringClass()->isFinal()
				)
			) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s is marked as impure but does not have any side effects.',
					$functionDescription,
				))->identifier(sprintf('impure%s.pure', $identifier))->build();
			}
		} elseif ($returnType->isVoid()->yes()) {
			if (
				count($throwPoints) === 0
				&& count($impurePoints) === 0
				&& !$isConstructor
				&& (!$functionReflection instanceof ExtendedMethodReflection || $functionReflection->isPrivate())
				&& count($functionReflection->getAsserts()->getAll()) === 0
			) {
				$hasByRef = false;
				foreach ($parameters as $parameter) {
					if (!$parameter->passedByReference()->createsNewVariable()) {
						continue;
					}

					$hasByRef = true;
					break;
				}

				$statements = array_filter($statements, static function (Stmt $stmt): bool {
					if ($stmt instanceof Stmt\Nop) {
						return false;
					}

					if (!$stmt instanceof Stmt\Expression) {
						return true;
					}
					if (!$stmt->expr instanceof FuncCall) {
						return true;
					}
					if (!$stmt->expr->name instanceof Name) {
						return true;
					}

					return !in_array($stmt->expr->name->toString(), CallToFunctionStatementWithoutSideEffectsRule::PHPSTAN_TESTING_FUNCTIONS, true);
				});

				if (!$hasByRef && count($statements) > 0) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'%s returns void but does not have any side effects.',
						$functionDescription,
					))->identifier('void.pure')->build();
				}
			}
		}

		return $errors;
	}

	/**
	 * @param ImpurePoint[] $impurePoints
	 * @param array<string, true> $pureUnlessCallableParamNames
	 * @return list<IdentifierRuleError>
	 */
	private function reportImpurePoints(array $impurePoints, array $pureUnlessCallableParamNames, string $functionDescription): array
	{
		$errors = [];
		foreach ($impurePoints as $impurePoint) {
			if ($this->isPureUnlessCallableInvocation($impurePoint, $pureUnlessCallableParamNames)) {
				continue;
			}
			// a warning about the arguments, or the last error left for an error getter,
			// does not make the result depend on anything else
			if ($impurePoint->getSideEffects() !== null && $impurePoint->getSideEffects()->hasOnly([SideEffects::WARNS, SideEffects::WRITES_GLOBAL_ERROR])->yes()) {
				continue;
			}
			if ($this->onlyWritesLocalArguments($impurePoint)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s %s in pure %s.',
				$impurePoint->isCertain() ? 'Impure' : 'Possibly impure',
				$impurePoint->getDescription(),
				lcfirst($functionDescription),
			))
				->line($impurePoint->getNode()->getStartLine())
				->identifier(sprintf(
					'%s.%s',
					$impurePoint->isCertain() ? 'impure' : 'possiblyImpure',
					$impurePoint->getIdentifier(),
				))
				->build();
		}

		return $errors;
	}

	/**
	 * A call whose only effects are writes to its arguments, and maybe a warning
	 * or the last error it leaves for an error getter, is weakly pure when
	 * each written argument is local: a variable passed by reference, which the
	 * call reassigns like an assignment would, or an object it creates. Writing
	 * through a by-ref argument to a property or a superglobal is an impure point
	 * of its own, so it is still reported.
	 */
	private function onlyWritesLocalArguments(ImpurePoint $impurePoint): bool
	{
		$call = $this->resolveCall($impurePoint);
		if ($call === null) {
			return false;
		}
		[$reflection, $parametersAcceptor, $normalizedNode] = $call;
		$scope = $impurePoint->getScope();

		// the impure point has the effects of this call when an extension describes them
		$sideEffects = $impurePoint->getSideEffects() ?? $reflection->getSideEffects();
		if (!$sideEffects->hasOnly([SideEffects::WRITES_ARGS, SideEffects::WARNS, SideEffects::WRITES_GLOBAL_ERROR])->yes() || $sideEffects->getCalledParameters() !== []) {
			return false;
		}

		$parameters = $parametersAcceptor->getParameters();
		foreach ($normalizedNode->getArgs() as $i => $arg) {
			$parameter = $parameters[$i] ?? ($parametersAcceptor->isVariadic() ? $parameters[count($parameters) - 1] ?? null : null);
			if ($parameter === null || $arg->unpack) {
				return false;
			}
			if ($sideEffects->writesParameter($parameter->getName())->no()) {
				continue;
			}
			if ($parameter->passedByReference()->yes() || $arg->value instanceof New_) {
				continue;
			}

			$argType = $scope->getType($arg->value);
			if (!$argType->isObject()->no() || !(new ResourceType())->isSuperTypeOf($argType)->no()) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The called function or method of an impure point, its selected
	 * variant, and the call with its arguments in parameter order.
	 *
	 * @return array{FunctionReflection|ExtendedMethodReflection, ParametersAcceptor, FuncCall|MethodCall|StaticCall}|null
	 */
	private function resolveCall(ImpurePoint $impurePoint): ?array
	{
		$node = $impurePoint->getNode();
		$scope = $impurePoint->getScope();
		if ($node instanceof FuncCall && $node->name instanceof Name) {
			if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
				return null;
			}
			$reflection = $this->reflectionProvider->getFunction($node->name, $scope);
		} elseif ($node instanceof MethodCall && $node->name instanceof Identifier) {
			$reflection = $scope->getMethodReflection($scope->getType($node->var), $node->name->name);
		} elseif ($node instanceof StaticCall && $node->name instanceof Identifier && $node->class instanceof Name) {
			$reflection = $scope->getMethodReflection($scope->resolveTypeByName($node->class), $node->name->name);
		} else {
			return null;
		}

		if ($reflection === null || $node->isFirstClassCallable()) {
			return null;
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $reflection->getVariants(), $reflection->getNamedArgumentsVariants());
		if ($node instanceof FuncCall) {
			$normalizedNode = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);
		} elseif ($node instanceof MethodCall) {
			$normalizedNode = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $node);
		} else {
			$normalizedNode = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $node);
		}
		if ($normalizedNode === null) {
			return null;
		}

		return [$reflection, $parametersAcceptor, $normalizedNode];
	}

	/**
	 * A call of one of the function's own callable parameters, directly or by
	 * a function it is passed to, or a call that is impure only through them:
	 * a function that is pure unless its callable is impure passing its
	 * callable on to another one.
	 *
	 * @param array<string, true> $pureUnlessCallableParamNames
	 */
	private function isPureUnlessCallableInvocation(ImpurePoint $impurePoint, array $pureUnlessCallableParamNames): bool
	{
		if ($pureUnlessCallableParamNames === []) {
			return false;
		}

		$node = $impurePoint->getNode();
		if ($node instanceof FuncCall && $node->name instanceof Variable) {
			return is_string($node->name->name) && array_key_exists($node->name->name, $pureUnlessCallableParamNames);
		}
		// the callable passed to a parameter that invokes it
		if ($node instanceof Variable) {
			return is_string($node->name) && array_key_exists($node->name, $pureUnlessCallableParamNames);
		}

		if ($impurePoint->isCertain()) {
			return false;
		}

		$call = $this->resolveCall($impurePoint);
		if ($call === null) {
			return false;
		}
		[, $parametersAcceptor, $normalizedNode] = $call;
		$scope = $impurePoint->getScope();

		$forwarded = false;
		$parameters = $parametersAcceptor->getParameters();
		foreach ($normalizedNode->getArgs() as $i => $arg) {
			$parameter = $parameters[$i] ?? ($parametersAcceptor->isVariadic() ? $parameters[count($parameters) - 1] ?? null : null);
			if ($parameter === null || $arg->unpack) {
				return false;
			}
			if (!$parameter instanceof ExtendedParameterReflection || $parameter->isPureUnlessCallableIsImpureParameter()->no()) {
				continue;
			}
			if ($arg->value instanceof Variable && is_string($arg->value->name) && array_key_exists($arg->value->name, $pureUnlessCallableParamNames)) {
				$forwarded = true;
				continue;
			}

			$argType = $scope->getType($arg->value);
			if ($argType->isNull()->yes()) {
				continue;
			}
			if (!$argType->isCallable()->yes()) {
				return false;
			}
			foreach ($argType->getCallableParametersAcceptors($scope) as $acceptor) {
				if (!$acceptor->isPure()->yes()) {
					return false;
				}
			}
		}

		return $forwarded;
	}

}
