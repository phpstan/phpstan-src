<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassPropertiesNode>
 */
class UninitializedPropertyRule implements Rule
{

	/** @var string[] */
	private array $additionalConstructors;

	/** @var array<string, string[]> */
	private array $additionalConstructorsCache = [];

	/**
	 * @param string[] $additionalConstructors
	 */
	public function __construct(array $additionalConstructors)
	{
		$this->additionalConstructors = $additionalConstructors;
	}

	public function getNodeType(): string
	{
		return ClassPropertiesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$classReflection = $scope->getClassReflection();
		[$properties, $prematureAccess] = $node->getUninitializedProperties($scope, $this->getConstructors($classReflection));

		$errors = [];
		foreach ($properties as $propertyName => $propertyNode) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Class %s has an uninitialized property $%s. Give it default value or assign it in the constructor.',
				$classReflection->getDisplayName(),
				$propertyName
			))->line($propertyNode->getLine())->build();
		}

		foreach ($prematureAccess as [$propertyName, $line]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Access to an uninitialized property %s::$%s.',
				$classReflection->getDisplayName(),
				$propertyName
			))->line($line)->build();
		}

		return $errors;
	}

	/**
	 * @param ClassReflection $classReflection
	 * @return string[]
	 */
	private function getConstructors(ClassReflection $classReflection): array
	{
		if (array_key_exists($classReflection->getName(), $this->additionalConstructorsCache)) {
			return $this->additionalConstructorsCache[$classReflection->getName()];
		}
		$constructors = [];
		if ($classReflection->hasConstructor()) {
			$constructors[] = $classReflection->getConstructor()->getName();
		}

		foreach ($this->additionalConstructors as $additionalConstructor) {
			[$className, $methodName] = explode('::', $additionalConstructor);
			foreach ($classReflection->getNativeMethods() as $nativeMethod) {
				if ($nativeMethod->getName() !== $methodName) {
					continue;
				}
				if ($nativeMethod->getDeclaringClass()->getName() !== $classReflection->getName()) {
					continue;
				}

				$prototype = $nativeMethod->getPrototype();
				if ($prototype->getDeclaringClass()->getName() !== $className) {
					continue;
				}

				$constructors[] = $methodName;
			}
		}

		$this->additionalConstructorsCache[$classReflection->getName()] = $constructors;

		return $constructors;
	}

}
