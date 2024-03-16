<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Analyser\ImpurePoint;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use function lcfirst;
use function sprintf;

class FunctionPurityCheck
{

	/**
	 * @param 'Function'|'Method' $identifier
	 * @param ParameterReflectionWithPhpDocs[] $parameters
	 * @param ImpurePoint[] $impurePoints
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		string $functionDescription,
		string $identifier,
		FunctionReflection|ExtendedMethodReflection $functionReflection,
		array $parameters,
		Type $returnType,
		array $impurePoints,
	): array
	{
		$errors = [];
		if ($functionReflection->isPure()->yes()) {
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

			if ($returnType->isVoid()->yes()) {
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
					->identifier(sprintf('impure.%s', $impurePoint->getIdentifier()))
					->build();
			}
		}

		return $errors;
	}

}
