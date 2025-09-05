<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Type\TypeCombinator;
use function count;
use function sprintf;

/**
 * @implements Rule<ClassPropertiesNode>
 */
#[RegisteredRule(level: 4)]
final class TooWidePropertyTypeRule implements Rule
{

	public function __construct(
		private ReadWritePropertiesExtensionProvider $extensionProvider,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private TooWideTypeCheck $check,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertiesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		$classReflection = $node->getClassReflection();

		foreach ($node->getProperties() as $property) {
			if (!$property->isPrivate()) {
				continue;
			}
			if ($property->isDeclaredInTrait()) {
				continue;
			}
			if ($property->isPromoted()) {
				continue;
			}
			$propertyName = $property->getName();
			if (!$classReflection->hasNativeProperty($propertyName)) {
				continue;
			}

			$propertyReflection = $classReflection->getNativeProperty($propertyName);
			$propertyType = $propertyReflection->getWritableType();
			$phpdocType = $propertyReflection->getPhpDocType();

			$propertyType = $this->check->findTypeToCheck($propertyType, $phpdocType, $scope);
			if ($propertyType === null) {
				continue;
			}

			foreach ($this->extensionProvider->getExtensions() as $extension) {
				if ($extension->isAlwaysRead($propertyReflection, $propertyName)) {
					continue 2;
				}
				if ($extension->isAlwaysWritten($propertyReflection, $propertyName)) {
					continue 2;
				}
				if ($extension->isInitialized($propertyReflection, $propertyName)) {
					continue 2;
				}
			}

			$assignedTypes = [];
			foreach ($node->getPropertyAssigns() as $assign) {
				$assignNode = $assign->getAssign();
				$assignPropertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($assignNode->getPropertyFetch(), $assign->getScope());
				foreach ($assignPropertyReflections as $assignPropertyReflection) {
					if ($propertyName !== $assignPropertyReflection->getName()) {
						continue;
					}
					if ($propertyReflection->getDeclaringClass()->getName() !== $assignPropertyReflection->getDeclaringClass()->getName()) {
						continue;
					}

					$assignedTypes[] = $assignPropertyReflection->getScope()->getType($assignNode->getAssignedExpr());
				}
			}

			if ($property->getDefault() !== null) {
				$assignedTypes[] = $scope->getType($property->getDefault());
			}

			if (count($assignedTypes) === 0) {
				continue;
			}

			$assignedType = TypeCombinator::union(...$assignedTypes);
			$propertyDescription = $this->describePropertyByName($propertyReflection, $propertyName);
			foreach ($this->check->checkProperty($property, $propertyType, $propertyDescription, $assignedType) as $error) {
				$errors[] = $error;
			}
		}
		return $errors;
	}

	private function describePropertyByName(PropertyReflection $property, string $propertyName): string
	{
		if (!$property->isStatic()) {
			return sprintf('Property %s::$%s', $property->getDeclaringClass()->getDisplayName(), $propertyName);
		}

		return sprintf('Static property %s::$%s', $property->getDeclaringClass()->getDisplayName(), $propertyName);
	}

}
