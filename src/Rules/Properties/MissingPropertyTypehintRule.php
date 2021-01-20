<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\ClassPropertyNode>
 */
final class MissingPropertyTypehintRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\MissingTypehintCheck $missingTypehintCheck;

	public function __construct(MissingTypehintCheck $missingTypehintCheck)
	{
		$this->missingTypehintCheck = $missingTypehintCheck;
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$propertyReflection = $scope->getClassReflection()->getNativeProperty($node->getName());
		$propertyType = $propertyReflection->getReadableType();
		if ($propertyType instanceof MixedType && !$propertyType->isExplicitMixed()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Property %s::$%s has no typehint specified.',
					$propertyReflection->getDeclaringClass()->getDisplayName(),
					$node->getName()
				))->build(),
			];
		}

		$messages = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($propertyType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s type has no value type specified in iterable type %s.',
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$node->getName(),
				$iterableTypeDescription
			))->tip(sprintf(MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP, $iterableTypeDescription))->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($propertyType) as [$name, $genericTypeNames]) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s with generic %s does not specify its types: %s',
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$node->getName(),
				$name,
				implode(', ', $genericTypeNames)
			))->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingPrototype($propertyType) as $callableType) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s type has no prototype specified for callable type %s.',
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$node->getName(),
				$callableType->describe(VerbosityLevel::typeOnly())
			))->build();
		}

		return $messages;
	}

}
