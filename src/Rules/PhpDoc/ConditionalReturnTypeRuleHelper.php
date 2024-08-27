<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ConditionalType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function count;
use function sprintf;
use function substr;

final class ConditionalReturnTypeRuleHelper
{

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function check(ParametersAcceptorWithPhpDocs $acceptor): array
	{
		$conditionalTypes = [];
		$parametersByName = [];
		foreach ($acceptor->getParameters() as $parameter) {
			TypeTraverser::map($parameter->getType(), static function (Type $type, callable $traverse) use (&$conditionalTypes): Type {
				if ($type instanceof ConditionalType || $type instanceof ConditionalTypeForParameter) {
					$conditionalTypes[] = $type;
				}

				return $traverse($type);
			});

			if ($parameter->getOutType() !== null) {
				TypeTraverser::map($parameter->getOutType(), static function (Type $type, callable $traverse) use (&$conditionalTypes): Type {
					if ($type instanceof ConditionalType || $type instanceof ConditionalTypeForParameter) {
						$conditionalTypes[] = $type;
					}

					return $traverse($type);
				});
			}

			if ($parameter->getClosureThisType() !== null) {
				TypeTraverser::map($parameter->getClosureThisType(), static function (Type $type, callable $traverse) use (&$conditionalTypes): Type {
					if ($type instanceof ConditionalType || $type instanceof ConditionalTypeForParameter) {
						$conditionalTypes[] = $type;
					}

					return $traverse($type);
				});
			}

			$parametersByName[$parameter->getName()] = $parameter;
		}

		TypeTraverser::map($acceptor->getReturnType(), static function (Type $type, callable $traverse) use (&$conditionalTypes): Type {
			if ($type instanceof ConditionalType || $type instanceof ConditionalTypeForParameter) {
				$conditionalTypes[] = $type;
			}

			return $traverse($type);
		});

		$errors = [];
		foreach ($conditionalTypes as $conditionalType) {
			if ($conditionalType instanceof ConditionalType) {
				$subjectType = $conditionalType->getSubject();
				if ($subjectType instanceof StaticType) {
					continue;
				}
				$templateTypes = [];
				TypeTraverser::map($subjectType, static function (Type $type, callable $traverse) use (&$templateTypes): Type {
					if ($type instanceof TemplateType) {
						$templateTypes[] = $type;
						return $type;
					}

					return $traverse($type);
				});

				if (count($templateTypes) === 0) {
					$errors[] = RuleErrorBuilder::message(sprintf('Conditional return type uses subject type %s which is not part of PHPDoc @template tags.', $subjectType->describe(VerbosityLevel::typeOnly())))
						->identifier('conditionalType.subjectNotFound')
						->build();
					continue;
				}
			} else {
				$parameterName = substr($conditionalType->getParameterName(), 1);
				if (!array_key_exists($parameterName, $parametersByName)) {
					$errors[] = RuleErrorBuilder::message(sprintf('Conditional return type references unknown parameter $%s.', $parameterName))
						->identifier('parameter.notFound')
						->build();
					continue;
				}
				$subjectType = $parametersByName[$parameterName]->getType();
			}

			$targetType = $conditionalType->getTarget();
			$isTargetSuperType = $targetType->isSuperTypeOf($subjectType);
			if ($isTargetSuperType->maybe()) {
				continue;
			}

			$verbosity = VerbosityLevel::getRecommendedLevelByType($subjectType, $targetType);

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Condition "%s" in conditional return type is always %s.',
				sprintf('%s %s %s', $subjectType->describe($verbosity), $conditionalType->isNegated() ? 'is not' : 'is', $targetType->describe($verbosity)),
				$conditionalType->isNegated()
					? ($isTargetSuperType->yes() ? 'false' : 'true')
					: ($isTargetSuperType->yes() ? 'true' : 'false'),
			))
				->identifier(sprintf('conditionalType.always%s', $conditionalType->isNegated()
					? ($isTargetSuperType->yes() ? 'False' : 'True')
					: ($isTargetSuperType->yes() ? 'True' : 'False')))
				->build();
		}

		return $errors;
	}

}
