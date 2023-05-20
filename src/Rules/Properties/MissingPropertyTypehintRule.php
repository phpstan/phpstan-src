<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use function implode;
use function sprintf;

/**
 * @implements Rule<ClassPropertyNode>
 */
final class MissingPropertyTypehintRule implements Rule
{

	public function __construct(private MissingTypehintCheck $missingTypehintCheck)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$propertyReflection = $scope->getClassReflection()->getNativeProperty($node->getName());

		if ($propertyReflection->isPromoted()) {
			return [];
		}

		$propertyType = $propertyReflection->getReadableType();

		if ($propertyType instanceof MixedType && !$propertyType->isExplicitMixed()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Property %s::$%s has no type specified.',
					$propertyReflection->getDeclaringClass()->getDisplayName(),
					$node->getName(),
				))->identifier('missingType.property')->build(),
			];
		}

		$messages = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($propertyType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s type has no value type specified in iterable type %s.',
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$node->getName(),
				$iterableTypeDescription,
			))
				->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
				->identifier('missingType.iterableValue')
				->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($propertyType) as [$name, $genericTypeNames]) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s with generic %s does not specify its types: %s',
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$node->getName(),
				$name,
				implode(', ', $genericTypeNames),
			))
				->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)
				->identifier('missingType.generics')
				->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($propertyType) as $callableType) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s type has no signature specified for %s.',
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$node->getName(),
				$callableType->describe(VerbosityLevel::typeOnly()),
			))->identifier('missingType.callable')->build();
		}

		return $messages;
	}

}
