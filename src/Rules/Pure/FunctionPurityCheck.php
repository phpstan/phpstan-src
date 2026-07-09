<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\Functions\CallToFunctionStatementWithoutSideEffectsRule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
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

	/**
	 * @param 'Function'|'Method' $identifier
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

		foreach ($parameters as $parameter) {
			if (!$parameter->isPureUnlessParameterPassedParameter()->yes()) {
				continue;
			}
			if ($parameter->isOptional()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s is marked @pure-unless-parameter-passed for parameter $%s, but $%s is not optional, so %s is never pure.',
				$functionDescription,
				$parameter->getName(),
				$parameter->getName(),
				lcfirst($functionDescription),
			))->identifier(sprintf('pure%s.nonOptionalParameterPassed', $identifier))->build();
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
		} elseif ($pureUnlessCallableParamNames !== []) {
			// A function declared @pure-unless-callable-is-impure is pure except
			// for the flagged callables, so its body is checked for purity while
			// the flagged callables' own invocations are exempt.
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
	 * @param array<string, true> $pureUnlessCallableParamNames
	 */
	private function isPureUnlessCallableInvocation(ImpurePoint $impurePoint, array $pureUnlessCallableParamNames): bool
	{
		if ($pureUnlessCallableParamNames === []) {
			return false;
		}

		$node = $impurePoint->getNode();
		if (!$node instanceof FuncCall) {
			return false;
		}
		if (!$node->name instanceof Variable) {
			return false;
		}
		if (!is_string($node->name->name)) {
			return false;
		}

		return array_key_exists($node->name->name, $pureUnlessCallableParamNames);
	}

}
