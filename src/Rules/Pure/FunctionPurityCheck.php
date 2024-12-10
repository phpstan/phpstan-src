<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\Functions\CallToFunctionStatementWithoutSideEffectsRule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use function array_filter;
use function count;
use function in_array;
use function lcfirst;
use function sprintf;

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

			foreach ($impurePoints as $impurePoint) {
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
		} elseif ($isPure->no()) {
			if (
				count($throwPoints) === 0
				&& count($impurePoints) === 0
				&& count($functionReflection->getAsserts()->getAll()) === 0
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

}
