<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use function count;
use function lcfirst;
use function sprintf;

class FunctionPurityCheck
{

	/**
	 * @param 'Function'|'Method' $identifier
	 * @param ParameterReflectionWithPhpDocs[] $parameters
	 * @param ImpurePoint[] $impurePoints
	 * @param ThrowPoint[] $throwPoints
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
	): array
	{
		$errors = [];
		$isPure = $functionReflection->isPure();
		$isConstructor = false;
		if (
			$functionReflection instanceof ExtendedMethodReflection
			&& $functionReflection->getDeclaringClass()->hasConstructor()
			&& $functionReflection->getDeclaringClass()->getConstructor()->getName() === $functionReflection->getName()
		) {
			$isConstructor = true;
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

			if ($returnType->isVoid()->yes() && !$isConstructor) {
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
			if (count($impurePoints) === 0) {
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
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s returns void but does not have any side effects.',
					$functionDescription,
				))->identifier('void.pure')->build();
			}
		}

		return $errors;
	}

}
