<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\TypeUtils;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<PropertyAssignNode>
 */
class ReadOnlyPropertyAssignRule implements Rule
{

	public function __construct(
		private PropertyReflectionFinder $propertyReflectionFinder,
		private ConstructorsHelper $constructorsHelper,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return PropertyAssignNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$propertyFetch = $node->getPropertyFetch();
		if (!$propertyFetch instanceof Node\Expr\PropertyFetch) {
			return [];
		}

		$errors = [];
		$reflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($propertyFetch, $scope);
		foreach ($reflections as $propertyReflection) {
			$nativeReflection = $propertyReflection->getNativeReflection();
			if ($nativeReflection === null) {
				continue;
			}
			if (!$scope->canAccessProperty($propertyReflection)) {
				continue;
			}
			if (!$nativeReflection->isReadOnly()) {
				continue;
			}

			$declaringClass = $nativeReflection->getDeclaringClass();

			if (!$scope->isInClass()) {
				$errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s is assigned outside of its declaring class.', $declaringClass->getDisplayName(), $propertyReflection->getName()))
					->identifier('property.readOnlyAssignOutOfClass')
					->build();
				continue;
			}

			$scopeClassReflection = $scope->getClassReflection();
			if ($scopeClassReflection->getName() !== $declaringClass->getName()) {
				$errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s is assigned outside of its declaring class.', $declaringClass->getDisplayName(), $propertyReflection->getName()))
					->identifier('property.readOnlyAssignOutOfClass')
					->build();
				continue;
			}

			$scopeMethod = $scope->getFunction();
			if (!$scopeMethod instanceof MethodReflection) {
				throw new ShouldNotHappenException();
			}

			$methodName = $scopeMethod->getName();
			if (
				in_array($methodName, $this->constructorsHelper->getConstructors($scopeClassReflection), true)
				|| strtolower($methodName) === '__unserialize'
				|| ($this->phpVersion->supportsReadonlyPropertyReinitializationOnClone() && strtolower($methodName) === '__clone')
			) {
				if (TypeUtils::findThisType($scope->getType($propertyFetch->var)) === null) {
					$errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s is not assigned on $this.', $declaringClass->getDisplayName(), $propertyReflection->getName()))
						->identifier('property.readOnlyAssignNotOnThis')
						->build();
				}

				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s is assigned outside of the constructor.', $declaringClass->getDisplayName(), $propertyReflection->getName()))
				->identifier('property.readOnlyAssignNotInConstructor')
				->build();
		}

		return $errors;
	}

}
