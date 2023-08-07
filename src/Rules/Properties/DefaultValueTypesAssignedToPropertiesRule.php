<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<ClassPropertyNode>
 */
class DefaultValueTypesAssignedToPropertiesRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$default = $node->getDefault();
		if ($default === null) {
			return [];
		}

		$classReflection = $node->getClassReflection();

		$propertyReflection = $classReflection->getNativeProperty($node->getName());
		$propertyType = $propertyReflection->getWritableType();
		if ($propertyReflection->getNativeType() instanceof MixedType) {
			if ($default instanceof Node\Expr\ConstFetch && (string) $default->name === 'null') {
				return [];
			}
		}
		$defaultValueType = $scope->getType($default);
		$accepts = $this->ruleLevelHelper->acceptsWithReason($propertyType, $defaultValueType, true);
		if ($accepts->result) {
			return [];
		}

		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($propertyType, $defaultValueType);

		return [
			RuleErrorBuilder::message(sprintf(
				'%s %s::$%s (%s) does not accept default value of type %s.',
				$node->isStatic() ? 'Static property' : 'Property',
				$classReflection->getDisplayName(),
				$node->getName(),
				$propertyType->describe($verbosityLevel),
				$defaultValueType->describe($verbosityLevel),
			))->acceptsReasonsTip($accepts->reasons)->build(),
		];
	}

}
