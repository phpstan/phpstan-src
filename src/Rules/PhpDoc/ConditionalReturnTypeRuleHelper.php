<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ConditionalType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function sprintf;
use function substr;

class ConditionalReturnTypeRuleHelper
{

	/**
	 * @return RuleError[]
	 */
	public function check(ParametersAcceptor $acceptor): array
	{
		$templateTypeMap = $acceptor->getTemplateTypeMap();
		$parametersByName = [];
		foreach ($acceptor->getParameters() as $parameter) {
			$parametersByName[$parameter->getName()] = $parameter;
		}

		$conditionalTypes = [];
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
				if (!$subjectType instanceof TemplateType || $templateTypeMap->getType($subjectType->getName()) === null) {
					$errors[] = RuleErrorBuilder::message(sprintf('Conditional return type uses subject type %s which is not part of PHPDoc @template tags.', $subjectType->describe(VerbosityLevel::typeOnly())))->build();
					continue;
				}
			} else {
				$parameterName = substr($conditionalType->getParameterName(), 1);
				if (!array_key_exists($parameterName, $parametersByName)) {
					$errors[] = RuleErrorBuilder::message(sprintf('Conditional return type references unknown parameter $%s.', $parameterName))->build();
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
			))->build();
		}

		return $errors;
	}

}
