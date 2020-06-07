<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Property>
 */
class DefaultValueTypesAssignedToPropertiesRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	public function __construct(RuleLevelHelper $ruleLevelHelper)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return Property::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$classReflection = $scope->getClassReflection();

		$errors = [];
		foreach ($node->props as $property) {
			if ($property->default === null) {
				continue;
			}

			$propertyReflection = $classReflection->getNativeProperty($property->name->name);
			$propertyType = $propertyReflection->getWritableType();
			if ($propertyReflection->getNativeType() instanceof MixedType) {
				if ($property->default instanceof Node\Expr\ConstFetch && (string) $property->default->name === 'null') {
					continue;
				}
			}
			$defaultValueType = $scope->getType($property->default);
			if ($this->ruleLevelHelper->accepts($propertyType, $defaultValueType, true)) {
				continue;
			}

			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($propertyType);

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s %s::$%s (%s) does not accept default value of type %s.',
				$node->isStatic() ? 'Static property' : 'Property',
				$classReflection->getDisplayName(),
				$property->name->name,
				$propertyType->describe($verbosityLevel),
				$defaultValueType->describe($verbosityLevel)
			))->build();
		}

		return $errors;
	}

}
